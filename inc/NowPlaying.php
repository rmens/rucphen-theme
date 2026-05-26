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

	public static function register(): void {
		// Nothing to hook for now; data is exposed via Assets::enqueue_frontend().
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
}
