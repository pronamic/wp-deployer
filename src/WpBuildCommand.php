<?php
/**
 * WordPress build command
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\Deployer
 */

namespace Pronamic\Deployer;

use Acme\Command\DefaultCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

/**
 * WordPress build command
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class WpBuildCommand extends Command {
	/**
	 * Configure.
	 */
	protected function configure() {
		$this
			->setName( 'wp-build' )
			->setDescription( 'WordPress build.' )
			->setDefinition(
				new InputDefinition(
					[
						new InputArgument( 'working-dir', InputArgument::REQUIRED ),
						new InputArgument( 'build-dir', InputArgument::REQUIRED ),
					]
				)
			);
	}

	/**
	 * Execute.
	 *
	 * @param InputInterface  $input  Input interface.
	 * @param OutputInterface $output Output interface.
	 * @return int
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		global $_composer_bin_dir;

		$working_dir = $input->getArgument( 'working-dir' );
		$build_dir   = $input->getArgument( 'build-dir' );

		$bin_dir = Path::makeRelative(
			$_composer_bin_dir ?? __DIR__ . '/../vendor/bin',
			getcwd()
		);

		$io = new SymfonyStyle( $input, $output );

		$helper = $this->getHelper( 'process' );

		$filesystem = new Filesystem();

		$filesystem->mkdir( $build_dir );

		$exclude_file = Path::makeRelative(
			\realpath( __DIR__ . '/../exclude.txt' ),
			getcwd()
		);

		// Build - Sync.
		$command = sprintf(
			'rsync --recursive --delete --exclude-from=%s --verbose %s %s',
			$exclude_file,
			$working_dir . '/',
			$build_dir . '/'
		);

		$process = Process::fromShellCommandline( $command );

		$helper->mustRun( $output, $process );

		// Composer.
		$command = \sprintf(
			'composer install --no-dev --prefer-dist --optimize-autoloader --working-dir=%s',
			$build_dir
		);

		$process = Process::fromShellCommandline( $command );

		$helper->mustRun( $output, $process );

		// Text domain fixer.
		$bin_phpcbf = $bin_dir . '/phpcbf';

		if ( file_exists( $bin_phpcbf ) ) {
			$command = \sprintf(
				$bin_phpcbf . ' -s -v --sniffs=WordPress.Utils.I18nTextDomainFixer %s',
				$build_dir
			);

			$process = Process::fromShellCommandline( $command );

			/**
			 * PHP Code Beautifier will return exit-code 1 if all 'errors' are fixed.
			 * 
			 * @link https://github.com/squizlabs/PHP_CodeSniffer/issues/3057
			 * @link https://github.com/squizlabs/PHP_CodeSniffer/issues/2898
			 */			
			$helper->run( $output, $process );
		}

		// Slug.
		$slug = null;

		$composer_json_file = $working_dir . '/composer.json';

		$data = file_get_contents( $composer_json_file );

		$composer_json = json_decode( $data );

		if ( ! isset( $composer->config->{'wp-slug'} ) ) {
			$io->note( 'The `composer.json` file is missing a `config.wp-slug` property.' );
		} else {
			$slug = $composer->config->{'wp-slug'};
		}

		// Distribution archive.
		$bin_wp = $bin_dir . '/wp';

		if ( file_exists( $bin_wp ) ) {
			$command = [
				$bin_wp,
				'dist-archive',
				$build_dir,
			];

			if ( $slug !== null ) {
				$command[] = '--plugin-dirname=' . $slug;
			}

			$process = new Process( $command );

			$helper->mustRun( $output, $process );
		}

		return 0;
	}
}
