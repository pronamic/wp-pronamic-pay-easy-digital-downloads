<?php
/**
 * Easy Digital Downloads refunds manager
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2025 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads
 */

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Number\Number;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Refunds\Refund;

/**
 * Easy Digital Downloads refunds manager class
 */
class RefundsManager {
	/**
	 * Setup refunds manager.
	 *
	 * @return void
	 */
	public function setup() {
		\add_action( 'edd_after_submit_refund_table', $this->edd_after_submit_refund_table( ... ) );

		\add_action( 'edd_refund_order', $this->edd_refund_order( ... ), 10, 2 );
	}

	/**
	 * After submit refund table.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/issues/6#issuecomment-3019177617
	 * @link https://easydigitaldownloads.com/development/2021/05/20/edd-3-0-beta2/
	 * @link https://github.com/awesomemotive/easy-digital-downloads/blob/0b7230238f652eeb3905b92c8b4fd76d5a27df89/includes/gateways/paypal-standard.php#L1137-L1170
	 * @param \EDD\Orders\Order $order The order.
	 * @return void
	 */
	private function edd_after_submit_refund_table( \EDD\Orders\Order $edd_order ) {
		if ( ! \str_starts_with( $edd_order->gateway, 'pronamic_' ) ) {
			return;
		}

		$config_id = EasyDigitalDownloads::get_pronamic_config_id( $edd_order->gateway );

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
							\get_the_title( $config_id )
						)
					);

					?>
				</label>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle Easy Digital Downloads refund order.
	 *
	 * @param int $order_id The order ID.
	 * @param int $refund_id The refund ID.
	 * @return void
	 */
	private function edd_refund_order( $order_id, $refund_id ) {
		if ( ! \current_user_can( 'edit_shop_payments', $order_id ) ) {
			return;
		}

		if ( empty( $_POST['data'] ) ) {
			return;
		}

		$edd_order = \edd_get_order( $order_id );

		if ( ! \str_starts_with( $edd_order->gateway, 'pronamic_' ) ) {
			return;
		}

		\parse_str( $_POST['data'], $form_data );

		if ( empty( $form_data['edd-pronamic-pay-refund'] ) ) {
			return;
		}

		$pronamic_payment = \get_pronamic_payment_by_transaction_id( $edd_order->get_transaction_id() );

		if ( null === $pronamic_payment ) {
			return;
		}

		$edd_refund = \edd_get_order( $refund_id );

		$pronamic_amount = new Money(
			Number::from_mixed( $edd_refund->total )->negative(),
			$pronamic_payment->get_total_amount()->get_currency()
		);

		$pronamic_refund = new Refund( $pronamic_payment, $pronamic_amount );

		Plugin::create_refund( $pronamic_refund );

		$note_object_ids = [
			$edd_order->id,
			$edd_refund->id,
		];

		$note_message = \sprintf(
			\__( '%1$s refunded. Transaction ID: %2$s', 'pronamic_ideal' ),
			$pronamic_amount->format_i18n(),
			$pronamic_refund->psp_id
		);

		foreach ( $note_object_ids as $note_object_id ) {
			\edd_add_note(
				[
					'object_id'   => $note_object_id,
					'object_type' => 'order',
					'user_id'     => is_admin() ? get_current_user_id() : 0,
					'content'     => $note_message
				]
			);
		}

		\edd_add_order_transaction(
			[
				'object_id'      => $edd_refund->id,
				'object_type'    => 'order',
				'transaction_id' => $pronamic_refund->psp_id,
				'gateway'        => $edd_order->gateway,
				'status'         => 'complete',
				'total'          => $pronamic_amount->get_value(),
			]
		);
	}
}
