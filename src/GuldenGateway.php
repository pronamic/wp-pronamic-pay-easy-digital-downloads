<?php

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Title: Easy Digital Downloads Gulden gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Reüel van der Steege
 * @version 2.0.1
 * @since  2.0.0
 */
class GuldenGateway extends Gateway {
	/**
	 * Construct and initialize Gulden gateway.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'id'             => 'pronamic_pay_gulden',
				'admin_label'    => sprintf(
					/* translators: 1: Gateway admin label prefix, 2: Gateway admin label */
					__( '%1$s - %2$s', 'pronamic_ideal' ),
					__( 'Pronamic', 'pronamic_ideal' ),
					__( 'Gulden', 'pronamic_ideal' )
				),
				'checkout_label' => __( 'Gulden', 'pronamic_ideal' ),
				'payment_method' => PaymentMethods::GULDEN,
			)
		);
	}
}
