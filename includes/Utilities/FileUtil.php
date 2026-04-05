<?php

namespace PinakaPosWp\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Class FileUtil
 *
 * @since 1.0.0
 * @package PinakaPosWp\Utilities
 */
class FileUtil {

	/**
	 * Get the instance of a file system.
	 *
	 * @since 1.0.0
	 * @return \WP_Filesystem_Base File system instance.
	 */
	public static function get_fs() {
		if ( empty( $GLOBALS['wp_filesystem'] ) ) {
			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();
			}
		}

		return $GLOBALS['wp_filesystem'];
	}

	/**
	 * Check if a file exists.
	 *
	 * @param string $file The file to check.
	 *
	 * @since 2.0.0
	 * @return bool True if the file exists, false otherwise.
	 */
	public static function file_exists( $file ) {
		// Strip any protocol/file wrappers.
		$file = self::sanitize_file_path( $file );
		if ( ! self::is_direct() ) {
			return file_exists( $file );
		}

		return self::get_fs()->exists( $file );
	}

	/**
	 * Open a file.
	 *
	 * @since 2.0.0
	 * @param string $file The file to open.
	 * @param string $mode The mode to open the file in.
	 * @return resource|bool The file resource on success, false on failure.
	 */
	public static function fopen( $file, $mode ) {
		$file = self::sanitize_file_path( $file );

		return @fopen( $file, $mode ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	}

	/**
	 * Get the filesize.
	 *
	 * @since 2.0.0
	 * @param string $file The file to get the size of.
	 * @return int|bool The file size on success, false on failure.
	 */
	public static function size( $file ) {
		$file = self::sanitize_file_path( $file );

		return self::get_fs()->size( $file );
	}

	/**
	 * Get the file contents as a string.
	 *
	 * Returns the file contents as a string. If you need the file contents as an array (such as for CSV files), use `file()` instead.
	 *
	 * @since 2.0.0
	 * @param string $file The file to get the contents of.
	 * @return string|bool The file contents on success, false on failure.
	 */
	public static function get_contents( $file ) {
		$file = self::sanitize_file_path( $file );
		return self::get_fs()->get_contents( $file );
	}

	/**
	 * Write contents to a file.
	 *
	 * @since 2.0.0
	 * @param string $file     The file to write to.
	 * @param string $contents The contents to write.
	 * @return int|bool The number of bytes written to the file on success, false on failure.
	 */
	public static function put_contents( $file, $contents ) {
		$file = self::sanitize_file_path( $file );
		return self::get_fs()->put_contents( $file, $contents );
	}

	/**
	 * Get the file contents as an array.
	 *
	 * Returns the file contents as an array. Each line in the file is an element in the array.
	 *
	 * @since 2.0.0
	 * @param string $file The file to get the contents of.
	 * @return array|bool The file contents as an array on success, false on failure.
	 */
	public static function file( $file ) {
		$file = self::sanitize_file_path( $file );
		if ( ! self::is_direct() ) {
			return file( $file );
		}

		return self::get_fs()->get_contents_array( $file );
	}

	/**
	 * Get the modified time of a file.
	 *
	 * @since 2.0.0
	 * @param string $file The file to get the modified time of.
	 * @return int|bool The modified time on success, false on failure.
	 */
	public static function filemtime( $file ) {
		$file = self::sanitize_file_path( $file );

		return self::get_fs()->mtime( $file );
	}

	/**
	 * Delete a file.
	 *
	 * @since 2.0.0
	 * @param string $file The file to delete.
	 * @return bool True on success, false on failure.
	 */
	public static function delete( $file ) {
		$file = self::sanitize_file_path( $file );

		return self::get_fs()->delete( $file );
	}

	/**
	 * Sanitize a file path.
	 *
	 * Removes potentially risky protocols from the file path.
	 *
	 * @param string $file The file path to sanitize.
	 *
	 * @since 2.0.0
	 * @return string The sanitized file path.
	 */
	public static function sanitize_file_path( $file ) {
		// If the file path doesn't have a protocol just return it.
		if ( false === strpos( $file, '://' ) && false === strpos( $file, rawurlencode( '://' ) ) ) {
			return $file;
		}

		$restricted_protocols = self::get_restricted_file_protocols();

		foreach ( $restricted_protocols as $protocol ) {
			// Create a case-insensitive pattern for each protocol.
			$pattern = '#^' . preg_quote( $protocol, '#' ) . '#i';
			$file    = preg_replace( $pattern, '', $file );
		}

		return $file;
	}

	/**
	 * Get the restricted file protocols.
	 *
	 * @since 2.0.0
	 * @return array The restricted file protocols.
	 */
	private static function get_restricted_file_protocols() {
		/**
		 * Filter the protocols that are restricted from file paths.
		 *
		 * @param array $protocols The protocols to restrict.
		 *
		 * @since 2.0.0
		 */
		$protocols = (array) apply_filters(
			'eac_file_system_restricted_protocols',
			array(
				'phar://',
				'php://',
				'glob://',
				'data://',
				'expect://',
				'zip://',
				'rar://',
				'zlib://',
			)
		);

		// Now we need the URL encoded protocols to ensure we catch all variations.
		return array_merge(
			$protocols,
			array_map( 'urlencode', $protocols )
		);
	}

	/**
	 * Check if the file system is direct.
	 *
	 * @since 2.0.0
	 * @return bool True if the file system is direct, false otherwise.
	 */
	private static function is_direct() {
		return self::get_fs() instanceof \WP_Filesystem_Direct;
	}
}
