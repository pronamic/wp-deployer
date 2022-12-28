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
	/**
	 * Test get entry.
	 * 
	 * @return void
	 */
	public function test_get_entry() {
		$file = __DIR__ . '/../plugin/CHANGELOG.md';

		$changelog = new Changelog( $file );

		$item = $changelog->get_entry( '1.1.0' );

		$this->assertNotNull( $item );
	}

	/**
	 * Test has last entry.
	 *
	 * @return void
	 */
	public function test_has_last_entry() {
		$file = __DIR__ . '/../plugin/CHANGELOG.md';

		$changelog = new Changelog( $file );

		$has_entry = $changelog->has_entry( '0.0.1' );

		$this->assertTrue( $has_entry );
	}
}
