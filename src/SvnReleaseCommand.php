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
class SvnReleaseCommand extends Command {
	/**
	 * Configure.
	 */
	protected function configure() {
		$this
			->setName( 'svn-release' )
			->setDescription( 'Subversion release.' )
			->setDefinition(
				new InputDefinition(
					[
						new InputArgument( 'working-dir', InputArgument::REQUIRED ),
						new InputArgument( 'svn-dir', InputArgument::REQUIRED ),
						new InputArgument( 'svn-url', InputArgument::REQUIRED ),
						new InputArgument( 'version', InputArgument::REQUIRED ),
					]
				)
			);

		$this->addOption(
			'username',
			null,
			InputOption::VALUE_OPTIONAL,
			'Subversion username'
		);

		$this->addOption(
			'password',
			null,
			InputOption::VALUE_OPTIONAL,
			'Subversion password'
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

		$working_dir = $input->getArgument( 'working-dir' );
		$svn_dir     = $input->getArgument( 'svn-dir' );
		$svn_url     = $input->getArgument( 'svn-url' );
		$version     = $input->getArgument( 'version' );

		$io->title( 'Subversion release' );

		// Authentication.
		$username = $input->getOption( 'username' );
		$password = $input->getOption( 'password' );

		$svn_auth = '--no-auth-cache';

		if ( ! empty( $username )  ) {
			$svn_auth .= '--username ' . $username;
		}

		if ( ! empty( $password ) ) {
			$svn_auth .= '--password ' . $oassword;
		}

		$io->table(
			[
				'Key',
				'Value',
			],
			[
				[ 'Working Directory', $working_dir ],
				[ 'Subversion Directory', $svn_dir ],
				[ 'Subversion URL', $svn_url ],
				[ 'Subversion Username', $username ],
				[ 'Subversion Password', $password ],
				[ 'Version', $version ],
			]
		);

		$io->section( 'Subversion' );

		// Checkout.
		if ( ! is_dir( $svn_dir ) ) {
			$command = sprintf(
				'svn checkout %s %s --depth immediates',
				$svn_url,
				$svn_dir
			);

			$process = Process::fromShellCommandline( $command );
			$process->setTty( true );

			$helper->mustRun( $output, $process );
		}

		// Subversion - Trunk.
		$command = sprintf(
			'svn update %s --set-depth infinity',
			$svn_dir . '/trunk'
		);

		$process = Process::fromShellCommandline( $command );
		$process->setTimeout( null );

		$helper->mustRun( $output, $process );

		// Subversion - Check tag.
		$command = sprintf(
			'svn info %s',
			$svn_url . '/tags/' . $version
		);

		$process = Process::fromShellCommandline( $command );

		$helper->run( $output, $process );

		$result = $process->getOutput();

		if ( ! empty( $result ) ) {
			$io->error( 'Subversion tag already exists.' );

			return 1;
		}

		// Subversion - Sync trunk.
		$command = sprintf(
			'rsync --recursive --delete --verbose %s %s',
			$working_dir . '/',
			$svn_dir . '/trunk/'
		);

		$process = Process::fromShellCommandline( $command );

		$helper->mustRun( $output, $process );

		// Subversion - Delete.
		$commands = [
			sprintf(
				'svn status %s',
				$svn_dir . '/trunk/'
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
				$svn_dir . '/trunk/'
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
			$svn_dir . '/trunk/',
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

		return 0;
	}
}
