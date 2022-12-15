<?php
/**
 * Git commit
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\Deployer
 */

namespace Pronamic\Deployer;

/**
 * Git commit class
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class GitCommit {
	public $hash;

	public $title_line;

	/**
	 * Construct git commit.
	 * 
	 * @param string $hash Hash.
	 */
	public function __construct( $hash ) {
		$this->hash = $hash;
	}
}
