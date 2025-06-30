<?php
/**
 * Easy Digital Downloads refunds
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads
 */

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Number\Number;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Refunds\Refund;

/**
 * Easy Digital Downloads refunds
 *
 * @author  Reüel van der Steege
 * @version 2.2.0
 * @since   2.2.0
 */
class RefundsManager {
	/**
	 * Setup refunds manager.
	 *
	 * @return void
	 */
	public function setup() {
		add_action(
			'edd_after_submit_refund_table',
			function ( \EDD\Orders\Order $order ) {
				if ( ! \str_starts_with( $order->gateway, 'pronamic_' ) ) {
					return;
				}

				$config_id = EasyDigitalDownloads::get_pronamic_config_id( $order->gateway );

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
		);

		add_action(
			'edd_refund_order',
			function ( $order_id, $refund_id, $all_refunded ) {
				if ( ! \current_user_can( 'edit_shop_payments', $order_id ) ) {
					return;
				}

				if ( empty( $_POST['data'] ) ) {
					return;
				}

				$edd_order = \edd_get_order( $order_id );

				if ( empty( $edd_order->gateway ) || ! \str_starts_with( $edd_order->gateway, 'pronamic_' ) ) {
					return;
				}

				// Get our data out of the serialized string.
				parse_str( $_POST['data'], $form_data );

				if ( empty( $form_data['edd-pronamic-pay-refund'] ) ) {
					// Checkbox was not checked.
					return;
				}

				$edd_refund = \edd_get_order( $refund_id );

				if ( empty( $edd_refund->total ) ) {
					return;
				}

				$pronamic_payment = \get_pronamic_payment_by_transaction_id( $edd_order->get_transaction_id() );

				if ( null === $pronamic_payment ) {
					return;
				}

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
			},
			10,
			3
		);
	}
}
