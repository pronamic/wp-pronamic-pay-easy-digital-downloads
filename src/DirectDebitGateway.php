<?php

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

/**
 * Title: Easy Digital Downloads Direct Debit gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.1.0
 * @since 1.1.0
 */
class DirectDebitGateway extends Gateway {
	/**
	 * Construct and initialize Credit Card gateway
	 */
	public function __construct() {
		parent::__construct( array(
			'id'             => 'pronamic_pay_direct_debit',
			'admin_label'    => __( 'Direct Debit', 'pronamic_ideal' ),
			'checkout_label' => __( 'Direct Debit', 'pronamic_ideal' ),
			'payment_method' => \Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT,
		) );
	}
}
