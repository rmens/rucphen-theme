<?php
/**
 * Lichtgewicht REST search endpoint voor de zoek-overlay in de header.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

/**
 * Handles RestSearch functionality.
 */
final class RestSearch {

	public const NAMESPACE = 'radio-rucphen/v1';
	public const ROUTE     = '/search';

	/**
	 * Registers hooks.
	 *
	 * @return void Return value.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
	}

	/**
	 * Registers REST routes.
	 *
	 * @return void Return value.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'     => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'limit' => array(
						'type'              => 'integer',
						'default'           => 8,
						'sanitize_callback' => static fn( $v ): int => max( 1, min( 20, (int) $v ) ),
					),
				),
				'callback'            => array( self::class, 'handle' ),
			)
		);
	}

	/**
	 * Handles the request.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response Return value.
	 */
	public static function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$q     = (string) $request->get_param( 'q' );
		$limit = (int) $request->get_param( 'limit' );

		if ( strlen( trim( $q ) ) < 2 ) {
			return rest_ensure_response(
				array(
					'query'   => $q,
					'results' => array(),
				)
			);
		}

		$query = new \WP_Query(
			array(
				's'                   => $q,
				'post_type'           => array( 'post', PostTypes::PROGRAM, PostTypes::PRESENTER, PostTypes::PODCAST, PostTypes::EVENT, 'page' ),
				'post_status'         => 'publish',
				'posts_per_page'      => $limit,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);

		$results = array();
		foreach ( $query->posts as $post ) {
			$results[] = array(
				'id'        => $post->ID,
				'title'     => get_the_title( $post ),
				'url'       => get_permalink( $post ),
				'type'      => $post->post_type,
				'excerpt'   => wp_strip_all_tags( get_the_excerpt( $post ) ),
				'thumbnail' => has_post_thumbnail( $post ) ? get_the_post_thumbnail_url( $post, 'rucphen-card' ) : null,
			);
		}

		return rest_ensure_response(
			array(
				'query'   => $q,
				'results' => $results,
			)
		);
	}
}
