<?php

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

use Pronamic\WordPress\Pay\AbstractPluginIntegration;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Payments\PaymentStatus as Core_Statuses;
use Pronamic\WordPress\Pay\Core\Util;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: Easy Digital Downloads extension
 * Description:
 * Copyright: 2005-2021 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.2
 * @since   1.0.0
 */
class Extension extends AbstractPluginIntegration {
	/**
	 * Refunds manager.
	 *
	 * @var RefundsManager
	 */
	private $refunds_manager;

	/**
	 * Constructs and initialize Easy Digital Downloads extension.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'name' => __( 'Easy Digital Downloads', 'pronamic_ideal' ),
			)
		);

		// Dependencies.
		$dependencies = $this->get_dependencies();

		$dependencies->add( new EasyDigitalDownloadsDependency() );
	}

	/**
	 * Setup plugin integration.
	 *
	 * @return void
	 */
	public function setup() {
		add_filter( 'pronamic_payment_source_text_easydigitaldownloads', array( $this, 'source_text' ), 10, 2 );
		add_filter( 'pronamic_payment_source_description_easydigitaldownloads', array( $this, 'source_description' ), 10, 2 );

		// Check if dependencies are met and integration is active.
		if ( ! $this->is_active() ) {
			return;
		}

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

		add_filter( 'pronamic_payment_source_url_easydigitaldownloads', array( $this, 'source_url' ), 10, 2 );
		add_filter( 'pronamic_payment_redirect_url_easydigitaldownloads', array( __CLASS__, 'redirect_url' ), 10, 2 );
		add_action( 'pronamic_payment_status_update_easydigitaldownloads', array( __CLASS__, 'status_update' ), 10, 1 );

		// Maybe empty cart for completed payment when handling returns.
		add_action( 'save_post_pronamic_payment', array( __CLASS__, 'maybe_empty_cart' ), 10, 1 );

		// Icons.
		add_filter( 'edd_accepted_payment_icons', array( __CLASS__, 'accepted_payment_icons' ) );

		// Currencies.
		add_filter( 'edd_currencies', array( __CLASS__, 'currencies' ), 10, 1 );
		add_filter( 'edd_currency_symbol', array( __CLASS__, 'currency_symbol' ), 10, 2 );
		add_filter( 'edd_nlg_currency_filter_before', array( __CLASS__, 'currency_filter_before' ), 10, 3 );
		add_filter( 'edd_nlg_currency_filter_after', array( __CLASS__, 'currency_filter_after' ), 10, 3 );

		// Statuses.
		add_filter( 'edd_payment_statuses', array( __CLASS__, 'edd_payment_statuses' ) );
		add_filter( 'edd_payments_table_views', array( $this, 'payments_table_views' ) );

		$this->register_post_statuses();

		// Refunds manager.
		$this->refunds_manager = new RefundsManager();

		$this->refunds_manager->setup();
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
			'pronamic_pay_afterpay'                => PaymentMethods::AFTERPAY_NL,
			'pronamic_pay_alipay'                  => PaymentMethods::ALIPAY,
			'pronamic_pay_belfius'                 => PaymentMethods::BELFIUS,
			'pronamic_pay_billink'                 => PaymentMethods::BILLINK,
			'pronamic_pay_bunq'                    => PaymentMethods::BUNQ,
			'pronamic_pay_blik'                    => PaymentMethods::BLIK,
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
			'pronamic_pay_mb_way'                  => PaymentMethods::MB_WAY,
			'pronamic_pay_payconiq'                => PaymentMethods::PAYCONIQ,
			'pronamic_pay_paypal'                  => PaymentMethods::PAYPAL,
			'pronamic_pay_spraypay'                => PaymentMethods::SPRAYPAY,
			'pronamic_pay_twint'                   => PaymentMethods::TWINT,
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
		$source_id = (int) $payment->get_source_id();

		switch ( $payment->get_status() ) {
			case Core_Statuses::CANCELLED:
			case Core_Statuses::EXPIRED:
			case Core_Statuses::FAILURE:
				/**
				 * Failed transaction URI.
				 *
				 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.10.3/includes/checkout/functions.php#L184-L199
				 */
				return \edd_get_failed_transaction_uri();
			case Core_Statuses::SUCCESS:
				/**
				 * Success page URI.
				 *
				 * The `payment_key` query argument is added so users will also see the receipt
				 * when the purchase session is no longer available.
				 *
				 * @link https://github.com/wp-pay-extensions/easy-digital-downloads/pull/1
				 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.10.3/includes/shortcodes.php#L657-L689
				 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.10.3/includes/payments/functions.php#L1158-L1168
				 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.10.3/includes/checkout/functions.php#L58-L75
				 */
				return \add_query_arg(
					'payment_key',
					\edd_get_payment_key( $source_id ),
					\edd_get_success_page_uri()
				);
			case Core_Statuses::RESERVED:
			case Core_Statuses::OPEN:
				return \home_url( '/' );
		}

		return $url;
	}

	/**
	 * Maybe empty cart for successful payment.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public static function maybe_empty_cart( $post_id ) {
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
					edd_update_payment_status( $source_id, EasyDigitalDownloads::ORDER_STATUS_CANCELLED );

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
							PaymentMethods::get_name( $payment->get_payment_method() ),
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
	public function source_text( $text, Payment $payment ) {
		$text = __( 'Easy Digital Downloads', 'pronamic_ideal' ) . '<br />';

		$text .= sprintf(
			'<a href="%s">%s</a>',
			EasyDigitalDownloads::get_payment_url( $payment->source_id ),
			/* translators: %s: payment number */
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
	public function source_description( $description, Payment $payment ) {
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
	public function source_url( $url, Payment $payment ) {
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

	/**
	 * Easy Digital Downloads payment statuses.
	 *
	 * The Easy Digital Downloads plugin is equipped with a "Set To Cancelled" bulk action.
	 * This bulk action will set the status of payments to 'cancelled', this is however not
	 * a registered payment status. Therefore we will register 'cancelled' as an payment
	 * status.
	 *
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.20/includes/admin/payments/class-payments-table.php#L427-L517
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.20/includes/payments/functions.php#L761-L779
	 * @param array $payment_statuses Easy Digital Downloads payment statuses.
	 * @return array
	 */
	public static function edd_payment_statuses( $payment_statuses ) {
		if ( ! array_key_exists( 'cancelled', $payment_statuses ) ) {
			$payment_statuses['cancelled'] = __( 'Cancelled', 'pronamic_ideal' );
		}

		if ( ! array_key_exists( 'partially_refunded', $payment_statuses ) ) {
			$payment_statuses['partially_refunded'] = __( 'Partially Refunded', 'pronamic_ideal' );
		}

		return $payment_statuses;
	}

	/**
	 * Register cancelled post status.
	 *
	 * @return void
	 */
	private function register_post_statuses() {
		register_post_status(
			'cancelled',
			array(
				'label'                     => _x( 'Cancelled', 'Easy Digital Downloads cancelled payment status', 'pronamic_ideal' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count value */
				'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'pronamic_ideal' ),
			)
		);

		register_post_status(
			'partially_refunded',
			array(
				'label'                     => _x( 'Partially Refunded', 'Easy Digital Downloads payment status', 'pronamic_ideal' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count value */
				'label_count'               => _n_noop( 'Partially Refunded <span class="count">(%s)</span>', 'Partially Refunded <span class="count">(%s)</span>', 'pronamic_ideal' ),
			)
		);
	}

	/**
	 * Payments table views.
	 *
	 * @param array $views Payments table views.
	 *
	 * @return array
	 */
	public function payments_table_views( $views ) {
		$count = \wp_count_posts( 'edd_payment' );

		$statuses = array( 'cancelled', 'partially_refunded' );

		foreach ( $statuses as $status ) {
			// Check if view for status already exists.
			if ( \array_key_exists( $status, $views ) ) {
				continue;
			}

			// Get post status object.
			$post_status = \get_post_status_object( $status );

			if ( null === $post_status ) {
				continue;
			}

			$views[ $status ] = sprintf(
				'<a href="%1$s"%2$s>%3$s</a>&nbsp;<span class="count">(%4$s)</span>',
				\add_query_arg(
					array(
						'status' => $status,
						'paged'  => false,
					)
				),
				\filter_input( \INPUT_GET, 'status' ) === $status ? ' class="current"' : '',
				\esc_html( $post_status->label ),
				\esc_html( \property_exists( $count, $status ) ? $count->$status : 0 )
			);
		}

		return $views;
	}
}
