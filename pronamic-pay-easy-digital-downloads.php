<?php
/**
 * Plugin Name: Pronamic Pay Easy Digital Downloads Add-On
 * Plugin URI: https://www.pronamic.eu/plugins/pronamic-pay-easy-digital-downloads/
 * Description: Extend the Pronamic Pay plugin with Easy Digital Downloads support to receive payments through a variety of payment providers.
 *
 * Version: 4.3.6
 * Requires at least: 5.9
 * Requires PHP: 7.4
 *
 * Author: Pronamic
 * Author URI: https://www.pronamic.eu/
 *
 * Text Domain: pronamic-pay-easy-digital-downloads
 * Domain Path: /languages/
 *
 * License: GPL-3.0-or-later
 *
 * Requires Plugins: pronamic-ideal, easy-digital-downloads
 * Depends: wp-pay/core
 *
 * GitHub URI: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads
 */

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
