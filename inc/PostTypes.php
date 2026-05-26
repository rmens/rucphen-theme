<?php
/**
 * Custom post types voor Radio Rucphen.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

final class PostTypes {

	public const PROGRAM   = 'rucphen_program';
	public const SLOT      = 'rucphen_slot';
	public const PRESENTER = 'rucphen_presenter';
	public const EVENT     = 'rucphen_event';

	public static function register(): void {
		add_action( 'init', [ self::class, 'register_types' ], 5 );
	}

	public static function register_types(): void {
		register_post_type(
			self::PROGRAM,
			[
				'labels' => [
					'name'          => __( 'Programma\'s', 'radio-rucphen' ),
					'singular_name' => __( 'Programma', 'radio-rucphen' ),
					'add_new_item'  => __( 'Nieuw programma', 'radio-rucphen' ),
					'edit_item'     => __( 'Programma bewerken', 'radio-rucphen' ),
					'menu_name'     => __( 'Programma\'s', 'radio-rucphen' ),
				],
				'public'        => true,
				'show_in_rest'  => true,
				'has_archive'   => 'programma',
				'rewrite'       => [ 'slug' => 'programma', 'with_front' => false ],
				'menu_icon'     => 'dashicons-format-audio',
				'menu_position' => 22,
				'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
				'template'      => [ [ 'core/paragraph', [ 'placeholder' => __( 'Programma omschrijving...', 'radio-rucphen' ) ] ] ],
			]
		);

		register_post_type(
			self::SLOT,
			[
				'labels' => [
					'name'          => __( 'Rooster', 'radio-rucphen' ),
					'singular_name' => __( 'Roosterslot', 'radio-rucphen' ),
					'add_new_item'  => __( 'Nieuw roosterslot', 'radio-rucphen' ),
					'edit_item'     => __( 'Slot bewerken', 'radio-rucphen' ),
					'menu_name'     => __( 'Rooster', 'radio-rucphen' ),
				],
				'public'        => false,
				'show_ui'       => true,
				'show_in_rest'  => true,
				'show_in_menu'  => true,
				'menu_icon'     => 'dashicons-calendar-alt',
				'menu_position' => 23,
				'supports'      => [ 'title', 'page-attributes', 'revisions' ],
				'has_archive'   => false,
				'rewrite'       => false,
			]
		);

		register_post_type(
			self::PRESENTER,
			[
				'labels' => [
					'name'          => __( 'Presentatoren', 'radio-rucphen' ),
					'singular_name' => __( 'Presentator', 'radio-rucphen' ),
					'add_new_item'  => __( 'Nieuwe presentator', 'radio-rucphen' ),
					'edit_item'     => __( 'Presentator bewerken', 'radio-rucphen' ),
					'menu_name'     => __( 'Presentatoren', 'radio-rucphen' ),
				],
				'public'        => true,
				'show_in_rest'  => true,
				'has_archive'   => 'djs',
				'rewrite'       => [ 'slug' => 'djs', 'with_front' => false ],
				'menu_icon'     => 'dashicons-businessperson',
				'menu_position' => 24,
				'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
			]
		);

		register_post_type(
			self::EVENT,
			[
				'labels' => [
					'name'          => __( 'Agenda', 'radio-rucphen' ),
					'singular_name' => __( 'Agenda-item', 'radio-rucphen' ),
					'add_new_item'  => __( 'Nieuw agenda-item', 'radio-rucphen' ),
					'edit_item'     => __( 'Agenda-item bewerken', 'radio-rucphen' ),
					'menu_name'     => __( 'Agenda', 'radio-rucphen' ),
				],
				'public'        => true,
				'show_in_rest'  => true,
				'has_archive'   => 'agenda',
				'rewrite'       => [ 'slug' => 'agenda', 'with_front' => false ],
				'menu_icon'     => 'dashicons-tickets-alt',
				'menu_position' => 25,
				'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
			]
		);
	}
}
