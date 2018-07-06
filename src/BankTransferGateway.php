<?php

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

/**
 * Title: Easy Digital Downloads Bank Transfer gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.2.6
 */
class BankTransferGateway extends Gateway {
	/**
	 * Construct and initialize Credit Card gateway
	 */
	public function __construct() {
		parent::__construct( array(
			'id'             => 'pronamic_pay_bank_transfer',
			'admin_label'    => __( 'Pronamic - Bank Transfer', 'pronamic_ideal' ),
			'checkout_label' => __( 'Bank Transfer', 'pronamic_ideal' ),
			'payment_method' => \Pronamic\WordPress\Pay\Core\PaymentMethods::BANK_TRANSFER,
		) );
	}
}
