<?php

/**
 * Title: Easy Digital Downloads Sofort gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2015
 * Company: Pronamic
 * @author Remco Tolsma
 * @version 1.1.0
 * @since 1.1.0
 */
class Pronamic_WP_Pay_Extensions_EDD_SofortGateway extends Pronamic_WP_Pay_Extensions_EDD_Gateway {
	/**
	 * Construct and initialize Mister Cash gateway
	 */
	public function __construct() {
		parent::__construct( array(
			'id'             => 'pronamic_pay_sofort',
			'admin_label'    => __( 'SOFORT Banking', 'pronamic_ideal' ),
			'checkout_label' => __( 'SOFORT Banking', 'pronamic_ideal' ),
			'payment_method' => Pronamic_WP_Pay_PaymentMethods::SOFORT,
		) );
	}
}
