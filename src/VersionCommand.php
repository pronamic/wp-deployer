<?php
/**
 * Version command
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\Deployer
 */

namespace Pronamic\Deployer;

use Acme\Command\DefaultCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Version command
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class VersionCommand extends Command {
	/**
	 * Configure.
	 */
	protected function configure() {
		$this
			->setName( 'version' )
			->setDescription( 'Version.' );
	}

	/**
	 * Execute.
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		$io = new SymfonyStyle( $input, $output );

		$helper = $this->getHelper( 'process' );

		$filesystem = new Filesystem();

		$cwd = \getcwd();

		$composer_json_file = $cwd . '/composer.json';
		$package_json_file  = $cwd . '/package.json';

		/**
		 * Detect type.
		 * 
		 * @link https://getcomposer.org/doc/04-schema.md#type
		 * @link https://www.smashingmagazine.com/2019/03/composer-wordpress/
		 * @link https://easyengine.io/tutorials/composer-wordpress/manage-themes-plugins/
		 */
		$type = '';

		if ( is_readable( $composer_json_file ) ) {
			$data = file_get_contents( $composer_json_file );

			$composer_json = json_decode( $data );

			if ( ! property_exists( $composer_json, 'type' ) ) {
				$io->note( 'The `composer.json` file is missing a `type` property.' );
			}

			if ( property_exists( $composer_json, 'type' ) ) {
				$type = $composer_json->type;
			}
		}

		/**
		 * Detect version.
		 */
		$version = '';

		if ( is_readable( $package_json_file ) ) {
			$data = file_get_contents( $package_json_file );

			$package_json = json_decode( $data );

			if ( ! property_exists( $package_json, 'version' ) ) {
				$io->note( 'The `package.json` file is missing a `version` property.' );
			}

			if ( property_exists( $package_json, 'version' ) ) {
				$version = $package_json->version;
			}
		}

		$io->title( 'Version' );

    	$helper = $this->getHelper( 'question' );

    	/**
    	 * New version.
    	 * 
    	 * @link https://docs.npmjs.com/cli/v8/commands/npm-version#description
    	 * @link https://github.com/npm/node-semver#functions
    	 */
    	$bump_method = $io->choice( 'Select bump methpd', [
    		'input',
			'major',
			'minor',
			'patch',
			'premajor',
			'preminor',
			'prepatch',
			'prerelease',
			'from-git',
		], 'patch' );

		switch ( $bump_method ) {
			case 'input':
				$new_version = $io->ask( 'New version?' );

				break;
			case 'major':
				$io->error( 'Bump method `major` not implemented.' );

				return 1;
			case 'minor':
				$io->error( 'Bump method `minor` not implemented.' );

				return 1;
			case 'patch':
				$io->error( 'Bump method `patch` not implemented.' );

				return 1;
			case 'premajor':
				$io->error( 'Bump method `premajor` not implemented.' );

				return 1;
			case 'preminor':
				$io->error( 'Bump method `preminor` not implemented.' );

				return 1;
			case 'prepatch':
				$io->error( 'Bump method `prepatch` not implemented.' );

				return 1;
			case 'prerelease':
				$io->error( 'Bump method `prerelease` not implemented.' );

				return 1;
			case 'from-git':
				$io->error( 'Bump method `from-git` not implemented.' );

				return 1;
			default:
				$new_version = $bump_method;

				break;
		}

		$io->section( 'Details' );

		$io->table(
			array(
				'Key',
				'Value',
			),
			array(
				array( 'Type', $type ),
				array( 'Version', $version ),
				array( 'New Version', $new_version ),
			)
		);

		/**
		 * If type = wordpress-plugin update plugin file.
		 * How do we find the main plugin file?
		 * Check of version header value exists?
		 * 
		 * @link https://github.com/WordPress/gutenberg/search?q=pluginEntryPoint
		 * @link https://docs.npmjs.com/cli/v8/configuring-npm/package-json#man
		 */

		/**
		 * If type = wordpress-theme update style.css file.
		 *
		 * Check of version header value exists?
		 */

		/**
		 * If type = wordpress-plugin or type = wordpress-theme
		 * and readme.txt exists patch readme.txt.
		 * 
		 * Check if "Stable tag" exists?
		 */

		/**
		 * If CHANGELOG.md check if new version is part of it?
		 */

		/**
		 * If changelog is missing add commits / pull requests to CHANGELOG.md?
		 * 
		 * Ask user to manual check the changelog?
		 * 
		 * Confirm the changelog.
		 */

		/**
		 * If readme.txt check if new version is in changelog section?
		 */

		/**
		 * If changelog is missing patch it from CHANGELOG.md to readm.txt.
		 * 
		 * Confirm the changelog.
		 */

		/**
		 * Check if "Requires PHP" matches composer PHP requrement.
		 * 
		 * @link https://mikemadison.net/blog/2020/11/17/configuring-php-version-with-composer
		 */

		/**
		 * If package.json exists use `npm version`?
		 * Or `npm pkg set $new_version`?
		 */

		/**
		 * GitHub CLI, create concept release?
		 * 
		 * @link https://cli.github.com/
		 */
	}
}
