<?php
/**
 * Changelog entry
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\Deployer
 */

namespace Pronamic\Deployer;

use DateTimeImmutable;

/**
 * Changelog entry class
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class ChangelogEntry {
	public $changelog;

	public $version;

	public $version_previous = '';

	public $date;

	public $body = '';

	public $commits = [];

	public $url;

	/**
	 * Construct changelog entry.
	 * 
	 * @param string $version Version.
	 */
	public function __construct( $changelog, $version ) {
		$this->changelog = $changelog;
		$this->version   = $version;
		$this->date      = new DateTimeImmutable();
	}

	public function render() {
		\ob_start();

		$changelog_entry = $this;

		include __DIR__ . '/../templates/changelog-entry.php';

		$output = ob_get_clean();

		return $output;
	}

	public function get_version_compare() {
		if ( '' === $this->version_previous ) {
			return $this->version;
		}

		return $this->version_previous . '...' . $this->version;
	}

	public function get_link() {
		if ( '' === $this->version_previous ) {
			return $this->url . '/releases/tag/' . $this->version;
		}

		return $this->url . '/compare/' . $this->version_previous . '...' . $this->version;
	}
}
