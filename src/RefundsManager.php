<?php
/**
 * Easy Digital Downloads refunds
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2021 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads
 */

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

use EDD_Payment;
use Exception;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Easy Digital Downloads refunds
 *
 * @author  Reüel van der Steege
 * @version 2.2.0
 * @since   2.2.0
 */
class RefundsManager {
	public function setup() {
		// Actions.
		\add_action( 'edd_view_order_details_before', array( $this, 'order_admin_script' ), 100 );
		\add_action( 'edd_pre_refund_payment', array( $this, 'maybe_refund_payment' ), 999 );
		\add_action( 'pronamic_pay_update_payment', array( $this, 'maybe_update_refunded_payment' ), 10, 1 );
	}

	/**
	 * Add checkbox to refund payment when viewing order.
	 *
	 * @param int $payment_id Easy Digital Downloads payment ID.
	 * @return void
	 */
	public function order_admin_script( $payment_id = 0 ) {
		// Check gateway.
		$payment_gateway = edd_get_payment_gateway( $payment_id );

		if ( 'pronamic_' !== substr( $payment_gateway, 0, 9 ) ) {
			return;
		}

		// Check config.
		$config_id = EasyDigitalDownloads::get_pronamic_config_id( $payment_gateway );

		$gateway = Plugin::get_gateway( $config_id );

		if ( null === $gateway || ! $gateway->supports( 'refunds' ) ) {
			return;
		}

		?>

		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$( 'select[name="edd-payment-status"]' ).change( function() {
					if ( 'refunded' == $( this ).val() ) {
						$( this ).parent().parent().append( '<input type="checkbox" id="edd-pronamic-pay-refund" name="edd-pronamic-pay-refund" value="1" style="margin-top:0">' );
						$( this ).parent().parent().append( '<label for="edd-pronamic-pay-refund"><?php echo \esc_html( __( 'Refund payment at gateway', 'pronamic_ideal' ) ); ?></label>' );
					} else {
						$( '#edd-pronamic-pay-refund' ).remove();
						$( 'label[for="edd-pronamic-pay-refund"]' ).remove();
					}
				} );
			} );
		</script>

		<?php
	}

	/**
	 * Maybe refund payment.
	 *
	 * @param EDD_Payment $edd_payment Easy Digital Downloads payment.
	 * @return void
	 */
	public function maybe_refund_payment( EDD_Payment $edd_payment ) {
		// Check refund request.
		if ( ! \filter_has_var( \INPUT_POST, 'edd-pronamic-pay-refund' ) ) {
			return;
		}

		// Check payment.
		$payment = \get_pronamic_payment_by_transaction_id( \edd_get_payment_transaction_id( $edd_payment->ID ) );

		if ( null === $payment ) {
			return;
		}

		// Process refund.
		try {
			$this->process_refund( $edd_payment, $payment );
		} catch ( \Exception $e ) {
			wp_die( \esc_html( $e->getMessage() ) );

			exit;
		}
	}

	/**
	 * Process refund.
	 *
	 * @param EDD_Payment $edd_payment Easy Digital Downloads payment.
	 * @param Payment     $payment     Pronamic payment.
	 * @throws \Exception Throws exception if original gateway does not exist anymore.
	 */
	private function process_refund( EDD_Payment $edd_payment, Payment $payment ) {
		// Check gateway.
		$config_id = $payment->get_config_id();

		$gateway = Plugin::get_gateway( $config_id );

		if ( null === $gateway ) {
			throw new Exception( __( 'Unable to process refund because gateway does not exist.', 'pronamic_ideal' ) );
		}

		// Transaction ID.
		$transaction_id = \edd_get_payment_transaction_id( $edd_payment->ID );

		// Create refund.
		$amount = new Money(
			$edd_payment->get_meta( '_edd_payment_total', true ),
			$payment->get_total_amount()->get_currency()
		);

		$refund_reference = Plugin::create_refund( $transaction_id, $gateway, $amount );

		// Update payment amount refunded.
		$edd_refunded_amount = $edd_payment->get_meta( '_pronamic_pay_amount_refunded', true );

		$refunded_amount = $payment->get_refunded_amount();

		if ( null === $refunded_amount ) {
			$refunded_amount = new Money( 0, $payment->get_total_amount()->get_currency() );
		}

		$refunded_amount->add( $amount );

		$edd_payment->update_meta( '_pronamic_pay_amount_refunded', $refunded_amount->get_value(), $edd_refunded_amount );

		// Add refund payment note.
		$this->add_refund_payment_note( $edd_payment, $payment->get_id(), $amount, $refund_reference );
	}


	/**
	 * Maybe update refunded payment.
	 *
	 * @param Payment $payment Payment.
	 * @return void
	 */
	public function maybe_update_refunded_payment( Payment $payment ) {
		// Check refunded amount.
		$refunded_amount = $payment->get_refunded_amount();

		if ( null === $refunded_amount ) {
			return;
		}

		$refunded_amount = $payment->get_refunded_amount()->get_value();

		// Check updated refund amount.
		$edd_payment = \edd_get_payment( $payment->get_transaction_id(), true );

		$edd_refunded_amount = $edd_payment->get_meta( '_pronamic_pay_amount_refunded', true );

		if ( $edd_refunded_amount === $refunded_amount ) {
			return;
		}

		$edd_payment->update_meta( '_pronamic_pay_amount_refunded', $refunded_amount, $edd_refunded_amount );

		// Update EDD payment status.
		$status = $refunded_amount < $payment->get_total_amount()->get_value() ? 'partially_refunded' : 'refunded';

		$edd_payment->update_status( $status );

		// Add refund payment note.
		$amount_difference = clone $payment->get_refunded_amount();

		$amount_difference->subtract( new Money( $edd_refunded_amount, $amount_difference->get_currency() ) );

		$this->add_refund_payment_note( $edd_payment, $payment->get_id(), $amount_difference );
	}

	/**
	 * Add refunded payment note.
	 *
	 * @param EDD_Payment $edd_payment Easy Digital Downloads payment.
	 * @param int         $payment_id  Payment ID.
	 * @param Money       $amount      Refunded amount.
	 * @param string      $reference   Gateway refund reference.
	 */
	private function add_refund_payment_note( EDD_Payment $edd_payment, $payment_id, Money $amount, $reference = null ) {
		$payment_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			\get_edit_post_link( (int) $payment_id ),
			sprintf(
			/* translators: %s: payment id */
				esc_html( __( 'payment #%s', 'pronamic_ideal' ) ),
				$payment_id
			)
		);

		$note = \sprintf(
			/* translators: 1: refunded amount, 2: edit payment anchor */
			__( 'Refunded %1$s for %2$s.', 'pronamic_ideal' ),
			$amount->format_i18n(),
			$payment_link
		);

		if ( null !== $reference ) {
			$note = \sprintf(
				/* translators: 1: refunded amount, 2: edit payment anchor, 3: gateway refund reference */
				__( 'Refunded %1$s for %2$s (gateway reference `%3$s`).', 'pronamic_ideal' ),
				$amount->format_i18n(),
				$payment_link,
				$reference
			);
		}

		\edd_insert_payment_note( $edd_payment->ID, $note );
	}
}
