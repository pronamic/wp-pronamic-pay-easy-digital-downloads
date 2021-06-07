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
		// @todo also check payment source.
		$payment = \get_pronamic_payment_by_meta( '_pronamic_payment_source_id', $edd_payment->ID );

		if ( null === $payment ) {
			return;
		}

		// Process refund.
		try {
			$this->process_refund( $edd_payment, $payment );
		} catch ( \Exception $e ) {
			// @todo add admin notice with error message.
			// return new \WP_Error( 'pronamic-pay-easy-digital-downloads-refund', $e->getMessage() );
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

		// Refund amount.
		$payment_amount = $edd_payment->get_meta( '_edd_payment_total', true );

		$amount = new Money( $payment_amount, $payment->get_total_amount()->get_currency() );

		// Create refund.
		Plugin::create_refund( $transaction_id, $gateway, $amount );
	}
}
