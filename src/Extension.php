<?php

/**
 * Title: Easy Digital Downloads iDEAL Add-On
 * Description:
 * Copyright: Copyright (c) 2005 - 2015
 * Company: Pronamic
 * @author Remco Tolsma
 * @version 1.2.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_EDD_Extension {
	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		// The "plugins_loaded" is one of the earliest hooks after EDD is set up
		add_action( 'plugins_loaded', array( __CLASS__, 'plugins_loaded' ) );
	}

	//////////////////////////////////////////////////

	/**
	 * Test to see if the Easy Digital Downloads plugin is active, then add all actions.
	 */
	public static function plugins_loaded() {
		if ( Pronamic_WP_Pay_Extensions_EDD_EasyDigitalDownloads::is_active() ) {
			// Gateways
			// @since 1.1.0
			new Pronamic_WP_Pay_Extensions_EDD_Gateway( array(
				'id'             => 'pronamic_ideal',
				'admin_label'    => __( 'Pronamic', 'pronamic_ideal' ),
				'checkout_label' => __( 'iDEAL', 'pronamic_ideal' ),
			) );

			new Pronamic_WP_Pay_Extensions_EDD_CreditCardGateway();
			new Pronamic_WP_Pay_Extensions_EDD_DirectDebitGateway();
			new Pronamic_WP_Pay_Extensions_EDD_IDealGateway();
			new Pronamic_WP_Pay_Extensions_EDD_MisterCashGateway();
			new Pronamic_WP_Pay_Extensions_EDD_SofortGateway();

			add_action( 'pronamic_payment_status_update_easydigitaldownloads', array( __CLASS__, 'status_update' ), 10, 2 );
			add_filter( 'pronamic_payment_source_text_easydigitaldownloads', array( __CLASS__, 'source_text' ), 10, 2 );

			// Icons
			add_filter( 'edd_accepted_payment_icons', array( __CLASS__, 'accepted_payment_icons' ) );
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Update the status of the specified payment
	 *
	 * @param Pronamic_Pay_Payment $payment
	 * @param boolean			  $can_redirect (optional, defaults to false)
	 */
	public static function status_update( Pronamic_Pay_Payment $payment, $can_redirect = false ) {
		$source_id = $payment->get_source_id();

		$data = new Pronamic_WP_Pay_Extensions_EDD_PaymentData( $source_id, array() );

		// Only update if order is not completed
		$should_update = edd_get_payment_status( $source_id ) !== Pronamic_WP_Pay_Extensions_EDD_EasyDigitalDownloads::ORDER_STATUS_PUBLISH;

		// Defaults
		$status = null;
		$note   = null;
		$url	= $data->get_normal_return_url();

		$status = $payment->get_status();

		switch ( $status ) {
			case Pronamic_WP_Pay_Statuses::CANCELLED :
				$url = $data->get_cancel_url();

				break;
			case Pronamic_WP_Pay_Statuses::EXPIRED :
				if ( $should_update ) {
					edd_update_payment_status( $source_id, Pronamic_WP_Pay_Extensions_EDD_EasyDigitalDownloads::ORDER_STATUS_ABANDONED );
				}

				$url = $data->get_error_url();

				break;
			case Pronamic_WP_Pay_Statuses::FAILURE :
				if ( $should_update ) {
					edd_update_payment_status( $source_id, Pronamic_WP_Pay_Extensions_EDD_EasyDigitalDownloads::ORDER_STATUS_FAILED );
				}

				$url = $data->get_error_url();

				break;
			case Pronamic_WP_Pay_Statuses::SUCCESS :
				if ( $should_update ) {
					edd_insert_payment_note( $source_id, __( 'Payment completed.', 'pronamic_ideal' ) );

					/*
					 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/admin/payments/view-order-details.php#L36
					 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/admin/payments/view-order-details.php#L199-L206
					 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/payments/functions.php#L1312-L1332
					 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/gateways/paypal-standard.php#L555-L576
					 */
				}

				edd_update_payment_status( $source_id, Pronamic_WP_Pay_Extensions_EDD_EasyDigitalDownloads::ORDER_STATUS_PUBLISH );

				edd_empty_cart();

				$url = $data->get_success_url();

				break;
			case Pronamic_WP_Pay_Statuses::OPEN :
				if ( $should_update ) {
					edd_insert_payment_note( $source_id, __( 'Payment open.', 'pronamic_ideal' ) );
				}

				break;
			default:
				if ( $should_update ) {
					edd_insert_payment_note( $source_id, __( 'Payment unknown.', 'pronamic_ideal' ) );
				}

				break;
		}

		if ( $can_redirect ) {
			wp_redirect( $url );

			exit;
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Source column
	 *
	 * @param string				  $text
	 * @param Pronamic_WP_Pay_Payment $payment
	 *
	 * @return string $text
	 */
	public static function source_text( $text, Pronamic_WP_Pay_Payment $payment ) {
		$text  = '';

		$text .= __( 'Easy Digital Downloads', 'pronamic_ideal' ) . '<br />';

		$text .= sprintf(
			'<a href="%s">%s</a>',
			get_edit_post_link( $payment->source_id ),
			sprintf( __( 'Payment %s', 'pronamic_ideal' ), $payment->source_id )
		);

		return $text;
	}

	//////////////////////////////////////////////////

	/**
	 * Accepted payment icons
	 *
	 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.1.3/includes/admin/settings/register-settings.php#L261-L268
	 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.1.3/includes/checkout/template.php#L573-L609
	 *
	 * @param array $icons
	 * @return array
	 */
	public static function accepted_payment_icons( $icons ) {
		// iDEAL
		$key = plugins_url( 'images/ideal/icon-64x48.png', Pronamic_WP_Pay_Plugin::$file );

		$icons[ $key ] = __( 'iDEAL', 'pronamic_ideal' );

		// Bancontact/Mister Cash
		$key = plugins_url( 'images/mister-cash/icon-64x48.png', Pronamic_WP_Pay_Plugin::$file );

		$icons[ $key ] = __( 'Bancontact/Mister Cash', 'pronamic_ideal' );

		// Return
		return $icons;
	}
}
