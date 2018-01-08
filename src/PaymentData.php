<?php

/**
 * Title: Easy Digital Downloads payment data
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.2.7
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_EDD_PaymentData extends Pronamic_WP_Pay_PaymentData {
	/**
	 * Payment ID
	 *
	 * @var int
	 */
	private $payment_id;

	/**
	 * Payment data
	 *
	 * @var mixed
	 */
	private $payment_data;

	/**
	 * Description
	 *
	 * @var string
	 */
	public $description;

	//////////////////////////////////////////////////

	/**
	 * Constructs and initializes an Easy Digital Downloads iDEAL data proxy
	 *
	 * @param int   $payment_id
	 * @param mixed $payment_data
	 */
	public function __construct( $payment_id, $payment_data ) {
		parent::__construct();

		$this->payment_id   = $payment_id;
		$this->payment_data = $payment_data;
	}

	//////////////////////////////////////////////////

	/**
	 * Get source ID
	 *
	 * @return int $source_id
	 */
	public function get_source_id() {
		return $this->payment_id;
	}

	/**
	 * Get source indicator
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_source()
	 * @return string
	 */
	public function get_source() {
		return 'easydigitaldownloads';
	}

	//////////////////////////////////////////////////

	public function get_title() {
		return sprintf( __( 'Easy Digital Downloads order %s', 'pronamic_ideal' ), $this->get_order_id() );
	}

	/**
	 * Get description
	 *
	 * @return string
	 */
	public function get_description() {
		if ( empty( $this->description ) ) {
			$this->description = '{edd_cart_details_name}';
		}

		// Name
		$edd_cart_details_name = '';

		if ( is_array( $this->payment_data['cart_details'] ) ) {
			$names = wp_list_pluck( $this->payment_data['cart_details'], 'name' );

			$edd_cart_details_name = implode( ', ', $names );
		}

		// Replace pairs
		$replace_pairs = array(
			'{edd_cart_details_name}' => $edd_cart_details_name,
			'{edd_payment_id}'        => $this->get_order_id(),
		);

		// Replace
		$description = strtr( $this->description, $replace_pairs );

		return $description;
	}

	/**
	 * Get order ID
	 *
	 * @return string
	 */
	public function get_order_id() {
		/*
		 * Check if the 'edd_get_payment_number' function exists, it was added in Easy Digital Downloads version 2.0.
		 *
		 * @since 1.2.0
		 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.4.3/includes/payments/functions.php#L1178-L1204
		 */
		if ( function_exists( 'edd_get_payment_number' ) ) {
			return edd_get_payment_number( $this->payment_id );
		} else {
			return $this->payment_id;
		}
	}

	/**
	 * Get items
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_items()
	 * @return Pronamic_IDeal_Items
	 */
	public function get_items() {
		// Items
		$items = new Pronamic_IDeal_Items();

		// Item
		// We only add one total item, because iDEAL cant work with negative price items (discount)
		$item = new Pronamic_IDeal_Item();
		$item->setNumber( $this->payment_id );
		$item->setDescription( $this->get_description() );
		$item->setPrice( $this->payment_data['price'] );
		$item->setQuantity( 1 );

		$items->addItem( $item );

		return $items;
	}

	//////////////////////////////////////////////////

	/**
	 * Get currency
	 *
	 * @return string
	 */
	public function get_currency_alphabetic_code() {
		return edd_get_option( 'currency' );
	}

	//////////////////////////////////////////////////

	public function get_email() {
		return $this->payment_data['user_email'];
	}

	public function get_first_name() {
		if ( is_array( $this->payment_data['user_info'] ) ) {
			if ( isset( $this->payment_data['user_info']['first_name'] ) ) {
				return $this->payment_data['user_info']['first_name'];
			}
		}
	}

	public function get_last_name() {
		if ( is_array( $this->payment_data['user_info'] ) ) {
			if ( isset( $this->payment_data['user_info']['last_name'] ) ) {
				return $this->payment_data['user_info']['last_name'];
			}
		}
	}

	public function get_customer_name() {
		$name = '';

		if ( is_array( $this->payment_data['user_info'] ) ) {
			if ( isset( $this->payment_data['user_info']['first_name'] ) ) {
				$name .= $this->payment_data['user_info']['first_name'];

				if ( isset( $this->payment_data['user_info']['last_name'] ) ) {
					$name .= ' ' . $this->payment_data['user_info']['last_name'];
				}
			}
		}

		return $name;
	}

	public function get_address() {
		return '';
	}

	public function get_city() {
		return '';
	}

	public function get_zip() {
		return '';
	}

	//////////////////////////////////////////////////

	public function get_normal_return_url() {
		return home_url();
	}

	public function get_cancel_url() {
		$page_id = edd_get_option( 'failure_page' );

		if ( is_numeric( $page_id ) ) {
			return get_permalink( $page_id );
		}

		return home_url();
	}

	public function get_success_url() {
		$page_id = edd_get_option( 'success_page' );

		if ( is_numeric( $page_id ) ) {
			return get_permalink( $page_id );
		}

		return home_url();
	}

	public function get_error_url() {
		$page_id = edd_get_option( 'failure_page' );

		if ( is_numeric( $page_id ) ) {
			return get_permalink( $page_id );
		}

		return home_url();
	}
}
