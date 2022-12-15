<?php
/**
 * Semantic version
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\Deployer
 */

namespace Pronamic\Deployer;

/**
 * Semantic version class
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class SemanticVersion {
	private $major;

	private $minor;

	private $patch;

	public function __construct( $value ) {
		$version_core = null;
		$build        = null;

		$position_plus = strpos( $value, '+' );

		if ( false !== $position_plus ) {
			$value = substr( $value, 0, $position_plus );
			$build = substr( $value, $position_plus + 1 );
		}

		$position_dash = strpos( $value, '-' );

		if ( false !== $position_dash ) {
			$version_core = substr( $value, 0, $position_dash );
			$pre_release  = substr( $value, $position + 1 );
		}

		if ( false === $position_dash ) {
			$version_core = $value;
		}

		$parts = explode( '.', $version_core );

		if ( 3 !== count( $parts ) ) {
			throw new \Exception( 'Invalid semantic versioning.' );
		}

		$this->major = (int) $parts[0];
		$this->minor = (int) $parts[1];
		$this->patch = (int) $parts[2];
	}

	public function inc( $release ) {
		switch ( $release ) {
			case 'major':
				return \sprintf(
					'%s.%s.%s',
					$this->major + 1,
					0,
					0
				);
			case 'minor':
				return \sprintf(
					'%s.%s.%s',
					$this->major,
					$this->minor + 1,
					0
				);
			case 'patch':
				return \sprintf(
					'%s.%s.%s',
					$this->major,
					$this->minor,
					$this->patch + 1
				);
			default:
				throw new \Exception( 'Unknow increment release type.' );
		}
	}
}
