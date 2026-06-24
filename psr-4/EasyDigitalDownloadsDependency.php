<?php

declare(strict_types=1);

/**
 * Easy Digital Downloads Dependency
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads
 */

namespace Pronamic\WordPress\Pay\Extensions\EasyDigitalDownloads;

use Pronamic\WordPress\Pay\Dependencies\Dependency;

/**
 * Easy Digital Downloads Dependency
 *
 * @version 2.1.0
 * @since   2.1.0
 */
final class EasyDigitalDownloadsDependency extends Dependency {
	/**
	 * Is met.
	 *
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.22/easy-digital-downloads.php#L209
	 * @return bool True if dependency is met, false otherwise.
	 */
	#[\Override]
	public function is_met() {
		return \defined( '\EDD_VERSION' );
	}
}
