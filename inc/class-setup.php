<?php
/**
 * Theme supports, menus en image sizes.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

/**
 * Handles Setup functionality.
 */
final class Setup {

	/**
	 * Registers hooks.
	 *
	 * @return void Return value.
	 */
	public static function register(): void {
		add_action( 'after_setup_theme', array( self::class, 'theme_supports' ) );
		add_action( 'after_setup_theme', array( self::class, 'register_menus' ) );
		add_action( 'after_setup_theme', array( self::class, 'image_sizes' ) );
		add_action( 'wp_head', array( self::class, 'preconnect_fonts' ), 2 );
		add_filter( 'block_categories_all', array( self::class, 'register_block_category' ), 10, 1 );
	}

	/**
	 * Register block category.
	 *
	 * @param array $categories Categories.
	 * @return array Return value.
	 */
	public static function register_block_category( array $categories ): array {
		array_unshift(
			$categories,
			array(
				'slug'  => 'radio-rucphen',
				'title' => __( 'Radio Rucphen', 'radio-rucphen' ),
				'icon'  => 'microphone',
			)
		);
		return $categories;
	}

	/**
	 * Preconnect fonts.
	 *
	 * @return void Return value.
	 */
	public static function preconnect_fonts(): void {
		echo "<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n";
		echo "<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n";
	}

	/**
	 * Theme supports.
	 *
	 * @return void Return value.
	 */
	public static function theme_supports(): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

		load_theme_textdomain( 'radio-rucphen', RUCPHEN_THEME_DIR . 'languages' );
	}

	/**
	 * Register menus.
	 *
	 * @return void Return value.
	 */
	public static function register_menus(): void {
		register_nav_menus(
			array(
				'primary'            => __( 'Primair menu', 'radio-rucphen' ),
				'radio'              => __( 'Radio (header secundair)', 'radio-rucphen' ),
				'mobile'             => __( 'Mobiel menu', 'radio-rucphen' ),
				'footer_listen'      => __( 'Footer - Luisteren', 'radio-rucphen' ),
				'footer_participate' => __( 'Footer - Meedoen', 'radio-rucphen' ),
				'footer_news'        => __( 'Footer - Nieuws', 'radio-rucphen' ),
				'footer_legal'       => __( 'Footer - Juridisch', 'radio-rucphen' ),
			)
		);
	}

	/**
	 * Image sizes.
	 *
	 * @return void Return value.
	 */
	public static function image_sizes(): void {
		add_image_size( 'rucphen-card', 768, 432, true );
		add_image_size( 'rucphen-hero', 1920, 900, true );
		add_image_size( 'rucphen-portrait', 600, 750, true );
	}
}
