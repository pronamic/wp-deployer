<?php
/**
 * WordPress project
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\Deployer
 */

namespace Pronamic\Deployer;

/**
 * WordPress project
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class WpProject {
	private $directory;

	public function __construct( $directory ) {
		$this->directory = $directory;
	}

	public function get_slug() {
		$slug = null;

		$composer_json_file = $this->directory . '/composer.json';

		if ( \is_readable( $composer_json_file ) ) {
			$data = \file_get_contents( $composer_json_file );

			$composer_json = \json_decode( $data );

			if ( isset( $composer_json->config->{'wp-slug'} ) ) {
				$slug = $composer_json->config->{'wp-slug'};
			}
		}

		return $slug;
	}

	public function get_version() {
		$version = null;

		$package_json_file = $this->directory . '/package.json';

		if ( \is_readable( $package_json_file ) ) {
			$data = \file_get_contents( $package_json_file );

			$package_json = \json_decode( $data );

			if ( isset( $package_json->version ) ) {
				$version = $package_json->version;
			}
		}

		return $version;
	}

	public function get_changelog() {
		$changelog_file = $this->directory . '/CHANGELOG.md';

		$changelog = new Changelog( $changelog_file );

		return $changelog;
	}
}
