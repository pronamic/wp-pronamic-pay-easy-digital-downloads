<?php

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Core\Statuses as Core_Statuses;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: Easy Digital Downloads iDEAL Add-On
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.0.0
 */
class Extension {
	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		// The "plugins_loaded" is one of the earliest hooks after EDD is set up
		add_action( 'plugins_loaded', array( __CLASS__, 'plugins_loaded' ) );
	}

	/**
	 * Test to see if the Easy Digital Downloads plugin is active, then add all actions.
	 */
	public static function plugins_loaded() {
		if ( EasyDigitalDownloads::is_active() ) {
			/*
			 * Gateways
			 * @since 1.1.0
			 */
			new Gateway( array(
				'id'             => 'pronamic_ideal',
				'admin_label'    => __( 'Pronamic', 'pronamic_ideal' ),
				'checkout_label' => __( 'iDEAL', 'pronamic_ideal' ),
			) );

			new BankTransferGateway();
			new BitcoinGateway();
			new CreditCardGateway();
			new DirectDebitGateway();
			new IDealGateway();
			new BancontactGateway();
			new SofortGateway();

			if ( PaymentMethods::is_active( PaymentMethods::GULDEN ) ) {
				new GuldenGateway();
			}

			add_filter( 'pronamic_payment_redirect_url_easydigitaldownloads', array( __CLASS__, 'redirect_url' ), 10, 2 );
			add_action( 'pronamic_payment_status_update_easydigitaldownloads', array( __CLASS__, 'status_update' ), 10, 1 );
			add_filter( 'pronamic_payment_source_text_easydigitaldownloads', array( __CLASS__, 'source_text' ), 10, 2 );

			// Icons
			add_filter( 'edd_accepted_payment_icons', array( __CLASS__, 'accepted_payment_icons' ) );

			// Currencies.
			add_filter( 'edd_currencies', array( __CLASS__, 'currencies' ), 10, 1 );
			add_filter( 'edd_currency_symbol', array( __CLASS__, 'currency_symbol' ), 10, 2 );
			add_filter( 'edd_nlg_currency_filter_before', array( __CLASS__, 'currency_filter_before' ), 10, 3 );
			add_filter( 'edd_nlg_currency_filter_after', array( __CLASS__, 'currency_filter_after' ), 10, 3 );
		}

		add_filter( 'pronamic_payment_source_description_easydigitaldownloads', array( __CLASS__, 'source_description' ), 10, 2 );
		add_filter( 'pronamic_payment_source_url_easydigitaldownloads', array( __CLASS__, 'source_url' ), 10, 2 );
	}

	/**
	 * Payment redirect URL filter.
	 *
	 * @param string                  $url
	 * @param Payment $payment
	 * @return string
	 */
	public static function redirect_url( $url, $payment ) {
		$source_id = $payment->get_source_id();

		$data = new PaymentData( $source_id, array() );

		$url = $data->get_normal_return_url();

		switch ( $payment->get_status() ) {
			case Core_Statuses::CANCELLED:
				$url = $data->get_cancel_url();

				break;
			case Core_Statuses::EXPIRED:
				$url = $data->get_error_url();

				break;
			case Core_Statuses::FAILURE:
				$url = $data->get_error_url();

				break;
			case Core_Statuses::SUCCESS:
				$url = $data->get_success_url();

				break;
			case Core_Statuses::OPEN:
				// Nothing to do?

				break;
		}

		return $url;
	}

	/**
	 * Update the status of the specified payment
	 *
	 * @param Payment $payment
	 */
	public static function status_update( Payment $payment ) {
		$source_id = $payment->get_source_id();

		$data = new PaymentData( $source_id, array() );

		// Only update if order is not completed
		$should_update = edd_get_payment_status( $source_id ) !== EasyDigitalDownloads::ORDER_STATUS_PUBLISH;

		if ( $should_update ) {
			switch ( $payment->get_status() ) {
				case Core_Statuses::CANCELLED:
					// Nothing to do?

					break;
				case Core_Statuses::EXPIRED:
					edd_update_payment_status( $source_id, EasyDigitalDownloads::ORDER_STATUS_ABANDONED );

					break;
				case Core_Statuses::FAILURE:
					edd_update_payment_status( $source_id, EasyDigitalDownloads::ORDER_STATUS_FAILED );

					break;
				case Core_Statuses::SUCCESS:
					edd_insert_payment_note( $source_id, __( 'Payment completed.', 'pronamic_ideal' ) );

					/*
					 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/admin/payments/view-order-details.php#L36
					 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/admin/payments/view-order-details.php#L199-L206
					 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/payments/functions.php#L1312-L1332
					 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/gateways/paypal-standard.php#L555-L576
					 */
					edd_update_payment_status( $source_id, EasyDigitalDownloads::ORDER_STATUS_PUBLISH );

					edd_empty_cart();

					break;
				case Core_Statuses::OPEN:
					edd_insert_payment_note( $source_id, __( 'Payment open.', 'pronamic_ideal' ) );

					break;
				default:
					edd_insert_payment_note( $source_id, __( 'Payment unknown.', 'pronamic_ideal' ) );

					break;
			}
		}
	}

	/**
	 * Filter currencies.
	 *
	 * @param array $currencies Available currencies.
	 *
	 * @return mixed
	 */
	public static function currencies( $currencies ) {
		if ( PaymentMethods::is_active( PaymentMethods::GULDEN ) ) {
			$currencies['NLG'] = sprintf(
				/* translators: %s: Gulden */
				'%s (G)',
				PaymentMethods::get_name( PaymentMethods::GULDEN )
			);
		}

		return $currencies;
	}

	/**
	 * Filter currency symbol.
	 *
	 * @param string $symbol   Symbol.
	 * @param string $currency Currency.
	 *
	 * @return string
	 */
	public static function currency_symbol( $symbol, $currency ) {
		if ( 'NLG' === $currency ) {
			$symbol = 'G';
		}

		return $symbol;
	}

	/**
	 * Filter currency before.
	 *
	 * @param string $formatted Formatted symbol and price.
	 * @param string $currency  Currency.
	 * @param string $price     Price.
	 *
	 * @return string
	 */
	public static function currency_filter_before( $formatted, $currency, $price ) {
		if ( ! function_exists( 'edd_currency_symbol' ) ) {
			return $formatted;
		}

		$symbol = edd_currency_symbol( $currency );

		switch ( $currency ) {
			case 'NLG':
				$formatted = $symbol . $price;

				break;
		}

		return $formatted;
	}

	/**
	 * Filter currency after.
	 *
	 * @param string $formatted Formatted symbol and price.
	 * @param string $currency  Currency.
	 * @param string $price     Price.
	 *
	 * @return string
	 */
	public static function currency_filter_after( $formatted, $currency, $price ) {
		if ( ! function_exists( 'edd_currency_symbol' ) ) {
			return $formatted;
		}

		$symbol = edd_currency_symbol( $currency );

		switch ( $currency ) {
			case 'NLG':
				$formatted = $price . $symbol;

				break;
		}

		return $formatted;
	}

	/**
	 * Source column
	 *
	 * @param string $text
	 * @param Payment $payment
	 *
	 * @return string $text
	 */
	public static function source_text( $text, Payment $payment ) {
		$text = __( 'Easy Digital Downloads', 'pronamic_ideal' ) . '<br />';

		$text .= sprintf(
			'<a href="%s">%s</a>',
			EasyDigitalDownloads::get_payment_url( $payment->source_id ),
			/* translators: %s: source id */
			sprintf( __( 'Payment %s', 'pronamic_ideal' ), $payment->source_id )
		);

		return $text;
	}

	/**
	 * Source description.
	 *
	 * @param string  $description Description.
	 * @param Payment $payment     Payment.
	 *
	 * @return string
	 */
	public static function source_description( $description, Payment $payment ) {
		return __( 'Easy Digital Downloads Order', 'pronamic_ideal' );
	}

	/**
	 * Source URL.
	 *
	 * @param string  $url     URL.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public static function source_url( $url, Payment $payment ) {
		return EasyDigitalDownloads::get_payment_url( $payment->source_id );
	}

	/**
	 * Accepted payment icons
	 *
	 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.1.3/includes/admin/settings/register-settings.php#L261-L268
	 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.1.3/includes/checkout/template.php#L573-L609
	 *
	 * @param array $icons Icons.
	 *
	 * @return array
	 */
	public static function accepted_payment_icons( $icons ) {
		// iDEAL.
		$key           = plugins_url( 'images/ideal/icon-64x48.png', Plugin::$file );
		$icons[ $key ] = PaymentMethods::get_name( PaymentMethods::IDEAL );

		// Bancontact.
		$key           = plugins_url( 'images/bancontact/icon-64x48.png', Plugin::$file );
		$icons[ $key ] = PaymentMethods::get_name( PaymentMethods::BANCONTACT );

		// Bitcoin.
		$key           = plugins_url( 'images/bitcoin/icon-64x48.png', Plugin::$file );
		$icons[ $key ] = PaymentMethods::get_name( PaymentMethods::BITCOIN );

		// Sofort.
		$key           = plugins_url( 'images/sofort/icon-64x48.png', Plugin::$file );
		$icons[ $key ] = PaymentMethods::get_name( PaymentMethods::SOFORT );

		return $icons;
	}
}
