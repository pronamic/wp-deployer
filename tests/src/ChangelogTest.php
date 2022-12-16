<?php
/**
 * Changelog test
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\Deployer
 */

namespace Pronamic\Deployer;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Changelog test class
 */
class ChangelogTest extends TestCase {
	public function test_get_entry() {
		$file = __DIR__ . '/../plugin/CHANGELOG.md';

		$changelog = new Changelog( $file );

		$item = $changelog->get_entry( '1.1.0' );

		var_dump( $item->body );
	}
}
