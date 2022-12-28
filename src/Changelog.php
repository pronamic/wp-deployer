<?php
/**
 * Changelog
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\Deployer
 */

namespace Pronamic\Deployer;

/**
 * Changelog class
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class Changelog {
	/**
	 * File.
	 * 
	 * @var string
	 */
	public $file;

	/**
	 * Data.
	 * 
	 * @var string[]
	 */
	public $data;

	/**
	 * Construct changelog entry.
	 * 
	 * @param string $file File.
	 */
	public function __construct( $file ) {
		$this->file = $file;

		$this->data = \file( $file );
	}

	/**
	 * New entry for version.
	 * 
	 * @param string $version Version.
	 * @return ChangelogEntry
	 */
	public function new_entry( $version ) {
		return new ChangelogEntry( $this, $version );
	}

	/**
	 * Get changelog entries.
	 *
	 * @return ChangelogEntry[]
	 */
	public function get_entries() {
		$entries    = [];
		$start      = null;
		$version    = null;
		$empty_line = null;
		$last_line  = array_key_last( $this->data );

		foreach ( $this->data as $i => $line ) {
			if ( '' === trim( $line ) ) {
				$empty_line = $i;
			}

			if ( ! \str_starts_with( $line, '## ' ) && $i !== $last_line ) {
				continue;
			}

			if ( \str_contains( $line, 'Unreleased' ) ) {
				continue;
			}

			if ( null !== $start && null !== $version ) {
				$end = $i - 1;

				if ( $i === $last_line ) {
					if ( \str_starts_with( $line, '[' . $version . ']' ) ) {
						$end = $i;
					} else if ( null !== $empty_line ) {
						$end = $empty_line;
					}
				}

				$body = array_slice( $this->data, $start + 1, $end - $start );

				$entry = new ChangelogEntry( $this, $version );

				$entry->body = trim( implode( '', $body ) );

				$entries[ $version ] = $entry;
			}

			$start = $i;

			// Version.
			$version = null;

			$version_start = \strpos( $line, '[' );
			$version_end   = \strpos( $line, ']' );

			if ( false !== $version_start && null !== $version_end ) {
				$version = trim( \substr( $line, $version_start + 1, $version_end - $version_start - 1 ) );
			}
		}

		return $entries;
	}

	/**
	 * Get entry for version.
	 * 
	 * @param string $version Version.
	 * @return ChangelogEntry|null
	 */
	public function get_entry( $version ) {
		$entries = $this->get_entries();

		if ( ! \array_key_exists( $version, $entries ) ) {
			return null;
		}

		return $entries[ $version ];
	}

	/**
	 * Has entry.
	 * 
	 * @param string $version Version.
	 * @return bool
	 */
	public function has_entry( $version ) {
		$entries = $this->get_entries();

		$has_entry = \array_key_exists( $version, $entries );

		return $has_entry;
	}

	/**
	 * Get insert position.
	 * 
	 * @return int
	 */
	public function get_insert_position() {
		$position = 0;

		foreach ( $this->data as $i => $line ) {
			$position = $i;

			if ( ! str_starts_with( $line, '## ' ) ) {
				continue;
			}

			if ( str_contains( $line, '[Unreleased]' ) ) {
				continue;
			}

			return $position;
		}

		return $position;
	}

	/**
	 * Insert.
	 * 
	 * @param int    $position Position.
	 * @param string $content  Content.
	 * @return void
	 */
	public function insert( $position, $content ) {
		\array_splice( $this->data, $position, 0, $content );
	}

	/**
	 * Save.
	 * 
	 * @return void
	 */
	public function save() {
		file_put_contents( $this->file, implode( '', $this->data ) );
	}
}
