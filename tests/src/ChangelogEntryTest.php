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

		$changelog_entry = $changelog->new_entry( '1.0.0' );

		$changelog_entry->body = '- Test.';

		$render = $changelog_entry->render();

		$expected = <<<END
		## [1.0.0] - 2023-03-30

		- Test.

		Full set of changes: [`1.0.0`][1.0.0]

		[1.0.0]: /releases/tag/v1.0.0
		END;

		$expected = \str_replace( '2023-03-30', \date( 'Y-m-d' ), $expected );

		$this->assertEquals( $expected, $render );
	}
}
