<?php
/**
 * Zuidwest Update importer.
 *
 * Haalt nieuws en video's uit de publieke WP REST API van zuidwestupdate.nl
 * en bewaart een genormaliseerde cache in WordPress options. Externe items
 * worden niet als posts of CPT opgeslagen.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

/**
 * Handles ZuidwestImporter functionality.
 */
final class ZuidwestImporter {

	public const OPTION_NEWS_CACHE   = 'rucphen_zwu_news_cache';
	public const OPTION_VIDEOS_CACHE = 'rucphen_zwu_videos_cache';
	public const OPTION_LAST_SUCCESS = 'rucphen_zwu_last_success_at';
	public const OPTION_LAST_ERROR   = 'rucphen_zwu_last_error';
	public const CRON_HOOK           = 'rucphen_zwu_import';

	/**
	 * Registers hooks.
	 *
	 * @return void Return value.
	 */
	public static function register(): void {
		add_action( self::CRON_HOOK, array( self::class, 'run' ) );
		add_action( 'init', array( self::class, 'schedule' ) );
		add_action( 'switch_theme', array( self::class, 'unschedule' ) );
	}

	/**
	 * Schedule.
	 *
	 * @return void Return value.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 60, 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule.
	 *
	 * @return void Return value.
	 */
	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Get news cache.
	 *
	 * @return array Return value.
	 */
	public static function get_news_cache(): array {
		$value = get_option( self::OPTION_NEWS_CACHE, array() );
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Get videos cache.
	 *
	 * @return array Return value.
	 */
	public static function get_videos_cache(): array {
		$value = get_option( self::OPTION_VIDEOS_CACHE, array() );
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Run.
	 *
	 * @return void Return value.
	 */
	public static function run(): void {
		$settings = Settings::get( Settings::OPTION_ZWU );

		$news_result   = self::fetch_items( $settings, 'standard' );
		$videos_result = self::fetch_items( $settings, 'video' );

		$any_success = false;
		$errors      = array();

		if ( is_wp_error( $news_result ) ) {
			$errors[] = 'news: ' . $news_result->get_error_message();
		} else {
			update_option( self::OPTION_NEWS_CACHE, $news_result, false );
			$any_success = true;
		}

		if ( is_wp_error( $videos_result ) ) {
			$errors[] = 'videos: ' . $videos_result->get_error_message();
		} else {
			update_option( self::OPTION_VIDEOS_CACHE, $videos_result, false );
			$any_success = true;
		}

		if ( $any_success ) {
			update_option( self::OPTION_LAST_SUCCESS, gmdate( 'c' ), false );
		}

		if ( array() === $errors ) {
			delete_option( self::OPTION_LAST_ERROR );
		} else {
			update_option( self::OPTION_LAST_ERROR, implode( ' | ', $errors ), false );
		}
	}

	/**
	 * Fetch items.
	 *
	 * @param array  $settings Settings.
	 * @param string $format Format.
	 */
	private static function fetch_items( array $settings, string $format ) {
		$base    = isset( $settings['base_url'] ) ? (string) $settings['base_url'] : '';
		$regions = isset( $settings['allowed_region_slugs'] ) && is_array( $settings['allowed_region_slugs'] )
			? array_values( array_intersect( $settings['allowed_region_slugs'], Taxonomies::ALLOWED_REGION_SLUGS ) )
			: Taxonomies::ALLOWED_REGION_SLUGS;

		if ( '' === $base || array() === $regions ) {
			return new \WP_Error( 'rucphen_zwu_config', 'invalid config' );
		}

		$max = 'video' === $format
			? max( 1, (int) ( $settings['max_videos'] ?? 8 ) )
			: max( 1, (int) ( $settings['max_news'] ?? 12 ) );

		$query = array(
			'_embed'   => 1,
			'per_page' => min( 50, $max * 2 ),
			'orderby'  => 'date',
			'order'    => 'desc',
		);

		$endpoint = trailingslashit( $base ) . 'posts?' . http_build_query( $query );

		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout'    => 15,
				'user-agent' => 'RadioRucphenTheme/' . RUCPHEN_THEME_VERSION,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error( 'rucphen_zwu_http', sprintf( 'HTTP %d', $code ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$raw  = json_decode( $body, true );
		if ( ! is_array( $raw ) ) {
			return new \WP_Error( 'rucphen_zwu_decode', 'invalid JSON response' );
		}

		$items = array();
		$seen  = array();
		foreach ( $raw as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$item = self::normalize( $entry, $regions );
			if ( null === $item ) {
				continue;
			}

			if ( 'video' === $format && 'video' !== $item['format'] ) {
				continue;
			}
			if ( 'standard' === $format && 'video' === $item['format'] ) {
				continue;
			}

			if ( isset( $seen[ $item['source_id'] ] ) ) {
				continue;
			}
			$seen[ $item['source_id'] ] = true;
			$items[]                    = $item;

			if ( count( $items ) >= $max ) {
				break;
			}
		}

		return $items;
	}

	/**
	 * Normalize.
	 *
	 * @param array $entry Entry.
	 * @param array $allowed_regions Allowed regions.
	 * @return ?array Return value.
	 */
	private static function normalize( array $entry, array $allowed_regions ): ?array {
		$id = isset( $entry['id'] ) ? (int) $entry['id'] : 0;
		if ( $id <= 0 ) {
			return null;
		}

		$region_slug  = '';
		$region_label = '';
		$embedded     = $entry['_embedded']['wp:term'] ?? array();
		if ( is_array( $embedded ) ) {
			foreach ( $embedded as $taxonomy_terms ) {
				if ( ! is_array( $taxonomy_terms ) ) {
					continue;
				}
				foreach ( $taxonomy_terms as $term ) {
					if ( ! is_array( $term ) ) {
						continue;
					}
					$slug = isset( $term['slug'] ) ? sanitize_title( (string) $term['slug'] ) : '';
					if ( in_array( $slug, $allowed_regions, true ) ) {
						$region_slug  = $slug;
						$region_label = isset( $term['name'] ) ? (string) $term['name'] : ucfirst( $slug );
						break 2;
					}
				}
			}
		}

		if ( '' === $region_slug ) {
			return null;
		}

		$title      = self::strip_to_plain( (string) ( $entry['title']['rendered'] ?? '' ) );
		$excerpt    = self::strip_to_plain( (string) ( $entry['excerpt']['rendered'] ?? '' ) );
		$source_url = isset( $entry['link'] ) ? esc_url_raw( (string) $entry['link'] ) : '';
		$published  = isset( $entry['date_gmt'] ) ? (string) $entry['date_gmt'] : '';
		$format     = isset( $entry['format'] ) && (string) 'video' === $entry['format'] ? 'video' : 'standard';

		$image_url = '';
		$media     = $entry['_embedded']['wp:featuredmedia'][0] ?? null;
		if ( is_array( $media ) ) {
			$sizes = $media['media_details']['sizes'] ?? array();
			if ( is_array( $sizes ) && isset( $sizes['medium_large']['source_url'] ) ) {
				$image_url = (string) $sizes['medium_large']['source_url'];
			} elseif ( isset( $media['source_url'] ) ) {
				$image_url = (string) $media['source_url'];
			}
		}

		$video_embed = null;
		if ( 'video' === $format && isset( $entry['content']['rendered'] ) ) {
			$video_embed = self::extract_embed_url( (string) $entry['content']['rendered'] );
		}

		return array(
			'source_id'       => 'zwu-' . $id,
			'source_name'     => 'Zuidwest Update',
			'source_url'      => $source_url,
			'published_at'    => '' !== $published ? $published . 'Z' : '',
			'title'           => $title,
			'excerpt'         => $excerpt,
			'image_url'       => esc_url_raw( $image_url ),
			'format'          => $format,
			'video_embed_url' => $video_embed,
			'region_slug'     => $region_slug,
			'region_label'    => $region_label,
		);
	}

	/**
	 * Strip to plain.
	 *
	 * @param string $html Html.
	 * @return string Return value.
	 */
	private static function strip_to_plain( string $html ): string {
		$text = wp_strip_all_tags( $html, true );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( preg_replace( '/\s+/u', ' ', $text ) ?? '' );
	}

	/**
	 * Extract embed url.
	 *
	 * @param string $html Html.
	 * @return ?string Return value.
	 */
	private static function extract_embed_url( string $html ): ?string {
		if ( preg_match( '#https?://(?:www\.)?(?:youtube\.com|youtu\.be|player\.vimeo\.com|vimeo\.com)/[^\s"\'<>]+#i', $html, $m ) === 1 ) {
			return esc_url_raw( $m[0] );
		}
		return null;
	}
}
