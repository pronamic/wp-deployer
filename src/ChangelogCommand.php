<?php
/**
 * Changelog command
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
use Symfony\Component\Process\Process;

/**
 * Changelog command
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class ChangelogCommand extends Command {
	/**
	 * Configure.
	 */
	protected function configure() {
		$this
			->setName( 'changelog' )
			->setDescription( 'Changelog.' )
			->setDefinition(
				new InputDefinition(
					[
						new InputArgument( 'version', InputArgument::REQUIRED ),
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
		$io = new SymfonyStyle( $input, $output );

		if ( ! is_readable( 'CHANGELOG.md' ) ) {
			$io->error( 'Cannot read CHANGELOG.md file.' );

			return 1;
		}

		$version = $input->getArgument( 'version' );

		$changelog = new Changelog( 'CHANGELOG.md' );

		$entry = $changelog->get_entry( $version );

		if ( null === $entry ) {
			$io->error( 'Cannot find entry for version.' );

			return 1;
		}

		$output->write( $entry->body );

		return 0;
	}
}
