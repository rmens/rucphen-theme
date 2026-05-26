<?php
/**
 * WP-CLI import van de huidige static site naar WordPress content.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

final class CliImport {

	public static function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		\WP_CLI::add_command(
			'radio-rucphen',
			self::class,
			[ 'shortdesc' => 'Radio Rucphen importcommando\'s.' ]
		);
	}

	/**
	 * Importeer programma's met uitzendmomenten, presentatoren, events en nieuws uit de
	 * huidige static site naar WordPress posts.
	 *
	 * ## OPTIONS
	 *
	 * --source=<path>
	 * : Pad naar de root van de static site (waar data/ en content/ in staan).
	 *
	 * [--dry-run]
	 * : Loop alleen door en log; schrijf niets.
	 *
	 * ## EXAMPLES
	 *
	 *     wp radio-rucphen import-static --source=/var/www/static-site
	 *
	 * @subcommand import-static
	 * @when after_wp_load
	 *
	 * @param array<int, string> $args
	 * @param array<string, mixed> $assoc
	 */
	public function import_static( array $args, array $assoc ): void {
		$source  = isset( $assoc['source'] ) ? (string) $assoc['source'] : '';
		$dry_run = ! empty( $assoc['dry-run'] );

		if ( $source === '' || ! is_dir( $source ) ) {
			\WP_CLI::error( 'Geef --source=<path> naar een geldige static-site map.' );
		}

		$config_path   = $source . '/data/config.json';
		$schedule_path = $source . '/data/schedule.json';
		$djs_path      = $source . '/data/djs.json';
		$events_path   = $source . '/data/events.json';
		$podcasts_path = $source . '/data/podcasts.json';
		$zwu_news_path = $source . '/data/external-news.json';
		$zwu_video_path = $source . '/data/external-videos.json';
		$nieuws_dir    = $source . '/content/nieuws';
		$djs_content   = $source . '/content/djs';

		$config   = self::read_json( $config_path );
		$schedule = self::read_json( $schedule_path );
		$djs      = self::read_json( $djs_path );
		$events   = self::read_json( $events_path );
		$podcasts = self::read_json( $podcasts_path );
		$zwu_news = self::read_json( $zwu_news_path );
		$zwu_videos = self::read_json( $zwu_video_path );

		\WP_CLI::log( 'Start import...' );

		if ( $config !== null ) {
			self::import_config( $config, $dry_run );
		}

		if ( is_array( $zwu_news ) || is_array( $zwu_videos ) ) {
			self::import_zuidwest_caches( is_array( $zwu_news ) ? $zwu_news : [], is_array( $zwu_videos ) ? $zwu_videos : [], $dry_run );
		}

		$presenter_map = [];
		if ( is_array( $djs ) ) {
			$presenter_map = self::import_presenters( $djs, $djs_content, $dry_run );
		}

		$program_map = [];
		if ( is_array( $schedule['programs'] ?? null ) ) {
			$program_map = self::import_programs( (array) $schedule['programs'], $dry_run );
		}

		if ( is_array( $schedule['weekly'] ?? null ) ) {
			self::import_program_airtimes( (array) $schedule['weekly'], $program_map, $presenter_map, $dry_run );
		}

		if ( is_array( $events ) ) {
			self::import_events( $events, $dry_run );
		}

		if ( is_array( $podcasts ) ) {
			self::import_podcasts( $podcasts, $source, $program_map, $dry_run );
		}

		if ( is_dir( $nieuws_dir ) ) {
			self::import_news_posts( $nieuws_dir, $dry_run );
		}

		\WP_CLI::success( 'Import klaar.' );
	}

	/**
	 * @return mixed
	 */
	private static function read_json( string $path ) {
		if ( ! is_readable( $path ) ) {
			return null;
		}
		$json = (string) file_get_contents( $path );
		$data = json_decode( $json, true );
		return is_array( $data ) ? $data : null;
	}

	private static function import_config( array $config, bool $dry_run ): void {
		if ( $dry_run ) {
			\WP_CLI::log( '  [dry] config.json -> options' );
			return;
		}

		if ( isset( $config['station'] ) && is_array( $config['station'] ) ) {
			$current = Settings::get( Settings::OPTION_STATION );
			$current['name']    = (string) ( $config['station']['name'] ?? $current['name'] );
			$current['tagline'] = (string) ( $config['station']['tagline'] ?? $current['tagline'] );
			update_option( Settings::OPTION_STATION, $current, false );
		}

		if ( isset( $config['stream']['url'] ) ) {
			$current = Settings::get( Settings::OPTION_STREAM );
			$current['stream_url'] = (string) $config['stream']['url'];
			update_option( Settings::OPTION_STREAM, $current, false );
		}

		if ( isset( $config['whatsapp'] ) && is_array( $config['whatsapp'] ) ) {
			$current = Settings::get( Settings::OPTION_CONTACT );
			$current['whatsapp_number']       = (string) ( $config['whatsapp']['number'] ?? $current['whatsapp_number'] );
			$current['whatsapp_default_text'] = (string) ( $config['whatsapp']['default_text'] ?? $current['whatsapp_default_text'] );
			update_option( Settings::OPTION_CONTACT, $current, false );
		}

		if ( isset( $config['frequencies'] ) && is_array( $config['frequencies'] ) ) {
			$current = Settings::get( Settings::OPTION_FREQUENCIES );
			$current['fm_mhz']         = (string) ( $config['frequencies']['fm_mhz'] ?? $current['fm_mhz'] );
			$dabs                      = $config['frequencies']['dab_blocks'] ?? [];
			$current['dab_blocks']     = is_array( $dabs ) ? implode( ', ', array_map( 'strval', $dabs ) ) : $current['dab_blocks'];
			$current['coverage']       = (string) ( $config['frequencies']['coverage'] ?? $current['coverage'] );
			$current['cable_provider'] = (string) ( $config['frequencies']['cable']['provider'] ?? $current['cable_provider'] );
			$current['cable_channel']  = (string) ( $config['frequencies']['cable']['channel'] ?? $current['cable_channel'] );
			update_option( Settings::OPTION_FREQUENCIES, $current, false );
		}

		if ( isset( $config['contact'] ) && is_array( $config['contact'] ) ) {
			$current = Settings::get( Settings::OPTION_CONTACT );
			$current['email_studio']   = (string) ( $config['contact']['email_studio'] ?? $current['email_studio'] );
			$current['email_redactie'] = (string) ( $config['contact']['email_redactie'] ?? $current['email_redactie'] );
			update_option( Settings::OPTION_CONTACT, $current, false );
		}

		if ( isset( $config['organization'] ) && is_array( $config['organization'] ) ) {
			$current = Settings::get( Settings::OPTION_ORGANIZATION );
			$current['legal_name'] = (string) ( $config['organization']['legal_name'] ?? $current['legal_name'] );
			$current['kvk']        = (string) ( $config['organization']['kvk'] ?? $current['kvk'] );
			$current['rsin']       = (string) ( $config['organization']['rsin'] ?? $current['rsin'] );
			$current['iban']       = (string) ( $config['organization']['iban'] ?? $current['iban'] );
			$current['anbi']       = (bool) ( $config['organization']['anbi'] ?? $current['anbi'] );
			update_option( Settings::OPTION_ORGANIZATION, $current, false );
		}

		\WP_CLI::log( '  config -> options OK' );
	}

	/**
	 * @return array<string, int> slug => post ID
	 */
	private static function import_presenters( array $djs, string $content_dir, bool $dry_run ): array {
		$map = [];
		foreach ( $djs as $dj ) {
			if ( ! is_array( $dj ) ) {
				continue;
			}
			$slug = (string) ( $dj['slug'] ?? '' );
			if ( $slug === '' ) {
				continue;
			}

			$bio_path = $content_dir . '/' . $slug . '.md';
			$bio      = is_readable( $bio_path ) ? (string) file_get_contents( $bio_path ) : '';
			$bio      = self::strip_frontmatter( $bio );

			if ( $dry_run ) {
				\WP_CLI::log( '  [dry] presenter: ' . $slug );
				continue;
			}

			$post_id = self::upsert_by_slug( PostTypes::PRESENTER, $slug, [
				'post_title'   => (string) ( $dj['name'] ?? $slug ),
				'post_content' => $bio,
				'post_status'  => 'publish',
			] );

			if ( $post_id > 0 ) {
				update_post_meta( $post_id, '_rucphen_presenter_tagline', (string) ( $dj['tagline'] ?? '' ) );
				update_post_meta( $post_id, '_rucphen_presenter_order', (int) ( $dj['order'] ?? 99 ) );
				$map[ $slug ] = $post_id;
			}
		}

		\WP_CLI::log( '  presentatoren: ' . count( $map ) );
		return $map;
	}

	/**
	 * @param array<int, array<string, mixed>> $programs
	 * @return array<string, int> slug => post ID
	 */
	private static function import_programs( array $programs, bool $dry_run ): array {
		$map = [];
		foreach ( $programs as $p ) {
			if ( ! is_array( $p ) ) {
				continue;
			}
			$slug = (string) ( $p['slug'] ?? '' );
			if ( $slug === '' ) {
				continue;
			}

			if ( $dry_run ) {
				\WP_CLI::log( '  [dry] programma: ' . $slug );
				continue;
			}

			$post_id = self::upsert_by_slug( PostTypes::PROGRAM, $slug, [
				'post_title'   => (string) ( $p['title'] ?? $slug ),
				'post_content' => (string) ( $p['long_description'] ?? $p['description'] ?? '' ),
				'post_excerpt' => (string) ( $p['description'] ?? '' ),
				'post_status'  => 'publish',
			] );

			if ( $post_id > 0 ) {
				update_post_meta( $post_id, '_rucphen_program_short_description', (string) ( $p['description'] ?? '' ) );
				update_post_meta( $post_id, '_rucphen_program_long_description', (string) ( $p['long_description'] ?? '' ) );
				update_post_meta( $post_id, '_rucphen_program_featured', ! empty( $p['featured'] ) );
				$map[ $slug ] = $post_id;
			}
		}

		\WP_CLI::log( '  programma\'s: ' . count( $map ) );
		return $map;
	}

	/**
	 * @param array<string, array<int, array<string, mixed>>> $weekly
	 * @param array<string, int> $program_map
	 * @param array<string, int> $presenter_map
	 */
	private static function import_program_airtimes( array $weekly, array $program_map, array $presenter_map, bool $dry_run ): void {
		$count = 0;
		$airtimes_by_program = [];
		$presenters_by_program = [];

		foreach ( $weekly as $day => $slots ) {
			if ( ! is_array( $slots ) ) {
				continue;
			}
			foreach ( $slots as $slot ) {
				if ( ! is_array( $slot ) ) {
					continue;
				}

				if ( $dry_run ) {
					\WP_CLI::log( '  [dry] uitzendmoment: ' . (string) $day . ' ' . (string) ( $slot['from'] ?? '' ) . '-' . (string) ( $slot['to'] ?? '' ) . ' ' . (string) ( $slot['program_slug'] ?? '' ) );
					continue;
				}

				$program_id = $program_map[ (string) ( $slot['program_slug'] ?? '' ) ] ?? 0;
				if ( $program_id <= 0 ) {
					continue;
				}

				$airtimes_by_program[ $program_id ][] = [
					'day'   => sanitize_key( (string) $day ),
					'start' => (string) ( $slot['from'] ?? '' ),
					'end'   => (string) ( $slot['to'] ?? '' ),
				];

				foreach ( (array) ( $slot['dj_slugs'] ?? [] ) as $s ) {
					$pid = $presenter_map[ (string) $s ] ?? 0;
					if ( $pid > 0 ) {
						$presenters_by_program[ $program_id ][ $pid ] = $pid;
					}
				}
				$count++;
			}
		}

		foreach ( $airtimes_by_program as $program_id => $airtimes ) {
			$airtimes = Meta::sanitize_airtimes( $airtimes );
			update_post_meta( (int) $program_id, '_rucphen_program_airtimes', $airtimes );
			update_post_meta( (int) $program_id, '_rucphen_program_presenter_ids', array_values( $presenters_by_program[ $program_id ] ?? [] ) );

			if ( $airtimes !== [] ) {
				update_post_meta( (int) $program_id, '_rucphen_program_default_start', $airtimes[0]['start'] );
				update_post_meta( (int) $program_id, '_rucphen_program_default_end', $airtimes[0]['end'] );
			}
		}

		\WP_CLI::log( '  uitzendmomenten: ' . $count );
	}

	/**
	 * @param array<int, array<string, mixed>> $events
	 */
	private static function import_events( array $events, bool $dry_run ): void {
		$count = 0;
		foreach ( $events as $e ) {
			if ( ! is_array( $e ) ) {
				continue;
			}
			$title = (string) ( $e['title'] ?? '' );
			$slug  = sanitize_title( $title . '-' . substr( (string) ( $e['start'] ?? '' ), 0, 10 ) );
			if ( $slug === '' ) {
				continue;
			}

			if ( $dry_run ) {
				\WP_CLI::log( '  [dry] event: ' . $slug );
				continue;
			}

			$post_id = self::upsert_by_slug( PostTypes::EVENT, $slug, [
				'post_title'   => $title,
				'post_content' => (string) ( $e['description'] ?? '' ),
				'post_status'  => 'publish',
			] );

			if ( $post_id > 0 ) {
				update_post_meta( $post_id, '_rucphen_event_start', (string) ( $e['start'] ?? '' ) );
				update_post_meta( $post_id, '_rucphen_event_end', (string) ( $e['end'] ?? '' ) );
				update_post_meta( $post_id, '_rucphen_event_location', (string) ( $e['location'] ?? '' ) );
				update_post_meta( $post_id, '_rucphen_event_url', (string) ( $e['url'] ?? '' ) );
				$count++;
			}
		}

		\WP_CLI::log( '  events: ' . $count );
	}

	/**
	 * @param array<int, array<string, mixed>> $podcasts
	 * @param array<string, int> $program_map
	 */
	private static function import_podcasts( array $podcasts, string $source, array $program_map, bool $dry_run ): void {
		$count = 0;
		foreach ( $podcasts as $podcast ) {
			if ( ! is_array( $podcast ) ) {
				continue;
			}

			$slug = sanitize_title( (string) ( $podcast['slug'] ?? '' ) );
			if ( $slug === '' ) {
				continue;
			}

			$description_path = (string) ( $podcast['description_md'] ?? '' );
			$body = '';
			if ( $description_path !== '' ) {
				$file = rtrim( $source, '/' ) . '/' . ltrim( $description_path, '/' );
				if ( is_readable( $file ) ) {
					$body = trim( (string) file_get_contents( $file ) );
				}
			}

			$program_slug = sanitize_title( (string) ( $podcast['program_slug'] ?? '' ) );
			$program_id   = $program_map[ $program_slug ] ?? 0;
			$date         = (string) ( $podcast['date'] ?? '' );

			if ( $dry_run ) {
				\WP_CLI::log( '  [dry] podcast: ' . $slug );
				continue;
			}

			$postarr = [
				'post_title'   => (string) ( $podcast['title'] ?? $slug ),
				'post_content' => $body,
				'post_excerpt' => wp_trim_words( wp_strip_all_tags( $body ), 28, '...' ),
				'post_status'  => 'publish',
			];
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
				$postarr['post_date'] = $date . ' 12:00:00';
			}

			$post_id = self::upsert_by_slug( PostTypes::PODCAST, $slug, $postarr );
			if ( $post_id > 0 ) {
				update_post_meta( $post_id, '_rucphen_podcast_program_slug', $program_slug );
				update_post_meta( $post_id, '_rucphen_podcast_program_id', (int) $program_id );
				update_post_meta( $post_id, '_rucphen_podcast_date', $date );
				update_post_meta( $post_id, '_rucphen_podcast_duration_seconds', (int) ( $podcast['duration_seconds'] ?? 0 ) );
				update_post_meta( $post_id, '_rucphen_podcast_audio_url', self::static_source_url( $source, (string) ( $podcast['audio_url'] ?? '' ) ) );
				update_post_meta( $post_id, '_rucphen_podcast_tracks', Meta::sanitize_podcast_tracks( $podcast['tracks'] ?? [] ) );
				$count++;
			}
		}

		\WP_CLI::log( '  podcasts: ' . $count );
	}

	/**
	 * @param array<int, array<string, mixed>> $news
	 * @param array<int, array<string, mixed>> $videos
	 */
	private static function import_zuidwest_caches( array $news, array $videos, bool $dry_run ): void {
		if ( $dry_run ) {
			\WP_CLI::log( '  [dry] Zuidwest cache: nieuws ' . count( $news ) . ', video ' . count( $videos ) );
			return;
		}

		if ( $news !== [] ) {
			update_option( ZuidwestImporter::OPTION_NEWS_CACHE, self::normalize_zuidwest_items( $news, 'standard' ), false );
		}
		if ( $videos !== [] ) {
			update_option( ZuidwestImporter::OPTION_VIDEOS_CACHE, self::normalize_zuidwest_items( $videos, 'video' ), false );
		}
		update_option( ZuidwestImporter::OPTION_LAST_SUCCESS, gmdate( 'c' ), false );

		\WP_CLI::log( '  Zuidwest cache: nieuws ' . count( $news ) . ', video ' . count( $videos ) );
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 * @return array<int, array<string, mixed>>
	 */
	private static function normalize_zuidwest_items( array $items, string $format ): array {
		$normalized = [];
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$normalized[] = [
				'source_id'       => (string) ( $item['source_id'] ?? $item['id'] ?? '' ),
				'source_name'     => (string) ( $item['source_name'] ?? 'Zuidwest Update' ),
				'source_url'      => (string) ( $item['source_url'] ?? '' ),
				'published_at'    => (string) ( $item['published_at'] ?? '' ),
				'title'           => (string) ( $item['title'] ?? '' ),
				'excerpt'         => (string) ( $item['excerpt'] ?? '' ),
				'image_url'       => (string) ( $item['image_url'] ?? '' ),
				'format'          => (string) ( $item['format'] ?? $format ),
				'video_embed_url' => $item['video_embed_url'] ?? null,
				'region_slug'     => (string) ( $item['region_slug'] ?? '' ),
				'region_label'    => (string) ( $item['region_label'] ?? $item['region_name'] ?? '' ),
			];
		}

		return $normalized;
	}

	private static function import_news_posts( string $dir, bool $dry_run ): void {
		$files = glob( $dir . '/*.md' );
		if ( ! is_array( $files ) ) {
			return;
		}

		$count = 0;
		foreach ( $files as $file ) {
			$base = basename( $file, '.md' );
			$slug = sanitize_title( $base );
			$raw  = (string) file_get_contents( $file );

			[ $front, $body ] = self::split_frontmatter( $raw );

			if ( $dry_run ) {
				\WP_CLI::log( '  [dry] nieuws: ' . $slug );
				continue;
			}

			$post_id = self::upsert_by_slug( 'post', $slug, [
				'post_title'   => (string) ( $front['title'] ?? $base ),
				'post_content' => $body,
				'post_status'  => 'publish',
				'post_date'    => isset( $front['date'] ) ? (string) $front['date'] : '',
				'post_excerpt' => (string) ( $front['excerpt'] ?? '' ),
			] );

			if ( $post_id > 0 ) {
				update_post_meta( $post_id, '_rucphen_news_source', 'redactie' );
				update_post_meta( $post_id, '_rucphen_news_cover', self::static_source_url( dirname( $dir, 2 ), (string) ( $front['cover'] ?? '' ) ) );
				$count++;
			}
		}

		\WP_CLI::log( '  nieuwsposts: ' . $count );
	}

	/**
	 * @param array<string, mixed> $postarr
	 */
	private static function upsert_by_slug( string $post_type, string $slug, array $postarr ): int {
		$existing = get_posts(
			[
				'post_type'      => $post_type,
				'name'           => $slug,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			]
		);

		$postarr['post_type'] = $post_type;
		$postarr['post_name'] = $slug;

		if ( $existing ) {
			$postarr['ID'] = (int) $existing[0];
			return (int) wp_update_post( $postarr );
		}

		return (int) wp_insert_post( $postarr );
	}

	private static function static_source_url( string $source, string $path ): string {
		$path = trim( $path );
		if ( $path === '' || ! str_starts_with( $path, '/' ) ) {
			return $path;
		}

		$file = rtrim( $source, '/' ) . $path;
		if ( ! is_readable( $file ) ) {
			return $path;
		}

		$root = rtrim( wp_normalize_path( ABSPATH ), '/' );
		$base = rtrim( wp_normalize_path( $source ), '/' );
		if ( ! str_starts_with( $base, $root ) ) {
			return $path;
		}

		$relative = '/' . ltrim( substr( $base, strlen( $root ) ), '/' );
		return home_url( trailingslashit( $relative ) . ltrim( $path, '/' ) );
	}

	private static function strip_frontmatter( string $body ): string {
		[ , $rest ] = self::split_frontmatter( $body );
		return $rest;
	}

	/**
	 * @return array{0: array<string, string>, 1: string}
	 */
	private static function split_frontmatter( string $body ): array {
		if ( strncmp( $body, "---\n", 4 ) !== 0 ) {
			return [ [], $body ];
		}

		$end = strpos( $body, "\n---", 4 );
		if ( $end === false ) {
			return [ [], $body ];
		}

		$front_raw = substr( $body, 4, $end - 4 );
		$rest      = trim( substr( $body, $end + 4 ) );

		$front = [];
		foreach ( preg_split( '/\R/', $front_raw ) ?: [] as $line ) {
			if ( strpos( $line, ':' ) === false ) {
				continue;
			}
			[ $k, $v ]   = explode( ':', $line, 2 );
			$front[ trim( $k ) ] = trim( $v, " \t\"'" );
		}

		return [ $front, $rest ];
	}
}
