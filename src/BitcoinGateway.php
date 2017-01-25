<?php

/**
 * Title: Easy Digital Downloads Bitcoin gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.2.6
 * @since 1.2.6
 */
class Pronamic_WP_Pay_Extensions_EDD_BitcoinGateway extends Pronamic_WP_Pay_Extensions_EDD_Gateway {
	/**
	 * Construct and initialize Credit Card gateway
	 */
	public function __construct() {
		parent::__construct( array(
			'id'             => 'pronamic_pay_bitcoin',
			'admin_label'    => __( 'Bitcoin', 'pronamic_ideal' ),
			'checkout_label' => __( 'Bitcoin', 'pronamic_ideal' ),
			'payment_method' => Pronamic_WP_Pay_PaymentMethods::BITCOIN,
		) );
	}
}
