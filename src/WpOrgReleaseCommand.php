<?php
/**
 * Deploy command
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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Deploy command
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class WpOrgReleaseCommand extends Command {
	/**
	 * Configure.
	 */
	protected function configure() {
		$this
			->setName( 'wp-org-release' )
			->setDescription( 'WordPress.org release.' )
			->setDefinition(
				new InputDefinition(
					[
						new InputArgument( 'working-dir', InputArgument::REQUIRED ),
						new InputArgument( 'svn-dir', InputArgument::REQUIRED ),
						new InputArgument( 'slug', InputArgument::REQUIRED ),
					]
				)
			);
	}

	/**
	 * Execute.
	 * 
	 * @param InputInterface  $input   Input interface.
	 * @param OutputInterface $output Output interface.
	 * @return int
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		$working_dir = $input->getArgument( 'working-dir' );
		$svn_dir     = $input->getArgument( 'svn-dir' );
		$slug        = $input->getArgument( 'slug' );
		$svn_url     = 'https://plugins.svn.wordpress.org/' . $slug;

		$file_headers = new FileHeaders();

		$plugins = [];

		foreach ( \glob( $working_dir . '/*.php' ) as $file ) {
			$headers = $file_headers->get_headers( $file );

			if ( \array_key_exists( 'Plugin Name', $headers ) ) {
				$plugins[ $file ] = $headers;
			}
		}

		if ( 1 !== count( $plugins ) ) {
			$io->error( 'Could not find WordPress plugin.' );

			return 1;
		}

		$version = null;

		foreach ( $plugins as $file => $headers ) {
			if ( \array_key_exists( 'Version', $headers ) ) {
				$version = $headers['Version'];
			}
		}

		if ( empty( $version ) ) {
			$io->error( 'No version in plugin header.' );

			return 1;
		}

		$command = $this->getApplication()->find( 'svn-release' );

		$command_arguments = [
			'working-dir' => $working_dir,
			'svn-dir'     => $svn_dir,
			'svn-url'     => $svn_url,
			'version'     => $version,
			'--username'  => \getenv( 'WP_ORG_USERNAME' ),
			'--password'  => \getenv( 'WP_ORG_PASSWORD' ),
		];

		$command_input = new ArrayInput( $command_arguments );

		return $command->run( $command_input, $output );
	}
}
