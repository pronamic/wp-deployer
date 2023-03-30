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
	/**
	 * Changelog.
	 * 
	 * @var Changelog
	 */
	public $changelog;

	/**
	 * Version.
	 * 
	 * @var string
	 */
	public $version;

	/**
	 * Previous version.
	 * 
	 * @var string
	 */
	public $version_previous = '';

	/**
	 * Date.
	 * 
	 * @var DateTimeImmutable
	 */
	public $date;

	/**
	 * Body.
	 * 
	 * @var string
	 */
	public $body = '';

	/**
	 * URL.
	 * 
	 * @var string
	 */
	public $url;

	/**
	 * Construct changelog entry.
	 * 
	 * @param Changelog $changelog Changelog.
	 * @param string    $version   Version.
	 */
	public function __construct( $changelog, $version ) {
		$this->changelog = $changelog;
		$this->version   = $version;
		$this->date      = new DateTimeImmutable();
	}

	/**
	 * Render.
	 * 
	 * @return string
	 */
	public function render() {
		$lines = [
			'## [' . $this->version . '] - ' . $this->date->format( 'Y-m-d' ),
			'',
			\trim( $this->body ),
			'',
			'Full set of changes: [`' . $this->get_version_compare() . '`][' . $this->version . ']',
			'',
			'[' . $this->version . ']: ' . $this->get_link()
		];

		return \implode( "\n", $lines );
	}

	/**
	 * Get lines.
	 * 
	 * @return string[]
	 */
	public function get_lines() {
		return explode( "\n", $this->body );
	}

	/**
	 * Get version compare.
	 * 
	 * @return string
	 */
	public function get_version_compare() {
		if ( '' === $this->version_previous ) {
			return $this->version;
		}

		return $this->version_previous . '...' . $this->version;
	}

	/**
	 * Get link.
	 * 
	 * @return string
	 */
	public function get_link() {
		if ( '' === $this->version_previous ) {
			return $this->url . '/releases/tag/v' . $this->version;
		}

		return $this->url . '/compare/v' . $this->version_previous . '...v' . $this->version;
	}
}
