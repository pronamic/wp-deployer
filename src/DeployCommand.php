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
class DeployCommand extends Command {
	/**
	 * Configure.
	 */
	protected function configure() {
		$this
			->setName( 'deploy' )
			->setDescription( 'Deploy.' )
			->setDefinition(
				new InputDefinition(
					[
						new InputArgument( 'slug', InputArgument::REQUIRED ),
						new InputArgument( 'git', InputArgument::REQUIRED ),
						new InputArgument( 'main_file', InputArgument::OPTIONAL ),
					]
				)
			);

		$this->addOption(
			'branch',
			null,
			InputOption::VALUE_REQUIRED,
			'Which branch do you want to use?',
			'main'
		);

		$this->addOption(
			'to-wp-org',
			null,
			InputOption::VALUE_NONE,
			'Do you want to publish to WordPress.org?'
		);

		$this->addOption(
			'to-s3',
			null,
			InputOption::VALUE_NONE,
			'Do you want to publish to S3?'
		);

		$this->addOption(
			'non-interactive',
			null,
			InputOption::VALUE_NONE,
			'Is user interaction possible?'
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
		$io = new SymfonyStyle( $input, $output );

		$helper = $this->getHelper( 'process' );

		$filesystem = new Filesystem();

		$xargs = Helper::get_gnu_xargs( $helper, $output );

		if ( false === $xargs ) {
			$io->error( 'Could not find GNU `xargs`, maybe try `brew install findutils`.' );

			return 1;
		}

		$grep = Helper::get_gnu_grep( $helper, $output );

		if ( false === $grep ) {
			$io->error( 'Could not find GNU `grep`, maybe try `brew install grep`.' );

			return 1;
		}

		$cut = Helper::get_gnu_cut( $helper, $output );

		if ( false === $cut ) {
			$io->error( 'Could not find GNU `cut`, maybe try `brew install coreutils`.' );

			return 1;
		}

		$tr = Helper::get_gnu_tr( $helper, $output );

		if ( false === $tr ) {
			$io->error( 'Could not find GNU `tr`, maybe try `brew install coreutils`.' );

			return 1;
		}

		$slug      = $input->getArgument( 'slug' );
		$git       = $input->getArgument( 'git' );
		$main_file = $input->getArgument( 'main_file' );

		$relative_path_git   = 'deploy/git/' . $slug;
		$relative_path_build = 'deploy/build/' . $slug;
		$relative_path_zip   = 'deploy/zip/' . $slug;
		$relative_path_svn   = 'deploy/svn/' . $slug;

		$filesystem->mkdir( $relative_path_git );
		$filesystem->mkdir( $relative_path_build );
		$filesystem->mkdir( $relative_path_zip );
		$filesystem->mkdir( $relative_path_svn );

		$branch          = $input->getOption( 'branch' );
		$to_wp_org       = $input->getOption( 'to-wp-org' );
		$to_s3           = $input->getOption( 'to-s3' );
		$non_interactive = $input->getOption( 'non-interactive' );

		if ( empty( $main_file ) ) {
			$main_file = sprintf( '%s.php', $slug );
		}

		$svn_url = sprintf(
			'https://plugins.svn.wordpress.org/%s',
			$slug
		);

		$io->title( sprintf( 'Deploy `%s`', $slug ) );

		$io->table(
			[
				'Key',
				'Value',
			],
			[
				[ 'Slug', $slug ],
				[ 'Git', $git ],
				[ 'Main file', $main_file ],
				[ 'SVN URL', $svn_url ],
				[ 'SVN path', $relative_path_svn ],
				[ 'Git path', $relative_path_git ],
				[ 'Build path', $relative_path_build ],
				[ 'ZIP path', $relative_path_zip ],
			]
		);

		if ( ! $non_interactive ) {
			$result = $io->confirm( 'OK?', true );

			if ( ! $result ) {
				return;
			}
		}

		// Git.
		$io->section( 'Git' );

		if ( ! is_dir( $relative_path_git . '/.git' ) ) {
			$command = sprintf(
				'git clone %s %s',
				$git,
				$relative_path_git
			);

			$process = Process::fromShellCommandline( $command );

			$helper->mustRun( $output, $process );
		}

		$process = new Process( [ 'git', 'pull' ], $relative_path_git );

		$helper->mustRun( $output, $process );

		$process = new Process( [ 'git', 'checkout', $branch ], $relative_path_git );

		$helper->mustRun( $output, $process );

		$process = new Process( [ 'git', 'pull' ], $relative_path_git );

		$helper->mustRun( $output, $process );

		// Version.
		$io->section( 'Version' );

		$commands = [
			sprintf(
				$grep . ' -i "Version:" %s',
				$relative_path_git . '/' . $main_file
			),
			sprintf(
				"awk -F '%s' '%s'",
				' ',
				'{print $NF}'
			),
			sprintf(
				$tr . " -d '%s'",
				'\r'
			),
		];

		$command = implode( ' | ', $commands );

		$process = Process::fromShellCommandline( $command );

		$helper->mustRun( $output, $process );

		$version_main_file = trim( $process->getOutput() );

		$commands = [
			sprintf(
				$grep . ' -i "Stable tag:" %s',
				$relative_path_git . '/readme.txt'
			),
			sprintf(
				"awk -F '%s' '%s'",
				' ',
				'{print $NF}'
			),
			sprintf(
				$tr . " -d '%s'",
				'\r'
			),
		];

		$command = implode( ' | ', $commands );

		$process = Process::fromShellCommandline( $command );

		$helper->mustRun( $output, $process );

		$version_readme_txt = trim( $process->getOutput() );

		$io->table(
			[
				'File',
				'Version',
			],
			[
				[
					'readme.txt',
					$version_readme_txt,
				],
				[
					$main_file,
					$version_main_file,
				],
			]
		);

		if ( $version_readme_txt !== $version_main_file ) {
			$io->error(
				sprintf(
					'Version in readme.txt & %s don\'t match. Exiting…',
					$main_file
				)
			);

			return 1;
		}

		$version = $version_main_file;

		if ( empty( $version ) ) {
			$io->error( 'Version is empty. Exiting…' );

			return 1;
		}

		if ( ! $non_interactive ) {
			$result = $io->confirm( 'OK?', true );

			if ( ! $result ) {
				return;
			}
		}

		// Composer.
		if ( is_readable( $relative_path_git . '/composer.json' ) ) {
			$command = 'composer install --no-dev --prefer-dist --optimize-autoloader';

			$process = Process::fromShellCommandline( $command, $relative_path_git );

			// To disable the timeout, set this value to null.
			$process->setTimeout( null );

			$helper->mustRun( $output, $process );
		}

		// Build - Empty build directory.
		$command = sprintf(
			'rm -r %s',
			$relative_path_build
		);

		$process = Process::fromShellCommandline( $command );

		$helper->mustRun( $output, $process );

		// Build - Create build directory.
		$command = sprintf(
			'mkdir %s',
			$relative_path_build
		);

		$process = Process::fromShellCommandline( $command );

		$helper->mustRun( $output, $process );

		// Build - Sync.
		$command = sprintf(
			'rsync --recursive --delete --exclude-from=exclude.txt --verbose %s %s',
			$relative_path_git . '/',
			$relative_path_build . '/'
		);

		$process = Process::fromShellCommandline( $command );

		$helper->mustRun( $output, $process );

		// ZIP.
		$io->section( 'ZIP' );

		$relative_file_zip = $relative_path_zip . '/' . $slug . '.' . $version . '.zip';

		$command = sprintf(
			'zip --recurse-paths %s %s',
			escapeshellarg( getcwd() . '/' . $relative_file_zip ),
			'' . $slug . '/*'
		);

		$process = Process::fromShellCommandline( $command, dirname( $relative_path_build ) );

		$helper->mustRun( $output, $process );

		// Subversion.
		if ( $to_wp_org ) {
			$io->section( 'WordPress.org SVN' );

			// Authentication.
			$env_wp_org_username = getenv( 'WP_ORG_USERNAME' );
			$env_wp_org_password = getenv( 'WP_ORG_PASSWORD' );

			$svn_auth = '';

			if ( ! empty( $env_wp_org_username ) && ! empty( $env_wp_org_password ) ) {
				$svn_auth = '--no-auth-cache --username $WP_ORG_USERNAME --password $WP_ORG_PASSWORD';
			}

			// Checkout.
			if ( ! is_dir( $relative_path_svn . '/.svn' ) ) {
				$command = sprintf(
					'svn checkout %s %s --depth immediates',
					$svn_url,
					$relative_path_svn
				);

				$process = Process::fromShellCommandline( $command );
				$process->setTty( true );

				$helper->mustRun( $output, $process );
			}

			// Subversion - Trunk.
			$command = sprintf(
				'svn update %s --set-depth infinity',
				$relative_path_svn . '/trunk'
			);

			$process = Process::fromShellCommandline( $command );

			// To disable the timeout, set this value to null.
			$process->setTimeout( null );

			$helper->mustRun( $output, $process );

			// Subversion - Assets.
			$command = sprintf(
				'svn update %s --set-depth infinity',
				$relative_path_svn . '/assets'
			);

			$process = Process::fromShellCommandline( $command );

			$helper->mustRun( $output, $process );

			// Subversion - Check tag.
			$command = sprintf(
				'svn info %s',
				$svn_url . '/tags/' . $version
			);

			$process = Process::fromShellCommandline( $command );

			$helper->run( $output, $process );

			$result = $process->getOutput();

			if ( empty( $result ) ) {
				$io->success( 'Subversion tag does not exists.' );

				// Subversion - Sync trunk.
				$command = sprintf(
					'rsync --recursive --delete --verbose %s %s',
					$relative_path_build . '/',
					$relative_path_svn . '/trunk/'
				);

				$process = Process::fromShellCommandline( $command );

				$helper->mustRun( $output, $process );

				// Subversion - Delete.
				$commands = [
					sprintf(
						'svn status %s',
						$relative_path_svn . '/trunk/'
					),
					$grep . " '^!'",
					$cut . ' -c 9-',
					$xargs . " -d '\\n' -i svn delete {}@",
				];

				$command = implode( ' | ', $commands );

				$process = Process::fromShellCommandline( $command );

				$helper->mustRun( $output, $process );

				// Subversion - Add.
				$commands = [
					sprintf(
						'svn status %s',
						$relative_path_svn . '/trunk/'
					),
					$grep . " '^?'",
					$cut . ' -c 9-',
					$xargs . " -d '\\n' -i svn add {}@",
				];

				$command = implode( ' | ', $commands );

				$process = Process::fromShellCommandline( $command );

				$helper->mustRun( $output, $process );

				// Subversion - Commit.
				$command = sprintf(
					"svn commit %s %s -m '%s'",
					$svn_auth,
					$relative_path_svn . '/trunk/',
					'Update'
				);

				$process = Process::fromShellCommandline( $command );
				$process->setTimeout( null );
				$process->setTty( true );

				$helper->mustRun( $output, $process );

				// Subversion - Tag.
				$command = sprintf(
					'svn cp %s %s %s -m "%s"',
					$svn_auth,
					$svn_url . '/trunk',
					$svn_url . '/tags/' . $version,
					sprintf(
						'Tagging version %s for release.',
						$version
					)
				);

				$process = Process::fromShellCommandline( $command );
				$process->setTty( true );

				$helper->mustRun( $output, $process );
			} else {
				$io->warning(
					sprintf(
						'Tag %s already exists on %s.',
						$version,
						$svn_url . '/tags/'
					)
				);
			}
		}

		// AWS S3.
		if ( $to_s3 ) {
			$io->section( 'AWS S3' );

			$s3_link = sprintf(
				's3://downloads.pronamic.eu/plugins/%s/%s.%s.zip',
				$slug,
				$slug,
				$version
			);

			$command = sprintf(
				'aws s3 cp %s %s --acl public-read',
				$relative_file_zip,
				$s3_link
			);

			$process = Process::fromShellCommandline( $command );

			// To disable the timeout, set this value to null.
			$process->setTimeout( null );

			$helper->mustRun( $output, $process );
		}

		return 0;
	}
}
