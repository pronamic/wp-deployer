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
	 * Get entry for version.
	 * 
	 * @param string $version Version.
	 * @return ChangelogEntry|null
	 */
	public function get_entry( $version ) {
		$search = '[' . $version . ']';

		$start   = null;
		$end     = null;
		$heading = null;

		foreach ( $this->data as $i => $line ) {
			if ( ! \str_starts_with( $line, '## ' ) ) {
				continue;
			}

			if ( null !== $start ) {
				$end = $i;

				break;
			}

			if ( null === $start && \str_contains( $line, $search ) ) {
				$title = $line;
				$start = $i;
			}
		}

		if ( null === $start ) {
			return null;
		}

		if ( null === $end ) {
			return null;
		}

		$body = array_slice( $this->data, $start + 1, $end - $start - 1 );

		$entry = new ChangelogEntry( $this, $version );

		$entry->body = trim( implode( '', $body ) );

		return $entry;
	}

	/**
	 * Has entry.
	 * 
	 * @param string $version Version.
	 * @return bool
	 */
	public function has_entry( $version ) {
		$search = '[' . $version . ']';

		foreach ( $this->data as $line ) {
			if ( ! str_starts_with( $line, '## ' ) ) {
				continue;
			}

			if ( str_contains( $line, $search ) ) {
				return true;
			}
		}

		return false;
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
