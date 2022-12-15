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

	public function generate_body() {
		$body = '';

		foreach ( $this->commits as $commit ) {
			$body .= '- ' . $this->change_present_to_past_tense( $commit->title_line ) . "\n";
		}

		return $body;
	}

	public function render() {
		\ob_start();

		$changelog_entry = $this;

		include __DIR__ . '/../templates/changelog-entry.php';

		$output = ob_get_clean();

		return $output;
	}

	public function change_present_to_past_tense( $text ) {
		$patterns = [
			'add'    => '/^Add /',
			'create' => '/^Create /',
			'fix'    => '/^Fix /',
			'update' => '/^Update /',
			'remove' => '/^Remove /',
		];

		$replacements = [
			'add'    => 'Added ',
			'create' => 'Created ',
			'fix'    => 'Fixed ',
			'update' => 'Updated ',
			'remove' => 'Removed ',
		];

		return \preg_replace( $patterns, $replacements, $text );
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
