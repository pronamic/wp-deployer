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
	}
}
