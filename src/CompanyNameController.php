<?php
/**
 * Easy Digital Downloads company name controller
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads
 */

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

/**
 * Title: Easy Digital Downloads company name controller
 * Description:
 * Copyright: 2005-2022 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 4.1.0
 * @since   4.1.0
 * @link    https://gitlab.com/pronamic-plugins/edd-company-name/-/tree/develop
 */
class CompanyNameController {
	/**
	 * Setup plugin integration.
	 *
	 * @return void
	 */
	public function setup() {
		\add_action( 'admin_init', array( $this, 'admin_init' ), 15 );

		if ( $this->is_company_name_field_enabled() ) {
			\add_action( 'edd_purchase_form_before_cc_form', array( $this, 'purchase_form' ) );

			\add_filter( 'edd_purchase_form_required_fields', array( $this, 'purchase_form_required_fields' ) );

			\add_filter( 'edd_payment_meta', array( $this, 'edd_payment_meta' ) );

			\add_action( 'edd_insert_payment', array( $this, 'edd_insert_payment' ) );

			\add_action( 'edd_updated_edited_purchase', array( $this, 'edd_updated_edited_purchase' ) );

			\add_action( 'edd_payment_view_details', array( $this, 'edd_payment_view_details' ) );

			// Templates.
			\add_filter( 'edd_get_payment_meta', array( $this, 'edd_get_payment_meta' ), 10, 2 );

			// Export.
			\add_filter( 'edd_export_csv_cols_payments', array( $this, 'edd_export_csv_cols_payments' ) );
			\add_filter( 'edd_export_get_data_payments', array( $this, 'edd_export_get_data_payments' ) );
		}

		// Settings.
		$this->register_settings();

		// Email.
		if ( function_exists( '\edd_add_email_tag' ) ) {
			\edd_add_email_tag(
				'company_name',
				\__( 'The company name', 'pronamic_ideal' ),
				array( $this, 'email_tag_company_name' )
			);
		}
	}

	/**
	 * Check if company name field is enabled.
	 * 
	 * @return bool True if enabled, false otherwise.
	 */
	private function is_company_name_field_enabled() {
		return (bool) \get_option( 'pronamic_pay_edd_company_name_field_enable' );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		\register_setting(
			'pronamic_pay',
			'pronamic_pay_edd_company_name_field_enable',
			array(
				'type'    => 'boolean',
				'default' => false,
			)
		);
	}

	/**
	 * Admin init.
	 */
	public function admin_init() {
		// Plugin settings - Easy Digital Downloads.
		\add_settings_section(
			'pronamic_pay_edd',
			\__( 'Easy Digital Downloads', 'pronamic_ideal' ),
			'__return_false',
			'pronamic_pay'
		);

		// Add settings fields.
		\add_settings_field(
			'pronamic_pay_edd_company_name_field_enable',
			\__( 'Add company name field', 'pronamic_ideal' ),
			array( $this, 'input_checkbox' ),
			'pronamic_pay',
			'pronamic_pay_edd',
			array(
				'legend'      => \__( 'Add company name field', 'pronamic_ideal' ),
				'description' => \__( 'Add company name field to purchase form fields', 'pronamic_ideal' ),
				'label_for'   => 'pronamic_pay_edd_company_name_field_enable',
				'classes'     => 'regular-text',
				'type'        => 'checkbox',
			)
		);
	}

	/**
	 * Input checkbox.
	 *
	 * @link https://github.com/WordPress/WordPress/blob/4.9.1/wp-admin/options-writing.php#L60-L68
	 * @link https://github.com/WordPress/WordPress/blob/4.9.1/wp-admin/options-reading.php#L110-L141
	 * @param array $args Arguments.
	 */
	public function input_checkbox( $args ) {
		$id     = $args['label_for'];
		$name   = $args['label_for'];
		$value  = \get_option( $name );
		$legend = $args['legend'];

		echo '<fieldset>';

		\printf(
			'<legend class="screen-reader-text"><span>%s</span></legend>',
			\esc_html( $legend )
		);

		\printf(
			'<label for="%s">',
			\esc_attr( $id )
		);

		\printf(
			'<input name="%s" id="%s" type="checkbox" value="1" %s />',
			\esc_attr( $name ),
			\esc_attr( $id ),
			\checked( $value, 1, false )
		);

		echo \esc_html( $args['description'] );

		echo '</label>';

		echo '</fieldset>';
	}

	/**
	 * Purchase form.
	 * 
	 * @link https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/blob/ee6c1f21c1ff35aeda23f364f94e95ec5d1f205f/src/Extension.php#L137-L144
	 * @link https://gitlab.com/pronamic-plugins/edd-company-name/-/blob/develop/edd-company-name.php#L71-91
	 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.2/includes/checkout/template.php#L162

	 * @return void
	 */
	public function purchase_form() {
		?>
		<fieldset class="pronamic-edd-fieldset">
			<legend><?php \esc_html_e( 'Customer', 'pronamic_ideal' ); ?></legend>

			<div class="" id="pronamic-edd-company-name-control">
				<label class="form-label" for="pronamic-edd-company-name">
					<?php \esc_html_e( 'Company Name', 'pronamic_ideal' ); ?>

					<?php if ( \edd_field_is_required( 'edd_company' ) ) : ?>
						<span class="edd-required-indicator">*</span>
					<?php endif; ?>
				</label>

				<span class="edd-description"><?php \esc_html_e( 'Enter the name of your company.', 'pronamic_ideal' ); ?></span>

				<input type="text" name="edd_company" class="form-control edd-input" id="pronamic-edd-company-name" autocomplete="organization" />
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Easy Digital Downlaods purchase form required fields
	 *
	 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.2/includes/process-purchase.php#L362
	 * @param array $required_fields Required fields.
	 * @return array
	 */
	public function purchase_form_required_fields( $required_fields ) {
		$required_fields['edd_company'] = array(
			'error_id'      => 'invalid_company',
			'error_message' => \__( 'Please enter your company name', 'pronamic_ideal' ),
		);

		return $required_fields;
	}

	/**
	 * Payment meta.
	 * 
	 * @param array $payment_meta Meta.
	 * @return array
	 */
	public function edd_payment_meta( $payment_meta ) {
		$payment_meta['company'] = isset( $_POST['edd_company'] ) ? sanitize_text_field( wp_unslash( $_POST['edd_company'] ) ) : ''; // input var okay

		return $payment_meta;
	}

	/**
	 * Easy Digital Downloads insert payment
	 *
	 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.2/includes/payments/functions.php#L202
	 */
	public function edd_insert_payment( $payment ) {
		$company = isset( $_POST['edd_company'] ) ? sanitize_text_field( wp_unslash( $_POST['edd_company'] ) ) : ''; // input var okay

		\update_post_meta( $payment, '_edd_payment_company', $company );
	}

	/**
	 * Easy Digital Downloads updated edited purchase
	 *
	 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.2/includes/admin/payments/actions.php#L193
	 */
	public function edd_updated_edited_purchase( $payment_id ) {
		$company = isset( $_POST['edd-payment-company'] ) ? sanitize_text_field( wp_unslash( $_POST['edd-payment-company'] ) ) : ''; // input var okay

		// Store the company name in the Easy Digital Download payment meta key
		\edd_update_payment_meta( $payment_id, '_edd_payment_company', $company );

		// Store the copmany name also in a WordPress post meta key
		\update_post_meta( $payment_id, '_edd_payment_company', $company );
	}

	/**
	 * Payment view details
	 *
	 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.2/includes/admin/payments/view-order-details.php#L409
	 */
	public function edd_payment_view_details( $payment_id ) {
		$company = \get_post_meta( $payment_id, '_edd_payment_company', true );

		?>
		<div class="column-container" style="margin-top: 1em;">
			<div class="column">
				<strong><?php \esc_html_e( 'Company:', 'pronamic_ideal' ); ?></strong>&nbsp;
				<input type="text" name="edd-payment-company" value="<?php echo \esc_attr( $company ); ?>" class="medium-text" />
			</div>
		</div>
		<?php
	}

	/**
	 * Get payment meta
	 *
	 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.2/includes/payments/functions.php#L810
	 */
	public function edd_get_payment_meta( $meta, $payment_id ) {
		// EDD PDF Invoices uses both `edd-action` and `edd_action` parameters, so we need to check both.
		$actions = \filter_input_array(
			INPUT_GET,
			array(
				'edd-action' => FILTER_SANITIZE_STRING,
				'edd_action' => FILTER_SANITIZE_STRING,
			) 
		);

		if ( ! is_array( $actions ) || ! in_array( 'generate_pdf_invoice', $actions, true ) ) {
			return $meta;
		}

		if ( ! isset( $meta['user_info'] ) ) {
			return $meta;
		}

		$company = \get_post_meta( $payment_id, '_edd_payment_company', true );

		$line1 = $meta['user_info']['address']['line1'];
		$line2 = $meta['user_info']['address']['line2'];

		if ( empty( $line1 ) ) {
			$line1 = $company;
		} elseif ( empty( $line2 ) ) {
			$line2 = $line1;
			$line1 = $company;
		} else {
			$line1 = $company . ' - ' . $line1;
		}

		$meta['user_info']['address']['line1'] = $line1;
		$meta['user_info']['address']['line2'] = $line2;

		return $meta;
	}

	/**
	 * Easy Digital Downloads export CSV columns payments
	 *
	 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.2/includes/admin/reporting/class-export.php#L84
	 */
	public function edd_export_csv_cols_payments( $cols ) {
		$cols['company'] = \__( 'Company', 'pronamic_ideal' );

		return $cols;
	}

	/**
	 * Easy Digital Downloads export get data payments
	 *
	 * @see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.2.2/includes/admin/reporting/class-export-payments.php#L201
	 */
	public function edd_export_get_data_payments( $data ) {
		foreach ( $data as $i => $payment ) {
			if ( isset( $payment['id'] ) ) {
				$payment_id = $payment['id'];

				$data[ $i ]['company'] = \get_post_meta( $payment_id, '_edd_payment_company', true );
			}
		}

		return $data;
	}

	/**
	 * Easy Digital Downloads email tag `company_name`
	 *
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.6.10/includes/emails/class-edd-email-tags.php#L365
	 * @link https://gitlab.com/pronamic-plugins/edd-company-name/-/blob/develop/edd-company-name.php#L306-316
	 * @param int $payment Payment ID.
	 * @return string
	 */
	public function email_tag_company_name( $payment_id ) {
		$company_name = \get_post_meta( $payment_id, '_edd_payment_company', true );

		return $company_name;
	}
}
