<?php
/**
 * Enqueue Tailwind build, JS en bootstrap data.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

final class Assets {

	public const HANDLE_CSS = 'radio-rucphen-app';
	public const HANDLE_JS  = 'radio-rucphen-app';

	public static function register(): void {
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_frontend' ] );
		add_action( 'enqueue_block_editor_assets', [ self::class, 'enqueue_editor' ] );
	}

	public static function enqueue_frontend(): void {
		$css_rel = 'assets/css/app.css';
		$js_rel  = 'assets/js/app.js';

		$css_path = RUCPHEN_THEME_DIR . $css_rel;
		$js_path  = RUCPHEN_THEME_DIR . $js_rel;

		if ( is_readable( $css_path ) ) {
			wp_enqueue_style(
				self::HANDLE_CSS,
				RUCPHEN_THEME_URI . $css_rel,
				[],
				(string) filemtime( $css_path )
			);
		}

		if ( is_readable( $js_path ) ) {
			wp_enqueue_script(
				self::HANDLE_JS,
				RUCPHEN_THEME_URI . $js_rel,
				[],
				(string) filemtime( $js_path ),
				[ 'in_footer' => true, 'strategy' => 'defer' ]
			);

			wp_localize_script(
				self::HANDLE_JS,
				'RucphenBoot',
				NowPlaying::bootstrap_data()
			);
		}
	}

	public static function enqueue_editor(): void {
		$css_rel  = 'assets/css/app.css';
		$css_path = RUCPHEN_THEME_DIR . $css_rel;

		if ( is_readable( $css_path ) ) {
			wp_enqueue_style(
				'radio-rucphen-editor',
				RUCPHEN_THEME_URI . $css_rel,
				[],
				(string) filemtime( $css_path )
			);
		}
	}
}
