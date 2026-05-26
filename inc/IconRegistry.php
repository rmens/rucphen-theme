<?php
/**
 * Allowlist-renderer voor SVG-iconen uit de icons/ map.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

final class IconRegistry {

	/**
	 * @var array<int, string>
	 */
	public const ICONS = [
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
	];

	public static function register(): void {
		// Pure utility class; geen hooks nodig.
	}

	public static function exists( string $name ): bool {
		return in_array( $name, self::ICONS, true );
	}

	/**
	 * @param array{class?: string, size?: int, label?: string, hidden?: bool} $args
	 */
	public static function render( string $name, array $args = [] ): string {
		if ( ! self::exists( $name ) ) {
			return '';
		}

		$path = RUCPHEN_THEME_DIR . 'icons/' . $name . '.svg';
		if ( ! is_readable( $path ) ) {
			return '';
		}

		$svg = (string) file_get_contents( $path );
		if ( $svg === '' ) {
			return '';
		}

		$class  = isset( $args['class'] ) ? (string) $args['class'] : 'icon icon-' . $name;
		$size   = isset( $args['size'] ) ? (int) $args['size'] : 24;
		$label  = isset( $args['label'] ) ? (string) $args['label'] : '';
		$hidden = isset( $args['hidden'] ) ? (bool) $args['hidden'] : ( $label === '' );

		$attrs = sprintf(
			' class="%s" width="%d" height="%d" aria-hidden="%s"',
			esc_attr( $class ),
			$size,
			$size,
			$hidden ? 'true' : 'false'
		);

		if ( $label !== '' ) {
			$attrs .= sprintf( ' role="img" aria-label="%s"', esc_attr( $label ) );
		}

		// Inject attrs op de root <svg> tag, vervang of voeg toe.
		$svg = preg_replace_callback(
			'#<svg\b([^>]*)>#i',
			static function ( array $m ) use ( $attrs, $class, $size, $hidden, $label ): string {
				$existing = $m[1];
				// strip bestaande width/height/class/aria-hidden om collisions te voorkomen
				$existing = preg_replace( '/\s(width|height|class|aria-hidden|role|aria-label)="[^"]*"/i', '', $existing );
				return '<svg' . $existing . $attrs . '>';
			},
			$svg,
			1
		);

		return (string) $svg;
	}

	public static function print( string $name, array $args = [] ): void {
		echo wp_kses(
			self::render( $name, $args ),
			[
				'svg'      => [ 'class' => true, 'width' => true, 'height' => true, 'viewBox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'xmlns' => true, 'aria-hidden' => true, 'aria-label' => true, 'role' => true, 'focusable' => true ],
				'path'     => [ 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'fill-rule' => true, 'clip-rule' => true ],
				'circle'   => [ 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ],
				'rect'     => [ 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ],
				'line'     => [ 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true ],
				'polyline' => [ 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ],
				'polygon'  => [ 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ],
				'g'        => [ 'fill' => true, 'stroke' => true, 'stroke-width' => true ],
				'title'    => [],
			]
		);
	}
}
