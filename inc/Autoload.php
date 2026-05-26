<?php
/**
 * PSR-4 style autoloader voor de RadioRucphen namespace.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

final class Autoload {

	public static function register(): void {
		spl_autoload_register( [ self::class, 'load' ] );
	}

	public static function load( string $class ): void {
		$prefix = __NAMESPACE__ . '\\';
		if ( str_starts_with( $class, $prefix ) === false ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$path     = RUCPHEN_THEME_DIR . 'inc/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
