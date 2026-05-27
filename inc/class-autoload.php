<?php
/**
 * PSR-4 style autoloader voor de RadioRucphen namespace.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

/**
 * Loads Radio Rucphen classes.
 */
final class Autoload {

	/**
	 * Registers hooks.
	 *
	 * @return void Return value.
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Loads a class file.
	 *
	 * @param string $class_name Class name.
	 * @return void Return value.
	 */
	public static function load( string $class_name ): void {
		$prefix = __NAMESPACE__ . '\\';
		if ( false === str_starts_with( $class_name, $prefix ) ) {
			return;
		}

		$relative = strtolower( substr( $class_name, strlen( $prefix ) ) );
		$path     = RUCPHEN_THEME_DIR . 'inc/class-' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
