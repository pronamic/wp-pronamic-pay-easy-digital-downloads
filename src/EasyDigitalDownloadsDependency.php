<?php
/**
 * Easy Digital Downloads Dependency
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads
 */

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

use Pronamic\WordPress\Pay\Dependencies\Dependency;

/**
 * Easy Digital Downloads Dependency
 *
 * @author  Reüel van der Steege
 * @version 2.1.0
 * @since   2.1.0
 */
class EasyDigitalDownloadsDependency extends Dependency {
	/**
	 * Is met.
	 *
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.22/easy-digital-downloads.php#L209
	 * @return bool True if dependency is met, false otherwise.
	 */
	public function is_met() {
		return \defined( '\EDD_VERSION' );
	}
}
