<?php

declare(strict_types=1);

/**
 * Easy Digital Downloads refunds manager.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads
 */

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Number\Number;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Refunds\Refund;

/**
 * Easy Digital Downloads refunds.
 */
final class RefundsManager {
	/**
	 * Setup refunds manager.
	 *
	 * @return void
	 */
	public function setup() {
		\add_action( 'edd_after_submit_refund_table', $this->display_refund_checkbox(...) );
		\add_action( 'edd_refund_order', $this->maybe_refund_order_at_gateway(...), 10, 3 );
		\add_action( 'pronamic_pay_update_payment', $this->maybe_sync_gateway_refund(...), 15, 1 );
	}

	/**
	 * Display gateway refund checkbox in EDD refund form.
	 *
	 * @param \EDD\Orders\Order $edd_order EDD order.
	 * @return void
	 */
	public function display_refund_checkbox( \EDD\Orders\Order $edd_order ) {
		if ( ! $this->is_pronamic_gateway( (string) $edd_order->gateway ) ) {
			return;
		}

		$config_id = EasyDigitalDownloads::get_pronamic_config_id( (string) $edd_order->gateway );

		$gateway = Plugin::get_gateway( (int) $config_id );

		if ( null === $gateway || ! $gateway->supports( 'refunds' ) ) {
			return;
		}

		?>
		<div class="edd-form-group edd-pronamic-pay-refund-transaction">
			<div class="edd-form-group__control">
				<input type="checkbox" id="edd-pronamic-pay-refund" name="edd-pronamic-pay-refund" class="edd-form-group__input" value="1">
				<label for="edd-pronamic-pay-refund" class="edd-form-group__label">
					<?php

					echo \esc_html(
						\sprintf(
							\__( 'Refund transaction in %s', 'pronamic_ideal' ),
							\get_the_title( (int) $config_id )
						)
					);

					?>
				</label>
			</div>
		</div>
		<?php
	}

	/**
	 * Maybe refund at gateway after an EDD refund is created.
	 *
	 * @param int  $order_id     EDD order ID.
	 * @param int  $refund_id    EDD refund order ID.
	 * @param bool $all_refunded Entire order refunded.
	 * @return void
	 */
	public function maybe_refund_order_at_gateway( $order_id, $refund_id, $all_refunded ) {
		if ( ! \current_user_can( 'edit_shop_payments', $order_id ) ) {
			return;
		}

		if ( empty( $_POST['data'] ) ) {
			return;
		}

		$edd_order = \edd_get_order( $order_id );

		if ( false === $edd_order || ! $this->is_pronamic_gateway( (string) $edd_order->gateway ) ) {
			return;
		}

		$form_data_raw = \wp_unslash( $_POST['data'] );

		if ( ! \is_string( $form_data_raw ) ) {
			return;
		}

		\parse_str( $form_data_raw, $form_data );

		if ( empty( $form_data['edd-pronamic-pay-refund'] ) ) {
			return;
		}

		$transaction_id = $this->get_order_transaction_id( $edd_order );

		if ( null === $transaction_id ) {
			return;
		}

		$pronamic_payment = \get_pronamic_payment_by_transaction_id( $transaction_id );

		if ( null === $pronamic_payment ) {
			return;
		}

		$edd_refund = \edd_get_order( $refund_id );

		if ( false === $edd_refund ) {
			return;
		}

		$amount = new Money(
			Number::from_mixed( $edd_refund->total )->negative(),
			$pronamic_payment->get_total_amount()->get_currency()
		);

		$refund = new Refund( $pronamic_payment, $amount );

		Plugin::create_refund( $refund );

		$note = \sprintf(
			/* translators: 1: refunded amount, 2: gateway refund reference */
			\__( '%1$s refunded. Transaction ID: %2$s.', 'pronamic_ideal' ),
			$amount->format_i18n(),
			$refund->psp_id
		);

		foreach ( [ $order_id, $refund_id ] as $note_object_id ) {
			\edd_add_note(
				[
					'object_id'   => $note_object_id,
					'object_type' => 'order',
					'user_id'     => \is_admin() ? \get_current_user_id() : 0,
					'content'     => $note,
				]
			);
		}

		\edd_add_order_transaction(
			[
				'object_id'      => $refund_id,
				'object_type'    => 'order',
				'transaction_id' => $refund->psp_id,
				'gateway'        => $edd_order->gateway,
				'status'         => 'complete',
				'total'          => \edd_negate_amount( (float) $amount->get_value() ),
			]
		);
	}

	/**
	 * Maybe synchronize refunded amount from gateway updates to EDD order.
	 *
	 * @param Payment $payment Pronamic payment.
	 * @return void
	 */
	public function maybe_sync_gateway_refund( Payment $payment ) {
		$refunded_amount = $payment->get_refunded_amount();

		if ( $refunded_amount->get_value() <= 0 ) {
			return;
		}

		$transaction_id = $payment->get_transaction_id();

		if ( null === $transaction_id ) {
			return;
		}

		$order_id = \edd_get_order_id_from_transaction_id( $transaction_id );

		if ( $order_id <= 0 ) {
			return;
		}

		$edd_order = \edd_get_order( $order_id );

		if ( false === $edd_order || ! $this->is_pronamic_gateway( (string) $edd_order->gateway ) ) {
			return;
		}

		$edd_refunded_amount = $this->get_edd_refunded_amount( $edd_order, $refunded_amount->get_currency() );

		if ( $edd_refunded_amount->get_value() === $refunded_amount->get_value() ) {
			return;
		}

		$refunded_value = $refunded_amount->get_value();

		\edd_update_order(
			$order_id,
			[
				'status' => $refunded_value < $payment->get_total_amount()->get_value() ? 'partially_refunded' : 'refunded',
			]
		);

		$difference = $refunded_amount->subtract( $edd_refunded_amount );

		$this->add_order_note(
			$order_id,
			\sprintf(
				/* translators: 1: refunded amount total, 2: amount difference */
				\__( 'Refund amount synchronized to %1$s (difference %2$s).', 'pronamic_ideal' ),
				$refunded_amount->format_i18n(),
				$difference->format_i18n()
			)
		);
	}

	/**
	 * Get refunded amount currently registered in EDD for an order.
	 *
	 * @param \EDD\Orders\Order                      $edd_order EDD order.
	 * @param \Pronamic\WordPress\Money\Currency|string $currency  Currency.
	 * @return Money
	 */
	private function get_edd_refunded_amount( \EDD\Orders\Order $edd_order, $currency ) {
		$order_total = (float) $edd_order->total;
		$current_total = (float) \edd_get_order_total( $edd_order->id );
		$refunded_value = $order_total - $current_total;

		if ( $refunded_value < 0 ) {
			$refunded_value = 0;
		}

		return new Money( $refunded_value, $currency );
	}

	/**
	 * Resolve transaction ID for an EDD order.
	 *
	 * @param \EDD\Orders\Order $edd_order EDD order.
	 * @return string|null
	 */
	private function get_order_transaction_id( \EDD\Orders\Order $edd_order ) {
		$transaction_id = $edd_order->get_transaction_id();

		if ( \is_scalar( $transaction_id ) && '' !== (string) $transaction_id ) {
			return (string) $transaction_id;
		}

		$transactions = \edd_get_order_transactions(
			[
				'object_id'   => $edd_order->id,
				'object_type' => 'order',
				'number'      => 1,
				'status'      => 'complete',
			]
		);

		if ( ! \is_array( $transactions ) || [] === $transactions ) {
			return null;
		}

		$transaction = \reset( $transactions );

		if ( ! isset( $transaction->transaction_id ) || empty( $transaction->transaction_id ) ) {
			return null;
		}

		return (string) $transaction->transaction_id;
	}

	/**
	 * Add note to EDD order.
	 *
	 * @param int    $order_id EDD order ID.
	 * @param string $note     Note message.
	 * @return void
	 */
	private function add_order_note( $order_id, $note ) {
		\edd_add_note(
			[
				'object_id'   => $order_id,
				'object_type' => 'order',
				'user_id'     => \is_admin() ? \get_current_user_id() : 0,
				'content'     => $note,
			]
		);
	}

	/**
	 * Check if this is a Pronamic gateway.
	 *
	 * @param string $gateway Gateway.
	 * @return bool
	 */
	private function is_pronamic_gateway( $gateway ) {
		return \str_starts_with( $gateway, 'pronamic_' );
	}
}
