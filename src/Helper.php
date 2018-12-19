<?php
/**
 * Helper
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\Deployer
 */

namespace Pronamic\Deployer;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Helper
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class Helper {
	/**
	 * Get GNU xargs.
	 *
	 * @param
	 * @param
	 * @return string|false
	 */
	public static function get_gnu_xargs( $helper, OutputInterface $output ) {
		$options = array(
			'xargs',
			'gxargs',
		);

		foreach ( $options as $option ) {
			$process = new Process( $option . ' --version' );

			$helper->run( $output, $process );

			$result = $process->getOutput();

			if ( false !== strpos( $result, 'GNU findutils' ) ) {
				return $option;
			}
		}

		return false;
	}

	/**
	 * Get GNU grep.
	 *
	 * @param
	 * @param
	 * @return string|false
	 */
	public static function get_gnu_grep( $helper, OutputInterface $output ) {
		$options = array(
			'grep',
			'ggrep',
		);

		foreach ( $options as $option ) {
			$process = new Process( $option . ' --version' );

			$helper->run( $output, $process );

			$result = $process->getOutput();

			if ( false !== strpos( $result, 'GNU grep' ) ) {
				return $option;
			}
		}

		return false;
	}
}
