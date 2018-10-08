<?php

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

/**
 * Title: Easy Digital Downloads iDEAL gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 2.0.1
 * @since 1.1.0
 */
class IDealGateway extends Gateway {
	/**
	 * Construct and initialize iDEAL gateway
	 */
	public function __construct() {
		parent::__construct(
			array(
				'id'             => 'pronamic_pay_ideal',
				'admin_label'    => sprintf(
					/* translators: 1: Gateway admin label prefix, 2: Gateway admin label */
					__( '%1$s - %2$s', 'pronamic_ideal' ),
					__( 'Pronamic', 'pronamic_ideal' ),
					__( 'iDEAL', 'pronamic_ideal' )
				),
				'checkout_label' => __( 'iDEAL', 'pronamic_ideal' ),
				'payment_method' => \Pronamic\WordPress\Pay\Core\PaymentMethods::IDEAL,
			)
		);
	}
}
