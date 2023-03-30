<?php
/**
 * Changelog entry test
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\Deployer
 */

namespace Pronamic\Deployer;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Changelog entry test class
 */
class ChangelogEntryTest extends TestCase {
	/**
	 * Test render.
	 * 
	 * @return void
	 */
	public function test_render() {
		$file = __DIR__ . '/../plugin/CHANGELOG.md';

		$changelog = new Changelog( $file );

		$changelog_entry = $changelog->new_entry( 'test' );

		$changelog_entry->body = 'Test';

		$render = $changelog_entry->render();

		$this->assertStringStartsWith( '## [test]', $render );

		$lines = explode( "\n", $render );

		$count = \count( $lines );

		$this->assertEquals( '', $lines[ $count - 4 ] );
		$this->assertStringStartsWith( 'Full set of changes: ', $lines[ $count - 3 ] );
		$this->assertEquals( '', $lines[ $count - 2 ] );
		$this->assertStringStartsWith( '[test]: ', $lines[ $count - 1 ] );
	}
}
