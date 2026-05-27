<?php
/**
 * Allowlist-renderer voor SVG-iconen uit de icons/ map.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

/**
 * Handles IconRegistry functionality.
 */
final class IconRegistry {

	/**
	 * Available SVG icon names.
	 *
	 * @var array<int, string>
	 */
	public const ICONS = array(
		'play',
		'pause',
		'volume',
		'volume-muted',
		'search',
		'menu',
		'close',
		'whatsapp',
		'external-link',
		'calendar',
		'clock',
		'map-pin',
		'chevron-left',
		'chevron-right',
	);

	/**
	 * Registers hooks.
	 *
	 * @return void Return value.
	 */
	public static function register(): void {
		// Pure utility class; geen hooks nodig.
	}

	/**
	 * Exists.
	 *
	 * @param string $name Name.
	 * @return bool Return value.
	 */
	public static function exists( string $name ): bool {
		return in_array( $name, self::ICONS, true );
	}

	/**
	 * Render.
	 *
	 * @param string $name Name.
	 * @param array  $args Args.
	 * @return string Return value.
	 */
	public static function render( string $name, array $args = array() ): string {
		if ( ! self::exists( $name ) ) {
			return '';
		}

		$path = RUCPHEN_THEME_DIR . 'icons/' . $name . '.svg';
		if ( ! is_readable( $path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local theme SVG file read.
		$svg = (string) file_get_contents( $path );
		if ( '' === $svg ) {
			return '';
		}

		$class  = isset( $args['class'] ) ? (string) $args['class'] : 'icon icon-' . $name;
		$size   = isset( $args['size'] ) ? (int) $args['size'] : 24;
		$label  = isset( $args['label'] ) ? (string) $args['label'] : '';
		$hidden = isset( $args['hidden'] ) ? (bool) $args['hidden'] : ( '' === $label );

		$attrs = sprintf(
			' class="%s" width="%d" height="%d" aria-hidden="%s"',
			esc_attr( $class ),
			$size,
			$size,
			$hidden ? 'true' : 'false'
		);

		if ( '' !== $label ) {
			$attrs .= sprintf( ' role="img" aria-label="%s"', esc_attr( $label ) );
		}

		// Inject attrs op de root <svg> tag, vervang of voeg toe.
		$svg = preg_replace_callback(
			'#<svg\b([^>]*)>#i',
			static function ( array $m ) use ( $attrs, $class, $size, $hidden, $label ): string {
				$existing = $m[1];
				// Strip bestaande width/height/class/aria-hidden om collisions te voorkomen.
				$existing = preg_replace( '/\s(width|height|class|aria-hidden|role|aria-label)="[^"]*"/i', '', $existing );
				return '<svg' . $existing . $attrs . '>';
			},
			$svg,
			1
		);

		return (string) $svg;
	}

	/**
	 * Print.
	 *
	 * @param string $name Name.
	 * @param array  $args Args.
	 * @return void Return value.
	 */
	public static function print( string $name, array $args = array() ): void {
		echo wp_kses(
			self::render( $name, $args ),
			array(
				'svg'      => array(
					'class'           => true,
					'width'           => true,
					'height'          => true,
					'viewBox'         => true,
					'fill'            => true,
					'stroke'          => true,
					'stroke-width'    => true,
					'stroke-linecap'  => true,
					'stroke-linejoin' => true,
					'xmlns'           => true,
					'aria-hidden'     => true,
					'aria-label'      => true,
					'role'            => true,
					'focusable'       => true,
				),
				'path'     => array(
					'd'               => true,
					'fill'            => true,
					'stroke'          => true,
					'stroke-width'    => true,
					'stroke-linecap'  => true,
					'stroke-linejoin' => true,
					'fill-rule'       => true,
					'clip-rule'       => true,
				),
				'circle'   => array(
					'cx'           => true,
					'cy'           => true,
					'r'            => true,
					'fill'         => true,
					'stroke'       => true,
					'stroke-width' => true,
				),
				'rect'     => array(
					'x'            => true,
					'y'            => true,
					'width'        => true,
					'height'       => true,
					'rx'           => true,
					'ry'           => true,
					'fill'         => true,
					'stroke'       => true,
					'stroke-width' => true,
				),
				'line'     => array(
					'x1'             => true,
					'y1'             => true,
					'x2'             => true,
					'y2'             => true,
					'stroke'         => true,
					'stroke-width'   => true,
					'stroke-linecap' => true,
				),
				'polyline' => array(
					'points'          => true,
					'fill'            => true,
					'stroke'          => true,
					'stroke-width'    => true,
					'stroke-linecap'  => true,
					'stroke-linejoin' => true,
				),
				'polygon'  => array(
					'points'       => true,
					'fill'         => true,
					'stroke'       => true,
					'stroke-width' => true,
				),
				'g'        => array(
					'fill'         => true,
					'stroke'       => true,
					'stroke-width' => true,
				),
				'title'    => array(),
			)
		);
	}
}
