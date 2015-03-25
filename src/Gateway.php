<?php

/**
 * Title: Easy Digital Downloads gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2015
 * Company: Pronamic
 * @author Remco Tolsma
 * @version 1.1.0
 * @since 1.1.0
 */
class Pronamic_WP_Pay_Extensions_EDD_Gateway {
	/**
	 * ID
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Admin label
	 *
	 * @var string
	 */
	private $admin_label;

	/**
	 * Checkout label
	 *
	 * @var string
	 */
	private $checkout_label;

	/**
	 * Supports
	 *
	 * @var array
	 */
	private $supports;

	//////////////////////////////////////////////////

	/**
	 * Bootstrap
	 */
	public function __construct( $args ) {
		$args = wp_parse_args( $args, array(
			'id'             => '',
			'admin_label'    => '',
			'checkout_label' => '',
			'supports'       => array(),
			'payment_method' => null,
		) );

		$this->id             = $args['id'];
		$this->admin_label    = $args['admin_label'];
		$this->checkout_label = $args['checkout_label'];
		$this->supports       = $args['supports'];
		$this->payment_method = $args['payment_method'];

		// Settings
		$checkout_label = edd_get_option( $this->id . '_checkout_label' );
		if ( ! empty( $checkout_label ) ) {
			$this->checkout_label = $checkout_label;
		}

		// Actions

		// Pronamic iDEAL Remove CC Form
		// @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/1.9.4/includes/checkout/template.php#L97
		// @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/1.9.4/includes/gateways/paypal-standard.php#L12
		add_action( 'edd_' . $this->id . '_cc_form', array( $this, 'payment_fields' ) );

		add_action( 'edd_gateway_' . $this->id, array( $this, 'process_purchase' ) );

		// Filters
		add_filter( 'edd_settings_gateways', array( $this, 'settings_gateways' ) );
		add_filter( 'edd_payment_gateways' , array( $this, 'payment_gateways' ) );

		add_filter( 'edd_get_payment_transaction_id-' . $this->id, array( $this, 'get_payment_transaction_id' ) );
	}

	//////////////////////////////////////////////////

	/**
	 * Add the gateway to Easy Digital Downloads
	 *
	 * @param mixed $gateways
	 *
	 * @return mixed $gateways
	 */
	public function payment_gateways( $gateways ) {
		$gateways[ $this->id ] = array(
			'admin_label'    => $this->admin_label,
			'checkout_label' => $this->checkout_label,
			'supports'       => $this->supports,
		);

		return $gateways;
	}

	//////////////////////////////////////////////////

	/**
	 * Add the iDEAL configuration settings to the Easy Digital Downloads payment gateways settings page.
	 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/admin/settings/register-settings.php#L126
	 *
	 * @param mixed $settings_gateways
	 * @return mixed $settings_gateways
	 */
	public function settings_gateways( $settings_gateways ) {
		$settings_gateways[ $this->id ] = array(
			'id'   => $this->id,
			'name' => '<strong>' . sprintf( __( '%s Settings', 'pronamic_ideal' ), $this->admin_label ) . '</strong>',
			'desc' => sprintf( __( 'Configure the %s settings', 'pronamic_ideal' ), $this->admin_label ),
			'type' => 'header',
		);

		$settings_gateways[ $this->id . '_config_id' ] = array(
			'id'      => $this->id . '_config_id',
			'name'    => __( 'Gateway Configuration', 'pronamic_ideal' ),
			'type'    => 'select',
			'options' => Pronamic_WP_Pay_Plugin::get_config_select_options( $this->payment_method ),
		);

		$settings_gateways[ $this->id . '_checkout_label' ] = array(
			'id'      => $this->id . '_checkout_label',
			'name'    => __( 'Checkout Label', 'pronamic_ideal' ),
			'type'    => 'text',
			'std'     => $this->checkout_label,
		);

		return $settings_gateways;
	}

	//////////////////////////////////////////////////

	/**
	 * Payment fields for this gateway
	 *
	 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/1.9.4/includes/checkout/template.php#L167
	 */
	public function payment_fields() {
		$gateway = Pronamic_WP_Pay_Plugin::get_gateway( edd_get_option( $this->id . '_config_id' ) );

		if ( $gateway ) {
			$input = $gateway->get_input_html();

			if ( $input ) {
				echo '<fieldset id="edd_cc_fields" class="edd-do-validate">';
				echo '<span><legend>', esc_html( $this->checkout_label ), '</legend></span>';
				// @codingStandardsIgnoreStart
				echo $input;
				// @codingStandardsIgnoreEnd
				echo '</fieldset>';
			}
		}
	}

	//////////////////////////////////////////////////

	/**
	 * The $purchase_data array consists of the following data:
	 *
	 * $purchase_data = array(
	 *   'downloads'    => array of download IDs,
	 *   'tax'          => taxed amount on shopping cart
	 *   'subtotal'     => total price before tax
	 *   'price'        => total price of cart contents after taxes,
	 *   'purchase_key' => Random key
	 *   'user_email'   => $user_email,
	 *   'date'         => date( 'Y-m-d H:i:s' ),
	 *   'user_id'      => $user_id,
	 *   'post_data'    => $_POST,
	 *   'user_info'    => array of user's information and used discount code
	 *   'cart_details' => array of cart details,
	 * );
	 */
	public function process_purchase( $purchase_data ) {
		$config_id = edd_get_option( $this->id . '_config_id' );

		// Collect payment data
		$payment_data = array(
			'price'        => $purchase_data['price'],
			'date'         => $purchase_data['date'],
			'user_email'   => $purchase_data['user_email'],
			'purchase_key' => $purchase_data['purchase_key'],
			'currency'     => edd_get_currency(),
			'downloads'    => $purchase_data['downloads'],
			'user_info'    => $purchase_data['user_info'],
			'cart_details' => $purchase_data['cart_details'],
			'gateway'      => $this->id,
			'status'       => 'pending',
		);

		// Record the pending payment
		$payment_id = edd_insert_payment( $payment_data );

		// Check payment
		if ( ! $payment_id ) {
			// Log error
			edd_record_gateway_error( __( 'Payment Error', 'pronamic_ideal' ), sprintf( __( 'Payment creation failed before sending buyer to the payment provider. Payment data: %s', 'pronamic_ideal' ), json_encode( $payment_data ) ), $payment_id );

			edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
		} else {
			$data = new Pronamic_WP_Pay_Extensions_EDD_PaymentData( $payment_id, $payment_data );

			$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $config_id );

			if ( $gateway ) {
				// Start
				$payment = Pronamic_WP_Pay_Plugin::start( $config_id, $gateway, $data, $this->payment_method );

				$error = $gateway->get_error();

				if ( is_wp_error( $error ) ) {
					edd_record_gateway_error( __( 'Payment Error', 'pronamic_ideal' ), sprintf( __( 'Payment creation failed before sending buyer to the payment provider. Payment data: %s', 'pronamic_ideal' ), json_encode( $payment_data ) ), $payment_id );

					edd_set_error( 'pronamic_pay_error', Pronamic_WP_Pay_Plugin::get_default_error_message() );

					foreach ( $error->get_error_messages() as $i => $message ) {
						edd_set_error( 'pronamic_pay_error_' . $i, $message );
					}

					edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
				} else {
					// Transaction ID
					// @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.3/includes/payments/functions.php#L1400-L1416
					edd_set_payment_transaction_id( $payment_id, $payment->get_transaction_id() );

					// Payment note
					$payment_link = add_query_arg( array(
						'post'   => $payment->get_id(),
						'action' => 'edit',
					), admin_url( 'post.php' ) );

					$note = sprintf(
						__( 'Payment %s pending.', 'pronamic_ideal' ),
						sprintf( '<a href="%s">#%s</a>', $payment_link, $payment->get_id() )
					);

					edd_insert_payment_note( $payment_id, $note );

					// Redirect
					$gateway->redirect( $payment );

					exit;
				}
			}
		}
	}

	/**
	 * Get payment transaction ID
	 *
	 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.3/includes/payments/functions.php#L1378-L1398
	 *
	 * @param string $payment_id
	 */
	public function get_payment_transaction_id( $payment_id ) {
		return null;
	}
}
