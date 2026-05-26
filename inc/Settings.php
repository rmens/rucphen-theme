<?php
/**
 * Settings API en options voor station, stream, contact, frequenties,
 * organisatie, nieuwsbrief en Zuidwest Update import.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

final class Settings {

	public const OPTION_STATION       = 'rucphen_station';
	public const OPTION_STREAM        = 'rucphen_stream';
	public const OPTION_CONTACT       = 'rucphen_contact';
	public const OPTION_FREQUENCIES   = 'rucphen_frequencies';
	public const OPTION_ORGANIZATION  = 'rucphen_organization';
	public const OPTION_NEWSLETTER    = 'rucphen_newsletter';
	public const OPTION_ZWU           = 'rucphen_zwu';

	public const MENU_SLUG = 'radio-rucphen-settings';

	public static function register(): void {
		add_action( 'admin_menu', [ self::class, 'register_menu' ] );
		add_action( 'admin_init', [ self::class, 'register_settings' ] );
		add_action( 'admin_post_rucphen_zwu_run_now', [ self::class, 'handle_run_zwu_now' ] );
	}

	public static function defaults(): array {
		return [
			self::OPTION_STATION => [
				'name'              => 'Radio Rucphen',
				'tagline'           => 'Het geluid van Rucphen',
				'logo_id'           => 0,
				'square_icon_id'    => 0,
				'hero_background_id'=> 0,
			],
			self::OPTION_STREAM => [
				'stream_url'                       => 'https://icecast.zuidwest.cloud/radiorucphen.mp3',
				'metadata_provider'                => 'zwfm-metadata',
				'metadata_websocket_url'           => '',
				'metadata_http_fallback_url'       => '',
				'metadata_stale_after_seconds'     => 60,
				'metadata_reconnect_min_seconds'   => 2,
				'metadata_reconnect_max_seconds'   => 30,
				'cover_lookup_enabled'             => true,
			],
			self::OPTION_CONTACT => [
				'email_studio'           => 'studio@radiorucphen.nl',
				'email_redactie'         => 'redactie@radiorucphen.nl',
				'whatsapp_number'        => '31600000000',
				'whatsapp_default_text'  => 'Hoi studio, mijn verzoekje is...',
				'address'                => '',
				'show_opening_hours'     => false,
			],
			self::OPTION_FREQUENCIES => [
				'fm_mhz'         => '106.4',
				'dab_blocks'     => '8C, 8A',
				'coverage'       => 'West-Brabant en Zeeland',
				'cable_provider' => 'Ziggo',
				'cable_channel'  => '',
				'radioplayer'    => '',
				'tunein'         => '',
			],
			self::OPTION_ORGANIZATION => [
				'legal_name'        => 'Stichting Rucphen RTV',
				'anbi'              => true,
				'kvk'               => '',
				'rsin'              => '',
				'iban'              => '',
				'jaarverslag_id'    => 0,
				'redactiestatuut'   => '',
			],
			self::OPTION_NEWSLETTER => [
				'provider'             => 'none',
				'placeholder_active'   => true,
				'fallback_whatsapp_text' => 'Zet mij op de nieuwsbrief-lijst',
			],
			self::OPTION_ZWU => [
				'base_url'             => 'https://www.zuidwestupdate.nl/wp-json/wp/v2',
				'allowed_region_slugs' => Taxonomies::ALLOWED_REGION_SLUGS,
				'max_news'             => 12,
				'max_videos'           => 8,
				'cron_interval'        => 'hourly',
				'attribution_label'    => 'Zuidwest Update',
				'store_remote_images'  => false,
				'allow_video_embed'    => true,
			],
		];
	}

	public static function get( string $option ): array {
		$defaults = self::defaults();
		$default  = $defaults[ $option ] ?? [];
		$value    = get_option( $option, $default );
		if ( ! is_array( $value ) ) {
			return $default;
		}
		return array_merge( $default, $value );
	}

	public static function register_menu(): void {
		add_menu_page(
			__( 'Radio Rucphen', 'radio-rucphen' ),
			__( 'Radio Rucphen', 'radio-rucphen' ),
			'manage_options',
			self::MENU_SLUG,
			[ self::class, 'render_settings_page' ],
			'dashicons-microphone',
			20
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Instellingen', 'radio-rucphen' ),
			__( 'Instellingen', 'radio-rucphen' ),
			'manage_options',
			self::MENU_SLUG,
			[ self::class, 'render_settings_page' ]
		);
	}

	public static function register_settings(): void {
		$options = [
			self::OPTION_STATION,
			self::OPTION_STREAM,
			self::OPTION_CONTACT,
			self::OPTION_FREQUENCIES,
			self::OPTION_ORGANIZATION,
			self::OPTION_NEWSLETTER,
			self::OPTION_ZWU,
		];

		foreach ( $options as $option ) {
			register_setting(
				'radio_rucphen',
				$option,
				[
					'type'              => 'array',
					'sanitize_callback' => [ self::class, 'sanitize_array' ],
					'default'           => self::defaults()[ $option ] ?? [],
					'show_in_rest'      => false,
				]
			);
		}
	}

	public static function sanitize_array( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$clean = [];
		foreach ( $value as $key => $v ) {
			$key = (string) $key;
			if ( is_array( $v ) ) {
				$clean[ $key ] = array_values( array_map( 'sanitize_text_field', array_map( 'strval', $v ) ) );
			} elseif ( is_bool( $v ) ) {
				$clean[ $key ] = $v;
			} elseif ( is_int( $v ) ) {
				$clean[ $key ] = (int) $v;
			} elseif ( str_ends_with( $key, '_url' ) ) {
				$clean[ $key ] = esc_url_raw( (string) $v );
			} else {
				$clean[ $key ] = sanitize_text_field( (string) $v );
			}
		}

		return $clean;
	}

	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$station       = self::get( self::OPTION_STATION );
		$stream        = self::get( self::OPTION_STREAM );
		$contact       = self::get( self::OPTION_CONTACT );
		$frequencies   = self::get( self::OPTION_FREQUENCIES );
		$organization  = self::get( self::OPTION_ORGANIZATION );
		$newsletter    = self::get( self::OPTION_NEWSLETTER );
		$zwu           = self::get( self::OPTION_ZWU );

		$last_success = get_option( 'rucphen_zwu_last_success_at', '' );
		$last_error   = get_option( 'rucphen_zwu_last_error', '' );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Radio Rucphen instellingen', 'radio-rucphen' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'radio_rucphen' ); ?>

				<h2><?php esc_html_e( 'Station', 'radio-rucphen' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="rucphen_station_name"><?php esc_html_e( 'Naam', 'radio-rucphen' ); ?></label></th>
						<td><input type="text" name="<?php echo esc_attr( self::OPTION_STATION ); ?>[name]" id="rucphen_station_name" value="<?php echo esc_attr( $station['name'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="rucphen_station_tagline"><?php esc_html_e( 'Tagline', 'radio-rucphen' ); ?></label></th>
						<td><input type="text" name="<?php echo esc_attr( self::OPTION_STATION ); ?>[tagline]" id="rucphen_station_tagline" value="<?php echo esc_attr( $station['tagline'] ); ?>" class="regular-text"></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Stream en now-playing', 'radio-rucphen' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="rucphen_stream_url"><?php esc_html_e( 'Stream URL', 'radio-rucphen' ); ?></label></th>
						<td><input type="url" name="<?php echo esc_attr( self::OPTION_STREAM ); ?>[stream_url]" id="rucphen_stream_url" value="<?php echo esc_attr( $stream['stream_url'] ); ?>" class="regular-text code"></td>
					</tr>
					<tr>
						<th><label for="rucphen_meta_ws"><?php esc_html_e( 'zwfm-metadata WebSocket URL', 'radio-rucphen' ); ?></label></th>
						<td><input type="url" name="<?php echo esc_attr( self::OPTION_STREAM ); ?>[metadata_websocket_url]" id="rucphen_meta_ws" value="<?php echo esc_attr( $stream['metadata_websocket_url'] ); ?>" class="regular-text code" placeholder="wss://metadata.example.nl/metadata"></td>
					</tr>
					<tr>
						<th><label for="rucphen_meta_http"><?php esc_html_e( 'HTTP fallback (optioneel)', 'radio-rucphen' ); ?></label></th>
						<td><input type="url" name="<?php echo esc_attr( self::OPTION_STREAM ); ?>[metadata_http_fallback_url]" id="rucphen_meta_http" value="<?php echo esc_attr( $stream['metadata_http_fallback_url'] ); ?>" class="regular-text code"></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Contact', 'radio-rucphen' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label><?php esc_html_e( 'WhatsApp nummer', 'radio-rucphen' ); ?></label></th>
						<td><input type="text" name="<?php echo esc_attr( self::OPTION_CONTACT ); ?>[whatsapp_number]" value="<?php echo esc_attr( $contact['whatsapp_number'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'WhatsApp standaardtekst', 'radio-rucphen' ); ?></label></th>
						<td><input type="text" name="<?php echo esc_attr( self::OPTION_CONTACT ); ?>[whatsapp_default_text]" value="<?php echo esc_attr( $contact['whatsapp_default_text'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Studio e-mail', 'radio-rucphen' ); ?></label></th>
						<td><input type="email" name="<?php echo esc_attr( self::OPTION_CONTACT ); ?>[email_studio]" value="<?php echo esc_attr( $contact['email_studio'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Redactie e-mail', 'radio-rucphen' ); ?></label></th>
						<td><input type="email" name="<?php echo esc_attr( self::OPTION_CONTACT ); ?>[email_redactie]" value="<?php echo esc_attr( $contact['email_redactie'] ); ?>" class="regular-text"></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Frequenties', 'radio-rucphen' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label><?php esc_html_e( 'FM MHz', 'radio-rucphen' ); ?></label></th>
						<td><input type="text" name="<?php echo esc_attr( self::OPTION_FREQUENCIES ); ?>[fm_mhz]" value="<?php echo esc_attr( $frequencies['fm_mhz'] ); ?>" class="small-text"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'DAB+ blokken', 'radio-rucphen' ); ?></label></th>
						<td><input type="text" name="<?php echo esc_attr( self::OPTION_FREQUENCIES ); ?>[dab_blocks]" value="<?php echo esc_attr( $frequencies['dab_blocks'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Kabel', 'radio-rucphen' ); ?></label></th>
						<td>
							<input type="text" name="<?php echo esc_attr( self::OPTION_FREQUENCIES ); ?>[cable_provider]" value="<?php echo esc_attr( $frequencies['cable_provider'] ); ?>" placeholder="<?php esc_attr_e( 'Provider', 'radio-rucphen' ); ?>">
							<input type="text" name="<?php echo esc_attr( self::OPTION_FREQUENCIES ); ?>[cable_channel]" value="<?php echo esc_attr( $frequencies['cable_channel'] ); ?>" placeholder="<?php esc_attr_e( 'Kanaal', 'radio-rucphen' ); ?>">
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Organisatie / ANBI', 'radio-rucphen' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label><?php esc_html_e( 'Juridische naam', 'radio-rucphen' ); ?></label></th>
						<td><input type="text" name="<?php echo esc_attr( self::OPTION_ORGANIZATION ); ?>[legal_name]" value="<?php echo esc_attr( $organization['legal_name'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'KvK / RSIN / IBAN', 'radio-rucphen' ); ?></label></th>
						<td>
							<input type="text" name="<?php echo esc_attr( self::OPTION_ORGANIZATION ); ?>[kvk]" value="<?php echo esc_attr( $organization['kvk'] ); ?>" placeholder="KvK">
							<input type="text" name="<?php echo esc_attr( self::OPTION_ORGANIZATION ); ?>[rsin]" value="<?php echo esc_attr( $organization['rsin'] ); ?>" placeholder="RSIN">
							<input type="text" name="<?php echo esc_attr( self::OPTION_ORGANIZATION ); ?>[iban]" value="<?php echo esc_attr( $organization['iban'] ); ?>" placeholder="IBAN">
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Zuidwest Update import', 'radio-rucphen' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label><?php esc_html_e( 'Max nieuwsitems', 'radio-rucphen' ); ?></label></th>
						<td><input type="number" min="1" max="50" name="<?php echo esc_attr( self::OPTION_ZWU ); ?>[max_news]" value="<?php echo esc_attr( (string) $zwu['max_news'] ); ?>" class="small-text"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Max video-items', 'radio-rucphen' ); ?></label></th>
						<td><input type="number" min="1" max="50" name="<?php echo esc_attr( self::OPTION_ZWU ); ?>[max_videos]" value="<?php echo esc_attr( (string) $zwu['max_videos'] ); ?>" class="small-text"></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Toegestane regio\'s', 'radio-rucphen' ); ?></th>
						<td>
							<code><?php echo esc_html( implode( ', ', Taxonomies::ALLOWED_REGION_SLUGS ) ); ?></code><br>
							<small><?php esc_html_e( 'Hardcoded in het theme. Andere regio\'s worden door de importer genegeerd.', 'radio-rucphen' ); ?></small>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Importstatus', 'radio-rucphen' ); ?></th>
						<td>
							<p>
								<strong><?php esc_html_e( 'Laatste succes:', 'radio-rucphen' ); ?></strong>
								<?php echo $last_success ? esc_html( $last_success ) : '<em>' . esc_html__( 'nog niet uitgevoerd', 'radio-rucphen' ) . '</em>'; ?>
							</p>
							<?php if ( $last_error ) : ?>
								<p style="color:#b32d2e"><strong><?php esc_html_e( 'Laatste fout:', 'radio-rucphen' ); ?></strong> <?php echo esc_html( $last_error ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Zuidwest Update nu draaien', 'radio-rucphen' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="rucphen_zwu_run_now">
				<?php wp_nonce_field( 'rucphen_zwu_run_now' ); ?>
				<?php submit_button( __( 'Importeer Zuidwest Update nu', 'radio-rucphen' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	public static function handle_run_zwu_now(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Onvoldoende rechten.', 'radio-rucphen' ) );
		}

		check_admin_referer( 'rucphen_zwu_run_now' );

		ZuidwestImporter::run();

		wp_safe_redirect( add_query_arg( 'rucphen_zwu', 'done', admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) );
		exit;
	}
}
