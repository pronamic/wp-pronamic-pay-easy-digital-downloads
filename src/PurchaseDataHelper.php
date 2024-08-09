<?php
/**
 * Easy Digital Downloads purchase data helper
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads
 */

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

/**
 * Easy Digital Downloads purchase data helper
 *
 * @author  Remco Tolsma
 * @version 2.1.2
 * @since   2.1.2
 */
class PurchaseDataHelper {
	/**
	 * Get post data value.
	 *
	 * @link https://github.com/WordPress/WordPress-Coding-Standards/wiki/Fixing-errors-for-input-data
	 * @param array<string, mixed> $purchase_data Purchase data.
	 * @param string               $key           Post data key.
	 * @return string|null
	 */
	public static function get_post_data_value( $purchase_data, $key ) {
		if ( ! \array_key_exists( 'post_data', $purchase_data ) ) {
			return null;
		}

		$post_data = $purchase_data['post_data'];

		if ( ! \is_array( $post_data ) ) {
			return null;
		}

		if ( ! \array_key_exists( $key, $post_data ) ) {
			return null;
		}

		$value = $post_data[ $key ];
		$value = \wp_unslash( $value );
		$value = \sanitize_text_field( $value );
		$value = \trim( $value );

		if ( empty( $value ) ) {
			return null;
		}

		return $value;
	}

	/**
	 * Get company name from purchase data array.
	 *
	 * @param array<string, mixed> $purchase_data Purchase data array.
	 * @return string|null
	 */
	public static function get_company_name( $purchase_data ) {
		/**
		 * Pronamic - Easy Digital Downloads - Company name.
		 *
		 * @link https://gitlab.com/pronamic-plugins/edd-company-name/-/blob/1.1.0/edd-company-name.php
		 */
		return self::get_post_data_value( $purchase_data, 'edd_company' );
	}

	/**
	 * Get VAT number from purchase data array.
	 *
	 * @param array<string, mixed> $purchase_data Purchase data array.
	 * @return string|null
	 */
	public static function get_vat_number( $purchase_data ) {
		/**
		 * Pronamic - Easy Digital Downloads - VAT.
		 *
		 * @link https://gitlab.com/pronamic-plugins/edd-vat/-/blob/1.0.0/includes/class-purchase-form.php
		 */
		return self::get_post_data_value( $purchase_data, 'vat_number' );
	}
}
