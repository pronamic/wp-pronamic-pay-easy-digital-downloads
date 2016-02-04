<?php

/**
 * Title: Easy Digital Downloads iDEAL gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 * @author Remco Tolsma
 * @version 1.1.0
 * @since 1.1.0
 */
class Pronamic_WP_Pay_Extensions_EDD_IDealGateway extends Pronamic_WP_Pay_Extensions_EDD_Gateway {
	/**
	 * Construct and initialize iDEAL gateway
	 */
	public function __construct() {
		parent::__construct( array(
			'id'             => 'pronamic_pay_ideal',
			'admin_label'    => __( 'iDEAL', 'pronamic_ideal' ),
			'checkout_label' => __( 'iDEAL', 'pronamic_ideal' ),
			'payment_method' => Pronamic_WP_Pay_PaymentMethods::IDEAL,
		) );
	}
}
