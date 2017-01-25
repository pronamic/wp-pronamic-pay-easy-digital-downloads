<?php

/**
 * Title: Easy Digital Downloads Credit Card gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.1.0
 * @since 1.1.0
 */
class Pronamic_WP_Pay_Extensions_EDD_DirectDebitGateway extends Pronamic_WP_Pay_Extensions_EDD_Gateway {
	/**
	 * Construct and initialize Credit Card gateway
	 */
	public function __construct() {
		parent::__construct( array(
			'id'             => 'pronamic_pay_direct_debit',
			'admin_label'    => __( 'Direct Debit', 'pronamic_ideal' ),
			'checkout_label' => __( 'Direct Debit', 'pronamic_ideal' ),
			'payment_method' => Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT,
		) );
	}
}
