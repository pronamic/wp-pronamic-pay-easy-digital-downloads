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
 * @version 2.0.0
 * @since  2.0.0
 */
class GuldenGateway extends Gateway {
	/**
	 * Construct and initialize Gulden gateway.
	 */
	public function __construct() {
		parent::__construct( array(
			'id'             => 'pronamic_pay_gulden',
			'admin_label'    => PaymentMethods::get_name( PaymentMethods::GULDEN ),
			'checkout_label' => PaymentMethods::get_name( PaymentMethods::GULDEN ),
			'payment_method' => PaymentMethods::GULDEN,
		) );
	}
}
