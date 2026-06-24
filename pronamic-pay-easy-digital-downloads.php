<?php
/**
 * Plugin Name: Pronamic Pay Easy Digital Downloads Add-On
 * Plugin URI: https://www.pronamic.eu/plugins/pronamic-pay-easy-digital-downloads/
 * Description: Extend the Pronamic Pay plugin with Easy Digital Downloads support to receive payments through a variety of payment providers.
 *
 * Version: 4.4.0
 * Requires at least: 5.9
 * Requires PHP: 8.2
 *
 * Author: Pronamic
 * Author URI: https://www.pronamic.eu/
 *
 * Text Domain: pronamic-pay-easy-digital-downloads
 * Domain Path: /languages/
 *
 * License: GPL-3.0-or-later
 *
 * Requires Plugins: easy-digital-downloads
 * Depends: wp-pay/core
 *
 * GitHub URI: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoload.
 */
require_once __DIR__ . '/vendor/autoload_packages.php';

/**
 * Bootstrap.
 */
add_action(
	'init',
	function () {
		load_plugin_textdomain( 'pronamic-pay-easy-digital-downloads', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

\Pronamic\WordPress\Pay\Plugin::instance(
	[
		'file'             => __FILE__,
		'action_scheduler' => __DIR__ . '/packages/woocommerce/action-scheduler/action-scheduler.php',
	]
);

add_filter(
	'pronamic_pay_plugin_integrations',
	function ( $integrations ) {
		foreach ( $integrations as $integration ) {
			if ( $integration instanceof \Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads\Extension ) {
				return $integrations;
			}
		}

		$integrations[] = new \Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads\Extension();

		return $integrations;
	}
);

if ( class_exists( \Pronamic\WordPress\Pay\Gateways\Mollie\Integration::class ) ) {
	add_filter(
		'pronamic_pay_gateways',
		function ( $gateways ) {
			$gateways[] = new \Pronamic\WordPress\Pay\Gateways\Mollie\Integration();

			return $gateways;
		}
	);
}
