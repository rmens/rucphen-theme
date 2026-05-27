<?php
/**
 * Taxonomieen voor Radio Rucphen.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

/**
 * Handles Taxonomies functionality.
 */
final class Taxonomies {

	public const REGION       = 'rucphen_region';
	public const PROGRAM_TYPE = 'rucphen_program_type';

	public const ALLOWED_REGION_SLUGS = array( 'etten-leur', 'halderberge', 'roosendaal', 'rucphen', 'zundert' );

	/**
	 * Registers hooks.
	 *
	 * @return void Return value.
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_taxonomies' ), 6 );
		add_action( 'init', array( self::class, 'seed_region_terms' ), 7 );
	}

	/**
	 * Register taxonomies.
	 *
	 * @return void Return value.
	 */
	public static function register_taxonomies(): void {
		register_taxonomy(
			self::REGION,
			array( 'post', PostTypes::EVENT ),
			array(
				'labels'            => array(
					'name'          => __( 'Regio\'s', 'radio-rucphen' ),
					'singular_name' => __( 'Regio', 'radio-rucphen' ),
					'menu_name'     => __( 'Regio\'s', 'radio-rucphen' ),
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'regio',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			self::PROGRAM_TYPE,
			array( PostTypes::PROGRAM ),
			array(
				'labels'            => array(
					'name'          => __( 'Programmatypes', 'radio-rucphen' ),
					'singular_name' => __( 'Programmatype', 'radio-rucphen' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'rewrite'           => false,
			)
		);
	}

	/**
	 * Seed region terms.
	 *
	 * @return void Return value.
	 */
	public static function seed_region_terms(): void {
		if ( '1' === get_option( 'rucphen_regions_seeded' ) ) {
			return;
		}

		$names = array(
			'etten-leur'  => 'Etten-Leur',
			'halderberge' => 'Halderberge',
			'roosendaal'  => 'Roosendaal',
			'rucphen'     => 'Rucphen',
			'zundert'     => 'Zundert',
		);

		foreach ( $names as $slug => $name ) {
			if ( ! term_exists( $slug, self::REGION ) ) {
				wp_insert_term( $name, self::REGION, array( 'slug' => $slug ) );
			}
		}

		update_option( 'rucphen_regions_seeded', '1', false );
	}
}
