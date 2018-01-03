<?php

/**
 * Title: Easy Digital Downloads Direct Debit mandate via iDEAL gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.1.0
 * @since 1.1.0
 */
class Pronamic_WP_Pay_Extensions_EDD_DirectDebitIDealGateway extends Pronamic_WP_Pay_Extensions_EDD_Gateway {
	/**
	 * Construct and initialize Credit Card gateway
	 */
	public function __construct() {
		parent::__construct( array(
			'id'             => 'pronamic_pay_direct_debit_ideal',
			'admin_label'    => __( 'Direct Debit (mandate via iDEAL)', 'pronamic_ideal' ),
			'checkout_label' => __( 'Direct Debit (mandate via iDEAL)', 'pronamic_ideal' ),
			'payment_method' => Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT_IDEAL,
		) );
	}
}
