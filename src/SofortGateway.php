<?php

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

/**
 * Title: Easy Digital Downloads Sofort gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.1.0
 */
class SofortGateway extends Gateway {
	/**
	 * Construct and initialize Mister Cash gateway
	 */
	public function __construct() {
		parent::__construct( array(
			'id'             => 'pronamic_pay_sofort',
			'admin_label'    => __( 'Pronamic - SOFORT Banking', 'pronamic_ideal' ),
			'checkout_label' => __( 'SOFORT Banking', 'pronamic_ideal' ),
			'payment_method' => \Pronamic\WordPress\Pay\Core\PaymentMethods::SOFORT,
		) );
	}
}
