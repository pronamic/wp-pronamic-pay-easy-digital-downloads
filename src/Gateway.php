<?php
/**
 * Easy Digital Downloads gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads
 */

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

use Pronamic\WordPress\Money\Currency;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Money\TaxedMoney;
use Pronamic\WordPress\Pay\Address;
use Pronamic\WordPress\Pay\ContactName;
use Pronamic\WordPress\Pay\Core\Util;
use Pronamic\WordPress\Pay\Customer;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentLines;
use Pronamic\WordPress\Pay\Payments\PaymentLineType;

/**
 * Title: Easy Digital Downloads gateway
 * Description:
 * Copyright: 2005-2024 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.2
 * @since   1.1.0
 */
class Gateway {
	/**
	 * ID.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Admin label.
	 *
	 * @var string
	 */
	private $admin_label;

	/**
	 * Checkout label.
	 *
	 * @var string
	 */
	private $checkout_label;

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	private $payment_method;

	/**
	 * Supports.
	 *
	 * @var string[]
	 */
	private $supports;

	/**
	 * Bootstrap
	 *
	 * @param array<string, mixed> $args Gateway properties.
	 */
	public function __construct( $args ) {
		$args = \wp_parse_args(
			$args,
			[
				'id'             => null,
				'admin_label'    => null,
				'checkout_label' => null,
				'supports'       => [],
				'payment_method' => null,
			]
		);

		if ( null === $args['admin_label'] ) {
			$args['admin_label'] = sprintf(
				/* translators: 1: Gateway admin label prefix, 2: Gateway admin label */
				__( '%1$s - %2$s', 'pronamic_ideal' ),
				__( 'Pronamic', 'pronamic_ideal' ),
				$args['checkout_label']
			);
		}

		$this->id             = $args['id'];
		$this->admin_label    = $args['admin_label'];
		$this->checkout_label = $args['checkout_label'];
		$this->supports       = $args['supports'];
		$this->payment_method = $args['payment_method'];

		// Settings.
		$checkout_label = \edd_get_option( $this->id . '_checkout_label' );

		if ( ! empty( $checkout_label ) ) {
			$this->checkout_label = $checkout_label;
		}

		// Actions.
		\add_action( 'edd_gateway_' . $this->id, [ $this, 'process_purchase' ] );

		/*
		 * Remove CC Form
		 *
		 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/1.9.4/includes/checkout/template.php#L97
		 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/1.9.4/includes/gateways/paypal-standard.php#L12
		 */
		\add_action( 'edd_' . $this->id . '_cc_form', [ $this, 'payment_fields' ] );

		// Filters.
		\add_filter( 'edd_settings_sections_gateways', [ $this, 'register_gateway_section' ] );
		\add_filter( 'edd_settings_gateways', [ $this, 'settings_gateways' ] );
		\add_filter( 'edd_payment_gateways', [ $this, 'payment_gateways' ] );

		\add_filter( 'edd_get_payment_transaction_id-' . $this->id, [ $this, 'get_payment_transaction_id' ] );
	}

	/**
	 * Add the gateway to Easy Digital Downloads
	 *
	 * @param array<string, array<string, mixed>> $gateways Gateways.
	 * @return array<string, array<string, mixed>> $gateways
	 */
	public function payment_gateways( $gateways ) {
		$gateways[ $this->id ] = [
			'admin_label'    => $this->admin_label,
			'checkout_label' => $this->checkout_label,
			'supports'       => $this->supports,
		];

		return $gateways;
	}

	/**
	 * Register gateway section.
	 *
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.8.17/includes/admin/settings/register-settings.php#L1272-L1275
	 *
	 * @param array<string, string> $gateway_sections Gateway sections.
	 * @return array<string, string>
	 */
	public function register_gateway_section( $gateway_sections ) {
		$gateway_sections[ $this->id ] = $this->admin_label;

		return $gateway_sections;
	}

	/**
	 * Add the iDEAL configuration settings to the Easy Digital Downloads payment gateways settings page.
	 *
	 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.8/includes/admin/settings/register-settings.php#L126
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.8.17/includes/admin/settings/register-settings.php#L408-L409
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.8.17/includes/gateways/amazon-payments.php#L344-L424
	 *
	 * @param mixed $settings_gateways Gateway settings.
	 *
	 * @return mixed $settings_gateways
	 */
	public function settings_gateways( $settings_gateways ) {
		$settings = [
			$this->id                     => [
				'id'   => $this->id,
				/* translators: %s: admin label */
				'name' => '<strong>' . \sprintf( __( '%s Settings', 'pronamic_ideal' ), $this->admin_label ) . '</strong>',
				/* translators: %s: gateway admin label */
				'desc' => \sprintf( __( 'Configure the %s settings', 'pronamic_ideal' ), $this->admin_label ),
				'type' => 'header',
			],
			$this->id . '_config_id'      => [
				'id'      => $this->id . '_config_id',
				'name'    => __( 'Gateway Configuration', 'pronamic_ideal' ),
				'type'    => 'select',
				'options' => Plugin::get_config_select_options( $this->payment_method ),
				'std'     => \get_option( 'pronamic_pay_config_id' ),
			],
			$this->id . '_checkout_label' => [
				'id'   => $this->id . '_checkout_label',
				'name' => __( 'Checkout Label', 'pronamic_ideal' ),
				'type' => 'text',
				// @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.5.9/includes/admin/settings/register-settings.php#L1537-L1541
				// @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.5.9/includes/gateways/amazon-payments.php#L330
				'std'  => $this->checkout_label,
			],
			$this->id . '_description'    => [
				'id'   => $this->id . '_description',
				'name' => __( 'Description', 'pronamic_ideal' ),
				'type' => 'text',
				// @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.5.9/includes/admin/settings/register-settings.php#L1537-L1541
				// @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.5.9/includes/gateways/amazon-payments.php#L330
				'std'  => '{edd_cart_details_name}',
				/* translators: %s: <code>{edd_cart_details_name}</code> */
				'desc' => '<br />' . \sprintf( __( 'Default: %s', 'pronamic_ideal' ), '<code>{edd_cart_details_name}</code>' ) .
					/* translators: %s: <code>{edd_cart_details_name}</code> */
					'<br />' . \sprintf( __( 'Available Tags: %s', 'pronamic_ideal' ), '<code>{edd_cart_details_name}</code> <code>{edd_payment_id}</code>' ),
			],
		];

		$settings_gateways[ $this->id ] = $settings;

		return $settings_gateways;
	}

	/**
	 * Payment fields for this gateway
	 *
	 * @version 1.2.1
	 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/1.9.4/includes/checkout/template.php#L167
	 * @return void
	 */
	public function payment_fields() {
		$config_id = EasyDigitalDownloads::get_pronamic_config_id( $this->id );

		$gateway = Plugin::get_gateway( (int) $config_id );

		if ( null === $gateway ) {
			return;
		}

		$payment_method = $gateway->get_payment_method( $this->payment_method );

		if ( null === $payment_method ) {
			return;
		}

		$fields = $payment_method->get_fields();

		if ( empty( $fields ) ) {
			return;
		}

		echo '<fieldset class="edd-do-validate">';
		echo '<legend>', \esc_html( $this->checkout_label ), '</legend>';

		foreach ( $fields as $field ) {
			echo '<p>';

			\printf(
				'<label for="%s">%s%s</label>',
				\esc_attr( $field->get_id() ),
				\esc_html( $field->get_label() ),
				$field->is_required() ? ' <span class="edd-required-indicator">*</span>' : ''
			);

			$field->output();

			echo '</p>';
		}

		echo '</fieldset>';
	}

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
	 *
	 * @param array<string, mixed> $purchase_data Purchase data.
	 * @return void
	 */
	public function process_purchase( $purchase_data ) {
		// Collect payment data.
		$edd_currency = \edd_get_currency();

		$payment_data = [
			'price'        => $purchase_data['price'],
			'date'         => $purchase_data['date'],
			'user_email'   => $purchase_data['user_email'],
			'purchase_key' => $purchase_data['purchase_key'],
			'currency'     => $edd_currency,
			'downloads'    => $purchase_data['downloads'],
			'user_info'    => $purchase_data['user_info'],
			'cart_details' => $purchase_data['cart_details'],
			'gateway'      => $this->id,
			'status'       => 'pending',
		];

		// Record the pending payment.
		$edd_payment_id = \edd_insert_payment( $payment_data );

		// Check payment.
		if ( false === $edd_payment_id ) {
			// Log error.
			\edd_record_gateway_error(
				\__( 'Payment Error', 'pronamic_ideal' ),
				\sprintf(
					/* translators: %s: payment data JSON */
					\__( 'Payment creation failed before sending buyer to the payment provider. Payment data: %s', 'pronamic_ideal' ),
					\strval( \wp_json_encode( $payment_data ) )
				)
			);

			\edd_send_back_to_checkout(
				[
					'payment-mode' => $purchase_data['post_data']['edd-gateway'],
				]
			);

			return;
		}

		$edd_payment_id = (int) $edd_payment_id;

		// Get gateway and currency.
		$config_id = EasyDigitalDownloads::get_pronamic_config_id( $this->id );

		$gateway = Plugin::get_gateway( (int) $config_id );

		if ( null === $gateway ) {
			\edd_set_error( 'pronamic_pay_error', Plugin::get_default_error_message() );

			\edd_send_back_to_checkout(
				[
					'payment-mode=' => $purchase_data['post_data']['edd-gateway'],
				]
			);

			return;
		}

		// Currency.
		$currency = Currency::get_instance( $edd_currency );

		/**
		 * Tax.
		 *
		 * @todo We have to check how tax is handled in Easy Digital Downloads 3.0.
		 *
		 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.22/includes/payments/functions.php#L148-L277
		 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/3.0.0-beta2/includes/payments/functions.php#L141-L200
		 */
		$tax_percentage = null;

		if ( \edd_use_taxes() ) {
			if ( \array_key_exists( 'tax_rate', $purchase_data ) ) {
				$tax_rate = $purchase_data['tax_rate'];

				$tax_percentage = $tax_rate * 100;
			}
		}

		// Payment.
		$payment = new Payment();

		$payment->order_id = EasyDigitalDownloads::get_payment_number( $edd_payment_id );
		$payment->title    = sprintf(
			/* translators: %s: order id */
			__( 'Easy Digital Downloads order %s', 'pronamic_ideal' ),
			$payment->order_id
		);

		$payment->set_description(
			EasyDigitalDownloads::get_description(
				\edd_get_option( $this->id . '_description' ),
				$edd_payment_id,
				$purchase_data
			)
		);

		$payment->config_id = (int) $config_id;
		$payment->source    = 'easydigitaldownloads';
		$payment->source_id = $edd_payment_id;

		$payment->set_payment_method( $this->payment_method );

		if ( \array_key_exists( 'price', $purchase_data ) ) {
			$payment->set_total_amount( new TaxedMoney( $purchase_data['price'], $currency, $purchase_data['tax'], $tax_percentage ) );
		}

		// Name.
		$name = new ContactName();

		// Company Name.
		$company_name = PurchaseDataHelper::get_company_name( $purchase_data );

		// VAT Number.
		$vat_number = PurchaseDataHelper::get_vat_number( $purchase_data );

		// Customer.
		$customer = new Customer();

		$customer->set_name( $name );
		$customer->set_company_name( $company_name );
		$customer->set_vat_number( $vat_number );
		$customer->set_phone( null );

		$payment->set_customer( $customer );

		if ( \array_key_exists( 'user_info', $purchase_data ) && \is_array( $purchase_data['user_info'] ) ) {
			$user_info = $purchase_data['user_info'];

			if ( \array_key_exists( 'email', $user_info ) ) {
				$customer->set_email( $user_info['email'] );
			}

			if ( \array_key_exists( 'first_name', $user_info ) ) {
				$name->set_first_name( $user_info['first_name'] );
			}

			if ( \array_key_exists( 'last_name', $user_info ) ) {
				$name->set_last_name( $user_info['last_name'] );
			}

			if ( \array_key_exists( 'address', $user_info ) && \is_array( $user_info['address'] ) && ! empty( $user_info['address'] ) ) {
				$address_array = $user_info['address'];

				$address = new Address();

				$address->set_name( $name );
				$address->set_company_name( $company_name );

				if ( \array_key_exists( 'line1', $address_array ) ) {
					$address->set_line_1( $address_array['line1'] );
				}

				if ( \array_key_exists( 'line2', $address_array ) ) {
					$address->set_line_2( $address_array['line2'] );
				}

				if ( \array_key_exists( 'city', $address_array ) ) {
					$address->set_city( $address_array['city'] );
				}

				if ( \array_key_exists( 'state', $address_array ) ) {
					$address->set_region( $address_array['state'] );
				}

				if ( \array_key_exists( 'country', $address_array ) ) {
					$address->set_country_code( $address_array['country'] );
				}

				if ( \array_key_exists( 'zip', $address_array ) ) {
					$address->set_postal_code( $address_array['zip'] );
				}

				if ( \array_key_exists( 'email', $user_info ) ) {
					$address->set_email( $user_info['email'] );
				}

				$payment->set_billing_address( $address );
				$payment->set_shipping_address( $address );
			}
		}

		// Lines.
		$payment->lines = new PaymentLines();

		if ( \array_key_exists( 'cart_details', $purchase_data ) && \is_array( $purchase_data['cart_details'] ) ) {
			$cart_details = $purchase_data['cart_details'];

			$cart_detail_defaults = [
				'name'        => null,
				'id'          => null,
				'item_number' => null,
				'item_price'  => null,
				'quantity'    => null,
				'discount'    => null,
				'subtotal'    => null,
				'tax'         => null,
				'fees'        => null,
				'price'       => null,
			];

			foreach ( $cart_details as $cart_detail ) {
				$detail = \wp_parse_args( $cart_detail, $cart_detail_defaults );

				$line = $payment->lines->new_line();

				/**
				 * ID.
				 *
				 * We build the ID from the cart detail ID and the optional cart item price ID.
				 *
				 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.17/includes/gateways/functions.php#L244-L247
				 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.17/includes/cart/functions.php#L220-L230
				 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.17/includes/cart/class-edd-cart.php#L1173-L1189
				 */
				$id = $detail['id'];

				$item_price_id = \edd_get_cart_item_price_id( $detail );

				if ( null !== $item_price_id ) {
					$id = \sprintf( '%s-%s', $id, $item_price_id );
				}

				$line->set_id( $id );

				/**
				 * Name.
				 *
				 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.17/includes/cart/functions.php#L243-L252
				 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.17/includes/cart/class-edd-cart.php#L1207-L1227
				 */
				$line->set_name( \edd_get_cart_item_name( $detail ) );

				$unit_price = $detail['price'] / $detail['quantity'];
				$unit_tax   = $detail['tax'] / $detail['quantity'];

				$line->set_unit_price( new TaxedMoney( $unit_price, $currency, $unit_tax, $tax_percentage ) );
				$line->set_total_amount( new TaxedMoney( $detail['price'], $currency, $detail['tax'], $tax_percentage ) );

				$line->set_type( PaymentLineType::DIGITAL );
				$line->set_quantity( $detail['quantity'] );
				$line->set_discount_amount( new Money( $detail['discount'], $currency ) );
				$line->set_product_category( EasyDigitalDownloads::get_download_category( $detail['id'] ) );

				// Product URL.
				$product_url = \get_permalink( $detail['id'] );

				if ( false !== $product_url ) {
					$line->set_product_url( $product_url );
				}

				// Image URL.
				$attachment_id = \get_post_thumbnail_id( $detail['id'] );

				if ( false !== $attachment_id ) {
					$image_url = \wp_get_attachment_url( $attachment_id );

					if ( false !== $image_url ) {
						$line->set_image_url( $image_url );
					}
				}
			}
		}

		// Fees.
		$edd_payment = \edd_get_payment( $edd_payment_id );

		$fees = [];

		if ( false !== $edd_payment ) {
			$fees = $edd_payment->get_fees();
		}

		$fee_defaults = [
			'amount'      => null,
			'label'       => null,
			'type'        => null,
			'no_tax'      => null,
			'download_id' => null,
			'price_id'    => null,
		];

		foreach ( $fees as $id => $fee ) {
			$fee = \wp_parse_args( $fee, $fee_defaults );

			$line = $payment->lines->new_line();

			$fee_tax_percentage = $fee['no_tax'] ? null : $tax_percentage;

			$line->set_unit_price( new TaxedMoney( $fee['amount'], $currency, null, $fee_tax_percentage ) );
			$line->set_total_amount( new TaxedMoney( $fee['amount'], $currency, null, $fee_tax_percentage ) );

			$line->set_type( PaymentLineType::FEE );
			$line->set_name( $fee['label'] );
			$line->set_id( $fee['id'] );
			$line->set_quantity( 1 );
		}

		// Start.
		try {
			$payment = Plugin::start_payment( $payment );
		} catch ( \Exception $e ) {
			\edd_record_gateway_error(
				__( 'Payment Error', 'pronamic_ideal' ),
				\sprintf(
					/* translators: %s: payment data JSON */
					__( 'Payment creation failed before sending buyer to the payment provider. Payment data: %s', 'pronamic_ideal' ),
					(string) \wp_json_encode( $payment_data )
				),
				(int) $edd_payment_id
			);

			\edd_set_error( 'pronamic_pay_error', Plugin::get_default_error_message() );
			\edd_set_error( 'pronamic_pay_error_' . $e->getCode(), $e->getMessage() );

			\edd_send_back_to_checkout(
				[
					'payment-mode' => $purchase_data['post_data']['edd-gateway'],
				]
			);

			return;
		}

		/*
		 * Transaction ID
		 *
		 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.3/includes/payments/functions.php#L1400-L1416
		 */
		$transaction_id = $payment->get_transaction_id();

		if ( null !== $transaction_id ) {
			\edd_set_payment_transaction_id( $edd_payment_id, $transaction_id );
		}

		// Insert payment note.
		$payment_link = add_query_arg(
			[
				'post'   => $payment->get_id(),
				'action' => 'edit',
			],
			admin_url( 'post.php' )
		);

		$note = sprintf(
			/* translators: %s: payment id */
			__( 'Payment %s pending.', 'pronamic_ideal' ),
			sprintf( '<a href="%s">#%s</a>', $payment_link, $payment->get_id() )
		);

		\edd_insert_payment_note( $edd_payment_id, $note );

		$gateway->redirect( $payment );

		exit;
	}

	/**
	 * Get payment transaction ID
	 *
	 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.3/includes/payments/functions.php#L1378-L1398
	 * @param string $payment_id Payment ID.
	 * @return null
	 */
	public function get_payment_transaction_id( $payment_id ) {
		$payment_id = null;

		return $payment_id;
	}
}
