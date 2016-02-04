<?php

/**
 * Title: Easy Digital Downloads Credit Card gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.1.0
 * @since 1.1.0
 */
class Pronamic_WP_Pay_Extensions_EDD_CreditCardGateway extends Pronamic_WP_Pay_Extensions_EDD_Gateway {
	/**
	 * Construct and initialize Credit Card gateway
	 */
	public function __construct() {
		parent::__construct( array(
			'id'             => 'pronamic_pay_credit_card',
			'admin_label'    => __( 'Credit Card', 'pronamic_ideal' ),
			'checkout_label' => __( 'Credit Card', 'pronamic_ideal' ),
			'payment_method' => Pronamic_WP_Pay_PaymentMethods::CREDIT_CARD,
		) );
	}
}
