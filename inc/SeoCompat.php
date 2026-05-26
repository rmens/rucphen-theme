<?php
/**
 * Yoast SEO compatibiliteitslaag.
 *
 * Met Yoast actief levert het theme geen eigen canonical/meta/OG/schema en
 * vertrouwt het op Yoast. Zonder Yoast wordt een minimale fallback voor
 * description, OG en Twitter card op publieke templates gerenderd.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

final class SeoCompat {

	public static function register(): void {
		add_action( 'wp_head', [ self::class, 'maybe_render_fallback' ], 5 );
		add_filter( 'wpseo_schema_graph_pieces', [ self::class, 'maybe_extend_schema' ], 11, 2 );
	}

	public static function yoast_active(): bool {
		return defined( 'WPSEO_VERSION' ) || class_exists( '\WPSEO_Frontend', false );
	}

	public static function maybe_render_fallback(): void {
		if ( self::yoast_active() ) {
			return;
		}

		$description = '';
		$title       = wp_get_document_title();

		if ( is_singular() ) {
			$post = get_post();
			if ( $post instanceof \WP_Post ) {
				$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );
				$description = (string) $excerpt;
			}
		} elseif ( is_home() || is_front_page() ) {
			$description = (string) get_bloginfo( 'description' );
		} elseif ( is_archive() ) {
			$description = (string) get_the_archive_description();
			$description = wp_strip_all_tags( $description );
		}

		$description = trim( preg_replace( '/\s+/u', ' ', $description ) ?? '' );

		if ( $description !== '' ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_html_excerpt( $description, 160, '...' ) ) );
		}

		$image = '';
		if ( is_singular() && has_post_thumbnail() ) {
			$image = (string) get_the_post_thumbnail_url( get_the_ID(), 'rucphen-card' );
		}

		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:type" content="%s">' . "\n", is_singular() ? 'article' : 'website' );
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( home_url( add_query_arg( null, null ) ) ) );

		if ( $description !== '' ) {
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( wp_html_excerpt( $description, 200, '...' ) ) );
		}

		if ( $image !== '' ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		}

		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	}

	/**
	 * Verrijking van Yoast's schema graph: hook voor toekomstige CPT-pieces.
	 *
	 * @param array<int, mixed> $pieces
	 * @param mixed             $context
	 * @return array<int, mixed>
	 */
	public static function maybe_extend_schema( $pieces, $context ): array {
		if ( ! is_array( $pieces ) ) {
			return [];
		}
		return $pieces;
	}
}
