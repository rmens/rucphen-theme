<?php
/**
 * Bootstrap data voor de zwfm-metadata WebSocket connectie in JS.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

final class NowPlaying {

	private const REST_NAMESPACE       = 'radio-rucphen/v1';
	private const ARTWORK_ROUTE        = '/now-playing-artwork';
	private const ITUNES_SEARCH_URL    = 'https://itunes.apple.com/search';
	private const ITUNES_LOOKUP_URL    = 'https://itunes.apple.com/lookup';
	private const ARTWORK_CACHE_TTL    = 12 * HOUR_IN_SECONDS;
	private const ARTWORK_MISS_TTL     = 30 * MINUTE_IN_SECONDS;
	private const ITUNES_COUNTRIES     = [ 'NL', 'US' ];
	private const ARTWORK_CACHE_VERSION = 'v2';
	private const ARTWORK_SIZE          = '600x600bb';

	public static function register(): void {
		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
	}

	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ARTWORK_ROUTE,
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'args'                => [
					'title' => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'artist' => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'formatted' => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
				'callback'            => [ self::class, 'handle_artwork' ],
			]
		);
	}

	public static function handle_artwork( \WP_REST_Request $request ): \WP_REST_Response {
		$title     = self::clean_metadata_value( (string) $request->get_param( 'title' ) );
		$artist    = self::clean_metadata_value( (string) $request->get_param( 'artist' ) );
		$formatted = self::clean_metadata_value( (string) $request->get_param( 'formatted' ) );

		if ( $artist === '' && $formatted !== '' ) {
			[ $artist, $title ] = self::split_formatted_metadata( $formatted, $title );
		}

		if ( strlen( $title . $artist ) < 2 ) {
			return new \WP_REST_Response( self::artwork_miss_response( $title, $artist ), 200 );
		}

		$cache_key = 'rucphen_np_art_' . self::ARTWORK_CACHE_VERSION . '_' . md5( strtolower( $artist . '|' . $title ) );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			$cached['cached'] = true;
			return new \WP_REST_Response( $cached, 200 );
		}

		$result = self::lookup_track_artwork( $artist, $title );
		if ( $result === null && $artist !== '' ) {
			$result = self::lookup_artist_artwork( $artist );
		}

		if ( $result === null ) {
			$result = self::artwork_miss_response( $title, $artist );
			set_transient( $cache_key, $result, self::ARTWORK_MISS_TTL );
			return new \WP_REST_Response( $result, 200 );
		}

		$stored_result           = $result;
		$stored_result['cached'] = false;
		set_transient( $cache_key, $stored_result, self::ARTWORK_CACHE_TTL );
		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function bootstrap_data(): array {
		$stream  = Settings::get( Settings::OPTION_STREAM );
		$contact = Settings::get( Settings::OPTION_CONTACT );
		$station = Settings::get( Settings::OPTION_STATION );

		return [
			'station' => [
				'name'    => (string) ( $station['name'] ?? 'Radio Rucphen' ),
				'tagline' => (string) ( $station['tagline'] ?? '' ),
			],
			'stream'  => [
				'url'                          => (string) ( $stream['stream_url'] ?? '' ),
				'metadataProvider'             => (string) ( $stream['metadata_provider'] ?? 'zwfm-metadata' ),
				'metadataWebsocketUrl'         => (string) ( $stream['metadata_websocket_url'] ?? '' ),
				'metadataHttpFallbackUrl'      => (string) ( $stream['metadata_http_fallback_url'] ?? '' ),
				'metadataStaleAfterSeconds'    => (int) ( $stream['metadata_stale_after_seconds'] ?? 60 ),
				'metadataReconnectMinSeconds'  => (int) ( $stream['metadata_reconnect_min_seconds'] ?? 2 ),
				'metadataReconnectMaxSeconds'  => (int) ( $stream['metadata_reconnect_max_seconds'] ?? 30 ),
				'coverLookupEnabled'           => (bool) ( $stream['cover_lookup_enabled'] ?? true ),
			],
			'contact' => [
				'whatsappNumber' => (string) ( $contact['whatsapp_number'] ?? '' ),
				'whatsappText'   => (string) ( $contact['whatsapp_default_text'] ?? '' ),
			],
			'restRoot' => esc_url_raw( rest_url( 'radio-rucphen/v1/' ) ),
		];
	}

	/**
	 * @return array{0:string,1:string}
	 */
	private static function split_formatted_metadata( string $formatted, string $fallback_title ): array {
		$parts = preg_split( '/\s+-\s+/', $formatted, 2 );
		if ( is_array( $parts ) && count( $parts ) === 2 ) {
			return [ self::clean_metadata_value( $parts[0] ), self::clean_metadata_value( $parts[1] ) ];
		}

		return [ '', $fallback_title !== '' ? $fallback_title : $formatted ];
	}

	private static function clean_metadata_value( string $value ): string {
		$value = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) );
		$value = preg_replace( '/\s+/', ' ', $value ) ?? $value;
		return trim( $value );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function lookup_track_artwork( string $artist, string $title ): ?array {
		$terms = [];
		if ( $artist !== '' && $title !== '' ) {
			$terms[] = $artist . ' ' . $title;
		}
		if ( $title !== '' ) {
			$terms[] = $title;
		}

		foreach ( array_unique( $terms ) as $term ) {
			foreach ( self::ITUNES_COUNTRIES as $country ) {
				$results = self::itunes_search(
					[
						'term'     => $term,
						'country'  => $country,
						'media'    => 'music',
						'entity'   => 'song',
						'limit'    => 8,
						'explicit' => 'Yes',
					]
				);

				$best = self::best_track_result( $results, $artist, $title );
				if ( $best !== null ) {
					return self::artwork_response_from_itunes( $best, 'track' );
				}
			}
		}

		return null;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function lookup_artist_artwork( string $artist ): ?array {
		$cache_key = 'rucphen_np_artist_art_' . self::ARTWORK_CACHE_VERSION . '_' . md5( strtolower( $artist ) );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			$cached['cached'] = true;
			return $cached;
		}

		$artist_id = 0;

		foreach ( self::ITUNES_COUNTRIES as $country ) {
			$artists = self::itunes_search(
				[
					'term'      => $artist,
					'country'   => $country,
					'media'     => 'music',
					'entity'    => 'musicArtist',
					'attribute' => 'artistTerm',
					'limit'     => 5,
				]
			);

			foreach ( $artists as $candidate ) {
				$score = self::text_match_score( $artist, (string) ( $candidate['artistName'] ?? '' ) );
				if ( $score < 16 ) {
					continue;
				}

				if ( ! empty( $candidate['artworkUrl100'] ) ) {
					return self::cache_artist_artwork( $cache_key, self::artwork_response_from_itunes( $candidate, 'artist' ) );
				}

				$page_artwork = self::artwork_response_from_artist_page( $candidate );
				if ( $page_artwork !== null ) {
					return self::cache_artist_artwork( $cache_key, $page_artwork );
				}

				$artist_id = (int) ( $candidate['artistId'] ?? 0 );
				break 2;
			}
		}

		if ( $artist_id > 0 ) {
			foreach ( self::ITUNES_COUNTRIES as $country ) {
				$catalog = self::itunes_lookup(
					[
						'id'      => $artist_id,
						'country' => $country,
						'entity'  => 'album',
						'limit'   => 6,
					]
				);

				foreach ( $catalog as $candidate ) {
					if ( (string) ( $candidate['wrapperType'] ?? '' ) !== 'collection' || empty( $candidate['artworkUrl100'] ) ) {
						continue;
					}
					return self::cache_artist_artwork( $cache_key, self::artwork_response_from_itunes( $candidate, 'artist-catalog' ) );
				}
			}
		}

		foreach ( self::ITUNES_COUNTRIES as $country ) {
			$tracks = self::itunes_search(
				[
					'term'      => $artist,
					'country'   => $country,
					'media'     => 'music',
					'entity'    => 'song',
					'attribute' => 'artistTerm',
					'limit'     => 5,
				]
			);

			foreach ( $tracks as $candidate ) {
				if ( empty( $candidate['artworkUrl100'] ) ) {
					continue;
				}
				if ( self::text_match_score( $artist, (string) ( $candidate['artistName'] ?? '' ) ) >= 12 ) {
					return self::cache_artist_artwork( $cache_key, self::artwork_response_from_itunes( $candidate, 'artist-catalog' ) );
				}
			}
		}

		return null;
	}

	/**
	 * @param array<string, mixed>|null $result
	 * @return array<string, mixed>|null
	 */
	private static function cache_artist_artwork( string $cache_key, ?array $result ): ?array {
		if ( $result === null ) {
			return null;
		}

		$stored_result           = $result;
		$stored_result['cached'] = false;
		set_transient( $cache_key, $stored_result, self::ARTWORK_CACHE_TTL );

		return $result;
	}

	/**
	 * @param array<int, array<string, mixed>> $results
	 * @return array<string, mixed>|null
	 */
	private static function best_track_result( array $results, string $artist, string $title ): ?array {
		$best       = null;
		$best_score = 0;

		foreach ( $results as $candidate ) {
			if ( (string) ( $candidate['kind'] ?? '' ) !== 'song' || empty( $candidate['artworkUrl100'] ) ) {
				continue;
			}

			$track_score  = self::text_match_score( $title, (string) ( $candidate['trackName'] ?? '' ) );
			$artist_score = $artist === '' ? 8 : self::text_match_score( $artist, (string) ( $candidate['artistName'] ?? '' ) );
			$score        = ( $track_score * 3 ) + ( $artist_score * 2 );

			if ( $score > $best_score ) {
				$best       = $candidate;
				$best_score = $score;
			}
		}

		$minimum = $artist !== '' && $title !== '' ? 58 : 34;
		return $best_score >= $minimum ? $best : null;
	}

	private static function text_match_score( string $needle, string $candidate ): int {
		$needle    = self::compare_string( $needle );
		$candidate = self::compare_string( $candidate );

		if ( $needle === '' || $candidate === '' ) {
			return 0;
		}

		if ( $needle === $candidate ) {
			return 24;
		}

		if ( str_contains( $candidate, $needle ) || str_contains( $needle, $candidate ) ) {
			return 18;
		}

		similar_text( $needle, $candidate, $percent );
		return (int) round( max( 0, min( 14, $percent / 7 ) ) );
	}

	private static function compare_string( string $value ): string {
		$value = self::clean_metadata_value( $value );
		$value = remove_accents( $value );
		$value = strtolower( $value );
		$value = preg_replace( '/\s+[\(\[][^\)\]]*(radio edit|single edit|remaster|feat\.?|ft\.?)[^\)\]]*[\)\]]/i', '', $value ) ?? $value;
		$value = preg_replace( '/\s+-\s+(radio edit|single edit|remaster).*$/i', '', $value ) ?? $value;
		$value = preg_replace( '/[^a-z0-9]+/', ' ', $value ) ?? $value;
		return trim( $value );
	}

	/**
	 * @param array<string, scalar> $params
	 * @return array<int, array<string, mixed>>
	 */
	private static function itunes_search( array $params ): array {
		return self::itunes_request( self::ITUNES_SEARCH_URL, $params );
	}

	/**
	 * @param array<string, scalar> $params
	 * @return array<int, array<string, mixed>>
	 */
	private static function itunes_lookup( array $params ): array {
		return self::itunes_request( self::ITUNES_LOOKUP_URL, $params );
	}

	/**
	 * @param array<string, mixed> $artist
	 * @return array<string, mixed>|null
	 */
	private static function artwork_response_from_artist_page( array $artist ): ?array {
		$url = esc_url_raw( (string) ( $artist['artistLinkUrl'] ?? $artist['artistViewUrl'] ?? '' ) );
		if ( $url === '' ) {
			return null;
		}

		$response = wp_remote_get(
			$url,
			[
				'timeout'     => 3,
				'redirection' => 3,
				'headers'     => [
					'Accept'     => 'text/html',
					'User-Agent' => 'Radio Rucphen WordPress Theme',
				],
			]
		);

		if ( is_wp_error( $response ) || (int) wp_remote_retrieve_response_code( $response ) >= 400 ) {
			return null;
		}

		$image = self::meta_image_from_html( wp_remote_retrieve_body( $response ) );
		if ( $image === '' ) {
			return null;
		}

		return [
			'found'      => true,
			'cached'     => false,
			'provider'   => 'itunes',
			'source'     => 'artist-image',
			'artworkUrl' => self::normalize_itunes_artwork_url( $image ),
			'title'      => '',
			'artist'     => (string) ( $artist['artistName'] ?? '' ),
			'collection' => '',
			'viewUrl'    => $url,
		];
	}

	private static function meta_image_from_html( string $html ): string {
		if ( $html === '' || ! preg_match_all( '/<meta\s+[^>]*>/i', $html, $matches ) ) {
			return '';
		}

		foreach ( $matches[0] as $tag ) {
			if ( ! preg_match( '/(?:property|name)=["\'](?:og:image|twitter:image)["\']/i', $tag ) ) {
				continue;
			}

			if ( ! preg_match( '/content=["\']([^"\']+)["\']/i', $tag, $content ) ) {
				continue;
			}

			$url = html_entity_decode( $content[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( self::is_itunes_artwork_url( $url ) ) {
				return esc_url_raw( $url );
			}
		}

		return '';
	}

	/**
	 * @param array<string, scalar> $params
	 * @return array<int, array<string, mixed>>
	 */
	private static function itunes_request( string $base_url, array $params ): array {
		$url      = add_query_arg( $params, $base_url );
		$response = wp_remote_get(
			esc_url_raw( $url ),
			[
				'timeout'     => 3,
				'redirection' => 2,
				'headers'     => [
					'Accept' => 'application/json',
				],
			]
		);

		if ( is_wp_error( $response ) || (int) wp_remote_retrieve_response_code( $response ) >= 400 ) {
			return [];
		}

		$payload = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $payload ) || empty( $payload['results'] ) || ! is_array( $payload['results'] ) ) {
			return [];
		}

		return array_values( array_filter( $payload['results'], 'is_array' ) );
	}

	/**
	 * @param array<string, mixed> $result
	 * @return array<string, mixed>|null
	 */
	private static function artwork_response_from_itunes( array $result, string $source ): ?array {
		$artwork = self::normalize_itunes_artwork_url( (string) ( $result['artworkUrl100'] ?? '' ) );
		if ( $artwork === '' ) {
			return null;
		}

		return [
			'found'      => true,
			'cached'     => false,
			'provider'   => 'itunes',
			'source'     => $source,
			'artworkUrl' => $artwork,
			'title'      => (string) ( $result['trackName'] ?? $result['collectionName'] ?? '' ),
			'artist'     => (string) ( $result['artistName'] ?? '' ),
			'collection' => (string) ( $result['collectionName'] ?? '' ),
			'viewUrl'    => esc_url_raw( (string) ( $result['trackViewUrl'] ?? $result['collectionViewUrl'] ?? $result['artistViewUrl'] ?? $result['artistLinkUrl'] ?? '' ) ),
		];
	}

	private static function normalize_itunes_artwork_url( string $url ): string {
		$url = esc_url_raw( $url );
		if ( $url === '' || ! self::is_itunes_artwork_url( $url ) ) {
			return $url;
		}

		$normalized = preg_replace_callback(
			'#/[^/?]+\.(jpe?g|png|webp)(\?.*)?$#i',
			static function ( array $matches ): string {
				return '/' . self::ARTWORK_SIZE . '.' . strtolower( $matches[1] ) . ( $matches[2] ?? '' );
			},
			$url,
			1
		);

		return esc_url_raw( is_string( $normalized ) ? $normalized : $url );
	}

	private static function is_itunes_artwork_url( string $url ): bool {
		$host = wp_parse_url( esc_url_raw( $url ), PHP_URL_HOST );
		return is_string( $host ) && str_ends_with( strtolower( $host ), 'mzstatic.com' );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function artwork_miss_response( string $title, string $artist ): array {
		return [
			'found'      => false,
			'cached'     => false,
			'provider'   => 'itunes',
			'source'     => 'none',
			'artworkUrl' => '',
			'title'      => $title,
			'artist'     => $artist,
			'collection' => '',
			'viewUrl'    => '',
		];
	}
}
