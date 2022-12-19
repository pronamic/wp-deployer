<?php
/**
 * File headers
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\Deployer
 */

namespace Pronamic\Deployer;

/**
 * File headers class
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class FileHeaders {
	/**
	 * Get headers.
	 * 
	 * @param string $file Filename.
	 * @return array<string, string>
	 * @throws \Exception Throws an exception when file contents cannot be retrieved.
	 */
	public function get_headers( $file ) {
		$file_data = \file_get_contents( $file, false, null, 0, 8 * 1024 );

		if ( false === $file_data ) {
			throw new \Exception( \sprintf( 'Could not read file: %s', $file ) );
		}

		$headers = [];

		$lines = \explode( "\n", $file_data );

		foreach ( $lines as $line ) {
			$colon_position = \strpos( $line, ':' );

			if ( false === $colon_position ) {
				continue;
			}

			$before = \substr( $line, 0, $colon_position );
			$after  = \substr( $line, $colon_position + 1 );

			$key   = $this->trim_key( $before );
			$value = $this->trim_value( $after );

			$headers[ $key ] = $value;
		}

		return $headers;
	}

	/**
	 * Trim file header key.
	 * 
	 * @param string $value Key value.
	 * @return string
	 */
	private function trim_key( $value ) {
		return \rtrim( \ltrim( $value, " \n\r\t\v\x00\/*#" ) );
	}

	/**
	 * Trim file header value.
	 * 
	 * @param string $value Value.
	 * @return string
	 */
	private function trim_value( $value ) {
		return \trim( $value );
	}

	/**
	 * Set headers in file.
	 * 
	 * @param string                $file    File.
	 * @param array<string, string> $headers Headers.
	 * @return void
	 * @throws \Exception Throws an exception when file contents cannot be retrieved.
	 */
	public function set_headers( $file, $headers ) {
		$lines = \file( $file );

		if ( false === $lines ) {
			throw new \Exception( \sprintf( 'Could not read file: %s', $file ) );
		}

		foreach ( $lines as $i => $line ) {
			$colon_position = \strpos( $line, ':' );

			if ( false === $colon_position ) {
				continue;
			}

			$before = \substr( $line, 0, $colon_position );
			$after  = \substr( $line, $colon_position + 1 );

			$key   = $this->trim_key( $before );
			$value = $this->trim_value( $after );

			if ( \array_key_exists( $key, $headers ) ) {
				$value_old = $value;
				$value_new = $headers[ $key ];

				$line = $before . ':' . \str_replace( $value_old, $value_new, $after );

				$lines[ $i ] = $line;
			}
		}

		\file_put_contents( $file, implode( '', $lines ) );
	}
}
