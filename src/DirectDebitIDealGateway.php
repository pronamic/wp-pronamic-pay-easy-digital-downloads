<?php

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

/**
 * Title: Easy Digital Downloads Direct Debit mandate via iDEAL gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.1.0
 */
class DirectDebitIDealGateway extends Gateway {
	/**
	 * Construct and initialize Credit Card gateway
	 */
	public function __construct() {
		parent::__construct(
			array(
				'id'             => 'pronamic_pay_direct_debit_ideal',
				'admin_label'    => sprintf(
					/* translators: 1: Gateway admin label prefix, 2: Gateway admin label */
					 __( '%1$s - %2$s', 'pronamic_ideal' ),
					__( 'Pronamic', 'pronamic_ideal' ),
					sprintf(
						/* translators: %s: payment method */
						 __( 'Direct Debit (mandate via %s)', 'pronamic_ideal' ),
						__( 'iDEAL', 'pronamic_ideal' )
					)
				),
				'checkout_label' => __( 'Direct Debit (mandate via iDEAL)', 'pronamic_ideal' ),
				'payment_method' => \Pronamic\WordPress\Pay\Core\PaymentMethods::DIRECT_DEBIT_IDEAL,
			)
		);
	}
}
