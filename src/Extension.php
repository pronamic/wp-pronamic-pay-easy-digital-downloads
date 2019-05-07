<?php

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Core\Statuses as Core_Statuses;
use Pronamic\WordPress\Pay\Core\Util;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: Easy Digital Downloads iDEAL Add-On
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.3
 * @since   1.0.0
 */
class Extension {
	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		// The `plugins_loaded` is one of the earliest hooks after EDD is set up.
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
			new Gateway(
				array(
					'id'             => 'pronamic_ideal',
					'admin_label'    => __( 'Pronamic', 'pronamic_ideal' ),
					'checkout_label' => __( 'iDEAL', 'pronamic_ideal' ),
				)
			);

			foreach ( self::get_payment_methods() as $id => $payment_method ) {
				new Gateway(
					array(
						'id'             => $id,
						'checkout_label' => PaymentMethods::get_name( $payment_method ),
						'payment_method' => $payment_method,
					)
				);
			}

			add_filter( 'pronamic_payment_redirect_url_easydigitaldownloads', array( __CLASS__, 'redirect_url' ), 10, 2 );
			add_action( 'pronamic_payment_status_update_easydigitaldownloads', array( __CLASS__, 'status_update' ), 10, 1 );
			add_filter( 'pronamic_payment_source_text_easydigitaldownloads', array( __CLASS__, 'source_text' ), 10, 2 );

			// Maybe empty cart for completed payment when handling returns.
			add_action( 'save_post_pronamic_payment', array( __CLASS__, 'maybe_empty_cart' ), 10, 1 );

			// Icons.
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
	 * Get payment methods.
	 *
	 * @return array
	 */
	private static function get_payment_methods() {
		$default = array(
			'pronamic_pay_mister_cash'        => PaymentMethods::BANCONTACT,
			'pronamic_pay_bank_transfer'      => PaymentMethods::BANK_TRANSFER,
			'pronamic_pay_bitcoin'            => PaymentMethods::BITCOIN,
			'pronamic_pay_credit_card'        => PaymentMethods::CREDIT_CARD,
			'pronamic_pay_direct_debit'       => PaymentMethods::DIRECT_DEBIT,
			'pronamic_pay_direct_debit_ideal' => PaymentMethods::DIRECT_DEBIT_IDEAL,
			'pronamic_pay_ideal'              => PaymentMethods::IDEAL,
			'pronamic_pay_sofort'             => PaymentMethods::SOFORT,
		);

		$optional = array(
			'pronamic_pay_afterpay'                => PaymentMethods::AFTERPAY,
			'pronamic_pay_alipay'                  => PaymentMethods::ALIPAY,
			'pronamic_pay_belfius'                 => PaymentMethods::BELFIUS,
			'pronamic_pay_billink'                 => PaymentMethods::BILLINK,
			'pronamic_pay_bunq'                    => PaymentMethods::BUNQ,
			'pronamic_pay_capayable'               => PaymentMethods::CAPAYABLE,
			'pronamic_pay_direct_debit_bancontact' => PaymentMethods::DIRECT_DEBIT_BANCONTACT,
			'pronamic_pay_direct_debit_ideal'      => PaymentMethods::DIRECT_DEBIT_IDEAL,
			'pronamic_pay_direct_debit_sofort'     => PaymentMethods::DIRECT_DEBIT_SOFORT,
			'pronamic_pay_focum'                   => PaymentMethods::FOCUM,
			'pronamic_pay_giropay'                 => PaymentMethods::GIROPAY,
			'pronamic_pay_gulden'                  => PaymentMethods::GULDEN,
			'pronamic_pay_idealqr'                 => PaymentMethods::IDEALQR,
			'pronamic_pay_in3'                     => PaymentMethods::IN3,
			'pronamic_pay_kbc'                     => PaymentMethods::KBC,
			'pronamic_pay_klarna_pay_later'        => PaymentMethods::KLARNA_PAY_LATER,
			'pronamic_pay_maestro'                 => PaymentMethods::MAESTRO,
			'pronamic_pay_payconiq'                => PaymentMethods::PAYCONIQ,
			'pronamic_pay_paypal'                  => PaymentMethods::PAYPAL,
		);

		$optional = array_filter(
			$optional,
			function ( $payment_method ) {
				return PaymentMethods::is_active( $payment_method );
			}
		);

		$payment_methods = array_merge( $default, $optional );

		uasort(
			$payment_methods,
			function ( $a, $b ) {
				return strnatcasecmp( PaymentMethods::get_name( $a ), PaymentMethods::get_name( $b ) );
			}
		);

		return $payment_methods;
	}

	/**
	 * Payment redirect URL filter.
	 *
	 * @param string  $url     Redirect URL.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public static function redirect_url( $url, $payment ) {
		switch ( $payment->get_status() ) {
			case Core_Statuses::CANCELLED:
			case Core_Statuses::EXPIRED:
			case Core_Statuses::FAILURE:
				return EasyDigitalDownloads::get_option_page_url( 'failure_page' );

			case Core_Statuses::SUCCESS:
				return EasyDigitalDownloads::get_option_page_url( 'success_page' );

			case Core_Statuses::RESERVED:
			case Core_Statuses::OPEN:
				return home_url( '/' );
		}

		return $url;
	}

	/**
	 * Maybe empty cart for succesful payment.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function maybe_empty_cart( $post_id ) {
		// Only empty cart when handling returns.
		if ( ! Util::input_has_vars( INPUT_GET, array( 'payment', 'key' ) ) ) {
			return;
		}

		$payment = get_pronamic_payment( $post_id );

		// Only empty for completed payments.
		if ( ! $payment || $payment->get_status() !== Core_Statuses::SUCCESS ) {
			return;
		}

		edd_empty_cart();
	}

	/**
	 * Update the status of the specified payment
	 *
	 * @param Payment $payment Payment.
	 */
	public static function status_update( Payment $payment ) {
		$source_id = (int) $payment->get_source_id();

		// Only update if order is not completed.
		$should_update = edd_get_payment_status( $source_id ) !== EasyDigitalDownloads::ORDER_STATUS_PUBLISH;

		// Always empty cart for completed payments.
		if ( $payment->get_status() === Core_Statuses::SUCCESS ) {
			edd_empty_cart();
		}

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
				case Core_Statuses::RESERVED:
					$note = array(
						sprintf(
							'%s %s.',
							PaymentMethods::get_name( $payment->get_method() ),
							__( 'payment reserved at gateway', 'pronamic_ideal' )
						),
					);

					$gateway = Plugin::get_gateway( $payment->get_config_id() );

					if ( $gateway->supports( 'reservation_payments' ) ) {
						$payment_edit_link = add_query_arg(
							array(
								'post'   => $payment->get_id(),
								'action' => 'edit',
							),
							admin_url( 'post.php' )
						);

						$payment_link = sprintf(
							'<a href="%1$s">%2$s</a>',
							$payment_edit_link,
							sprintf(
								/* translators: %s: payment id */
								esc_html( __( 'payment #%s', 'pronamic_ideal' ) ),
								$payment->get_id()
							)
						);

						$note[] = sprintf(
							/* translators: %s: payment edit link */
							__( 'Create an invoice at payment gateway for %1$s after processing the order.', 'pronamic_ideal' ),
							$payment_link // WPCS: xss ok.
						);
					}

					$note = implode( ' ', $note );

					edd_insert_payment_note( $source_id, $note );

					break;
				case Core_Statuses::SUCCESS:
					edd_insert_payment_note( $source_id, __( 'Payment completed.', 'pronamic_ideal' ) );

					/*
					 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/admin/payments/view-order-details.php#L36
					 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/admin/payments/view-order-details.php#L199-L206
					 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/payments/functions.php#L1312-L1332
					 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/gateways/paypal-standard.php#L555-L576
					 */
					edd_update_payment_status( $source_id, EasyDigitalDownloads::ORDER_STATUS_PUBLISH );

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
	 * @param string  $text    Source text.
	 * @param Payment $payment Payment.
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
	 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.1.3/includes/admin/settings/register-settings.php#L261-L268
	 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.1.3/includes/checkout/template.php#L573-L609
	 *
	 * @param array $icons Icons.
	 *
	 * @return array
	 */
	public static function accepted_payment_icons( $icons ) {
		$payment_methods = self::get_payment_methods();

		foreach ( $payment_methods as $id => $payment_method ) {
			$icon = sprintf(
				'/images/%s/icon-64x48.png',
				str_replace( '_', '-', $payment_method )
			);

			// Check if file exists.
			if ( ! is_readable( plugin_dir_path( Plugin::$file ) . $icon ) ) {
				continue;
			}

			// Add icon URL.
			$url = plugins_url( $icon, Plugin::$file );

			$icons[ $url ] = PaymentMethods::get_name( $payment_method );
		}

		return $icons;
	}
}
