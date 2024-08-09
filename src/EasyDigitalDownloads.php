<?php
/**
 * Easy Digital Downloads
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads
 */

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

use WP_Error;

/**
 * Title: Easy Digital Downloads
 * Description:
 * Copyright: 2005-2024 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.0
 * @since   1.0.0
 */
class EasyDigitalDownloads {
	/**
	 * Order status pending
	 *
	 * @var string
	 */
	public const ORDER_STATUS_PENDING = 'pending';

	/**
	 * Order status completed
	 *
	 * @var string
	 */
	public const ORDER_STATUS_PUBLISH = 'publish';

	/**
	 * Order status refunded
	 *
	 * @var string
	 */
	public const ORDER_STATUS_REFUNDED = 'refunded';

	/**
	 * Order status failed
	 *
	 * @var string
	 */
	public const ORDER_STATUS_FAILED = 'failed';

	/**
	 * Order status abandoned
	 *
	 * @var string
	 */
	public const ORDER_STATUS_ABANDONED = 'abandoned';

	/**
	 * Order status revoked/cancelled
	 *
	 * @var string
	 */
	public const ORDER_STATUS_REVOKED = 'revoked';

	/**
	 * Order status cancelled
	 *
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.20/includes/admin/payments/class-payments-table.php#L506-L508
	 * @var string
	 */
	public const ORDER_STATUS_CANCELLED = 'cancelled';

	/**
	 * Get payment URL by the specified payment ID.
	 *
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/3.0.0-beta2/includes/admin/payments/class-payments-table.php#L443
	 *
	 * @param int|string|null $payment_id Payment ID.
	 * @return string
	 */
	public static function get_payment_url( $payment_id ) {
		if ( \defined( '\EDD_VERSION' ) && version_compare( \EDD_VERSION, '3', '<' ) ) {
			$url = \get_edit_post_link( (int) $payment_id );

			if ( null !== $url ) {
				return $url;
			}
		}

		return \add_query_arg(
			[
				'id'        => $payment_id,
				'post_type' => 'download',
				'page'      => 'edd-payment-history',
				'view'      => 'view-order-details',
			],
			\admin_url( 'edit.php' )
		);
	}

	/**
	 * Get download category.
	 *
	 * @param int $post_id Post ID.
	 * @return string|null
	 */
	public static function get_download_category( $post_id ) {
		/*
		 * Yoast SEO primary term support.
		 * @link https://github.com/Yoast/wordpress-seo/blob/8.4/inc/wpseo-functions.php#L62-L81
		 */
		if ( function_exists( 'yoast_get_primary_term' ) ) {
			$name = \yoast_get_primary_term( 'download_category', $post_id );

			return empty( $name ) ? null : $name;
		}

		/*
		 * WordPress core.
		 * @link https://developer.wordpress.org/reference/functions/wp_get_post_terms/
		 */
		$term_names = wp_get_post_terms(
			$post_id,
			'download_category',
			[
				'fields'  => 'names',
				'orderby' => 'count',
				'order'   => 'DESC',
			]
		);

		if ( $term_names instanceof WP_Error ) {
			return null;
		}

		$term_name = reset( $term_names );

		if ( false === $term_name ) {
			return null;
		}

		return $term_name;
	}

	/**
	 * Get payment number.
	 *
	 * @param int $payment_id Payment ID.
	 * @return string
	 */
	public static function get_payment_number( $payment_id ) {
		/*
		 * Check if the 'edd_get_payment_number' function exists, it was added in Easy Digital Downloads version 2.0.
		 *
		 * @since 1.2.0
		 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/2.4.3/includes/payments/functions.php#L1178-L1204
		 */
		if ( function_exists( 'edd_get_payment_number' ) ) {
			return \edd_get_payment_number( $payment_id );
		}

		return (string) $payment_id;
	}

	/**
	 * Get description.
	 *
	 * @param string               $description   Description.
	 * @param int                  $payment_id    Payment ID.
	 * @param array<string, mixed> $purchase_data Purchase data.
	 * @return string
	 */
	public static function get_description( $description, $payment_id, $purchase_data ) {
		if ( empty( $description ) ) {
			$description = '{edd_cart_details_name}';
		}

		// Name.
		$edd_cart_details_name = '';

		if ( is_array( $purchase_data['cart_details'] ) ) {
			$names = wp_list_pluck( $purchase_data['cart_details'], 'name' );

			$edd_cart_details_name = implode( ', ', $names );
		}

		// Replacements.
		$replacements = [
			'{edd_cart_details_name}' => $edd_cart_details_name,
			'{edd_payment_id}'        => $payment_id,
			'{edd_payment_number}'    => self::get_payment_number( $payment_id ),
		];

		// Replace.
		$description = strtr( $description, $replacements );

		return $description;
	}

	/**
	 * Get page URL by option.
	 *
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.8/includes/admin/settings/register-settings.php#L16-L30
	 * @param string $key Option key, for example: 'failure_page' or 'success_page'.
	 * @return string
	 */
	public static function get_option_page_url( $key ) {
		$post_id = edd_get_option( $key );

		if ( empty( $post_id ) ) {
			return home_url( '/' );
		}

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return home_url( '/' );
		}

		$url = get_permalink( $post_id );

		if ( false !== $url ) {
			return $url;
		}

		return home_url( '/' );
	}

	/**
	 * Get the Pronamic configuration ID for this gateway.
	 *
	 * @param string $gateway_id Gateway identifier.
	 * @return null|string
	 */
	public static function get_pronamic_config_id( $gateway_id ) {
		$config_id = edd_get_option( $gateway_id . '_config_id' );

		$config_id = empty( $config_id ) ? get_option( 'pronamic_pay_config_id' ) : $config_id;

		if ( empty( $config_id ) ) {
			return null;
		}

		return $config_id;
	}
}
