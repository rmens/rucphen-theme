<?php
/**
 * Registreer alle CPT-meta velden met show_in_rest.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

final class Meta {

	public static function register(): void {
		add_action( 'init', [ self::class, 'register_meta' ], 20 );
	}

	public static function register_meta(): void {
		$program_fields = [
			'_rucphen_program_short_description' => 'string',
			'_rucphen_program_long_description'  => 'string',
			'_rucphen_program_featured'          => 'boolean',
			'_rucphen_program_default_start'     => 'string',
			'_rucphen_program_default_end'       => 'string',
			'_rucphen_program_color'             => 'string',
			'_rucphen_program_presenter_ids'     => 'array',
		];
		foreach ( $program_fields as $key => $type ) {
			self::register_field( PostTypes::PROGRAM, $key, $type );
		}

		$slot_fields = [
			'_rucphen_slot_day'           => 'string',
			'_rucphen_slot_start'         => 'string',
			'_rucphen_slot_end'           => 'string',
			'_rucphen_slot_program_id'    => 'integer',
			'_rucphen_slot_presenter_ids' => 'array',
			'_rucphen_slot_note'          => 'string',
			'_rucphen_slot_is_exception'  => 'boolean',
			'_rucphen_slot_valid_from'    => 'string',
			'_rucphen_slot_valid_until'   => 'string',
		];
		foreach ( $slot_fields as $key => $type ) {
			self::register_field( PostTypes::SLOT, $key, $type );
		}

		$presenter_fields = [
			'_rucphen_presenter_tagline'   => 'string',
			'_rucphen_presenter_order'     => 'integer',
			'_rucphen_presenter_facebook'  => 'string',
			'_rucphen_presenter_instagram' => 'string',
			'_rucphen_presenter_website'   => 'string',
		];
		foreach ( $presenter_fields as $key => $type ) {
			self::register_field( PostTypes::PRESENTER, $key, $type );
		}

		$event_fields = [
			'_rucphen_event_start'    => 'string',
			'_rucphen_event_end'      => 'string',
			'_rucphen_event_location' => 'string',
			'_rucphen_event_url'      => 'string',
		];
		foreach ( $event_fields as $key => $type ) {
			self::register_field( PostTypes::EVENT, $key, $type );
		}

		self::register_field( 'post', '_rucphen_news_source', 'string' );
	}

	private static function register_field( string $post_type, string $key, string $type ): void {
		$args = [
			'type'              => $type === 'array' ? 'array' : $type,
			'single'            => true,
			'show_in_rest'      => $type === 'array'
				? [ 'schema' => [ 'type' => 'array', 'items' => [ 'type' => 'integer' ] ] ]
				: true,
			'auth_callback'     => static function () use ( $post_type ): bool {
				$obj = get_post_type_object( $post_type );
				return $obj !== null && current_user_can( $obj->cap->edit_posts );
			},
			'sanitize_callback' => self::sanitizer_for( $type ),
		];

		register_post_meta( $post_type, $key, $args );
	}

	private static function sanitizer_for( string $type ): callable {
		return match ( $type ) {
			'integer' => static fn( $v ) => (int) $v,
			'boolean' => static fn( $v ) => (bool) $v,
			'array'   => static function ( $v ): array {
				if ( ! is_array( $v ) ) {
					return [];
				}
				return array_values( array_map( 'intval', $v ) );
			},
			default   => static fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '',
		};
	}
}
