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
use Symfony\Component\Console\Input\InputOption;
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
						new InputOption(
							'working-dir',
							null,
							InputOption::VALUE_REQUIRED,
							'The working directory.',
							'./'
						),
						new InputOption(
							'build-dir',
							null,
							InputOption::VALUE_REQUIRED,
							'The build directory.',
							'./build/plugin'
						),
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

		$working_dir = $input->getOption( 'working-dir' );
		$build_dir   = $input->getOption( 'build-dir' );

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

		// Project.
		$project = new WpProject( $working_dir );

		$io->title( 'Build' );

		// Build - Sync.
		$io->section( 'Rsync' );

		$io->text( 'The build command uses <fg=green>rsync</fg=green> to copy files from the working directory to the build directory.' );

		$options = [
			'--recursive',
			'--delete',
			'--verbose',
			'--exclude-from=' . $exclude_file,
		];

		if ( \is_readable( $working_dir . '/.pronamic-build-ignore' ) ) {
			$options[] = '--exclude-from=' . Path::makeRelative(
				$working_dir . '/.pronamic-build-ignore',
				getcwd()
			);
		}

		$command = [
			'rsync',
			...$options,
			$working_dir . '/',
			$build_dir . '/',
		];

		$process = new Process( $command );

		$helper->mustRun( $output, $process );

		// Composer.
		$io->section( 'Composer' );

		$io->text( 'The build command installs the required Composer libraries.' );

		$command = \sprintf(
			'composer install --no-dev --prefer-dist --optimize-autoloader --working-dir=%s',
			$build_dir
		);

		$process = Process::fromShellCommandline( $command );

		$helper->mustRun( $output, $process );

		// Text domain fixer.
		$io->section( 'I18n text domain fixer tool' );

		$io->text( 'The build command runs the WordPress Coding Standards <fg=green>I18nTextDomainFixer</fg=green> tool.' );

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
		$io->section( 'Slug' );

		$io->text( 'The build command tries to determine the <info>slug</info> of the plugin or theme.' );

		$slug = $project->get_slug();

		if ( empty( $slug ) ) {
			$io->note( 'The slug could not be determined, define it in <fg=green>composer.json</fg=green> <fg=green>config.wp-slug</fg=green>.' );
		}

		// WP-CLI.
		$bin_wp = $bin_dir . '/wp';

		// Make POT.
		if ( file_exists( $bin_wp ) ) {
			$io->section( 'Create a POT file' );

			$io->text( 'The build command uses <info>WP-CLI</info> to create a POT file via <fg=green>wp i18n make-pot</fg=green>.' );

			$command = [
				$bin_wp,
				'i18n',
				'make-pot',
				$build_dir,
			];

			if ( $slug !== null ) {
				$command[] = '--slug=' . $slug;
			}

			$process = new Process( $command );

			$helper->mustRun( $output, $process );
		}

		// Distribution archive.
		if ( file_exists( $bin_wp ) ) {
			$io->section( 'Create a distribution archive' );

			$io->text( 'The build command uses <info>WP-CLI</info> to create a distribution archive via <fg=green>wp dist-archive</fg=green>.' );

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
