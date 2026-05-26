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
	 * Importeer programma's, rooster, presentatoren, events en nieuws uit de
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
		$nieuws_dir    = $source . '/content/nieuws';
		$djs_content   = $source . '/content/djs';

		$config   = self::read_json( $config_path );
		$schedule = self::read_json( $schedule_path );
		$djs      = self::read_json( $djs_path );
		$events   = self::read_json( $events_path );

		\WP_CLI::log( 'Start import...' );

		if ( $config !== null ) {
			self::import_config( $config, $dry_run );
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
			self::import_slots( (array) $schedule['weekly'], $program_map, $presenter_map, $dry_run );
		}

		if ( is_array( $events ) ) {
			self::import_events( $events, $dry_run );
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
	private static function import_slots( array $weekly, array $program_map, array $presenter_map, bool $dry_run ): void {
		$count = 0;
		foreach ( $weekly as $day => $slots ) {
			if ( ! is_array( $slots ) ) {
				continue;
			}
			foreach ( $slots as $i => $slot ) {
				if ( ! is_array( $slot ) ) {
					continue;
				}
				$slug = sanitize_title( $day . '-' . ( $slot['from'] ?? '' ) . '-' . ( $slot['program_slug'] ?? $i ) );

				if ( $dry_run ) {
					\WP_CLI::log( '  [dry] slot: ' . $slug );
					continue;
				}

				$program_id = $program_map[ (string) ( $slot['program_slug'] ?? '' ) ] ?? 0;
				$post_id    = self::upsert_by_slug( PostTypes::SLOT, $slug, [
					'post_title'  => sprintf( '%s %s-%s', ucfirst( (string) $day ), (string) ( $slot['from'] ?? '' ), (string) ( $slot['to'] ?? '' ) ),
					'post_status' => 'publish',
				] );

				if ( $post_id > 0 ) {
					update_post_meta( $post_id, '_rucphen_slot_day', (string) $day );
					update_post_meta( $post_id, '_rucphen_slot_start', (string) ( $slot['from'] ?? '' ) );
					update_post_meta( $post_id, '_rucphen_slot_end', (string) ( $slot['to'] ?? '' ) );
					update_post_meta( $post_id, '_rucphen_slot_program_id', (int) $program_id );

					$ids = [];
					foreach ( (array) ( $slot['dj_slugs'] ?? [] ) as $s ) {
						$pid = $presenter_map[ (string) $s ] ?? 0;
						if ( $pid > 0 ) {
							$ids[] = $pid;
						}
					}
					update_post_meta( $post_id, '_rucphen_slot_presenter_ids', $ids );
					$count++;
				}
			}
		}

		\WP_CLI::log( '  slots: ' . $count );
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
