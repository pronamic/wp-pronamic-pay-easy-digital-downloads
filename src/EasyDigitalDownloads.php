<?php

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

/**
 * Title: Easy Digital Downloads
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.0.0
 */
class EasyDigitalDownloads {
	/**
	 * Order status pending
	 *
	 * @var string
	 */
	const ORDER_STATUS_PENDING = 'pending';

	/**
	 * Order status completed
	 *
	 * @var string
	 */
	const ORDER_STATUS_PUBLISH = 'publish';

	/**
	 * Order status refunded
	 *
	 * @var string
	 */
	const ORDER_STATUS_REFUNDED = 'refunded';

	/**
	 * Order status failed
	 *
	 * @var string
	 */
	const ORDER_STATUS_FAILED = 'failed';

	/**
	 * Order status abandoned
	 *
	 * @var string
	 */
	const ORDER_STATUS_ABANDONED = 'abandoned';

	/**
	 * Order status revoked/cancelled
	 *
	 * @var string
	 */
	const ORDER_STATUS_REVOKED = 'revoked';

	/**
	 * Check if Easy Digital Downloads is active
	 *
	 * @return boolean
	 */
	public static function is_active() {
		return defined( 'EDD_VERSION' );
	}

	/**
	 * Get payment URL by the specified payment ID.]
	 *
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/3.0.0-beta2/includes/admin/payments/class-payments-table.php#L443
	 *
	 * @param int $payment_id Payment ID.
	 * @return string
	 */
	public static function get_payment_url( $payment_id ) {
		if ( version_compare( EDD_VERSION, '3', '<' ) ) {
			return get_edit_post_link( $payment_id );
		}

		return add_query_arg(
			array(
				'id'        => $payment_id,
				'post_type' => 'download',
				'page'      => 'edd-payment-history',
				'view'      => 'view-order-details',
			),
			admin_url( 'edit.php' )
		);
	}
}
