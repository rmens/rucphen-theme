<?php
/**
 * Registreer CPT-meta en beheer velden in wp-admin.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

final class Meta {

	public const HERO_TEXT_META = '_rucphen_hero_text';

	private const HERO_NONCE      = 'rucphen_hero_meta_nonce';
	private const PROGRAM_NONCE   = 'rucphen_program_meta_nonce';
	private const PRESENTER_NONCE = 'rucphen_presenter_meta_nonce';

	public static function register(): void {
		add_action( 'init', [ self::class, 'register_meta' ], 20 );
		add_action( 'add_meta_boxes', [ self::class, 'add_meta_boxes' ] );
		add_action( 'save_post', [ self::class, 'save_hero_meta' ], 10, 2 );
		add_action( 'save_post_' . PostTypes::PROGRAM, [ self::class, 'save_program_meta' ], 10, 2 );
		add_action( 'save_post_' . PostTypes::PRESENTER, [ self::class, 'save_presenter_programs' ], 10, 2 );
	}

	public static function register_meta(): void {
		foreach ( self::hero_post_types() as $post_type ) {
			self::register_field( $post_type, self::HERO_TEXT_META, 'textarea' );
		}

		$program_fields = [
			'_rucphen_program_short_description' => 'string',
			'_rucphen_program_long_description'  => 'string',
			'_rucphen_program_featured'          => 'boolean',
			'_rucphen_program_default_start'     => 'string',
			'_rucphen_program_default_end'       => 'string',
			'_rucphen_program_color'             => 'string',
			'_rucphen_program_presenter_ids'     => 'array_int',
		];
		foreach ( $program_fields as $key => $type ) {
			self::register_field( PostTypes::PROGRAM, $key, $type );
		}
		self::register_airtimes_field();

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

		$podcast_fields = [
			'_rucphen_podcast_program_slug'     => 'string',
			'_rucphen_podcast_program_id'       => 'integer',
			'_rucphen_podcast_date'             => 'string',
			'_rucphen_podcast_duration_seconds' => 'integer',
			'_rucphen_podcast_audio_url'        => 'string',
		];
		foreach ( $podcast_fields as $key => $type ) {
			self::register_field( PostTypes::PODCAST, $key, $type );
		}
		self::register_podcast_tracks_field();

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
		self::register_field( 'post', '_rucphen_news_cover', 'string' );
	}

	public static function add_meta_boxes(): void {
		foreach ( self::hero_post_types() as $post_type ) {
			add_meta_box(
				'rucphen-page-hero',
				__( 'Hero', 'radio-rucphen' ),
				[ self::class, 'render_hero_box' ],
				$post_type,
				'normal',
				'default'
			);
		}

		add_meta_box(
			'rucphen-program-broadcast',
			__( 'Uitzending', 'radio-rucphen' ),
			[ self::class, 'render_program_box' ],
			PostTypes::PROGRAM,
			'normal',
			'high'
		);

		add_meta_box(
			'rucphen-presenter-programs',
			__( 'Programma\'s', 'radio-rucphen' ),
			[ self::class, 'render_presenter_programs_box' ],
			PostTypes::PRESENTER,
			'side',
			'default'
		);
	}

	public static function render_hero_box( \WP_Post $post ): void {
		wp_nonce_field( self::HERO_NONCE, self::HERO_NONCE );

		$text = (string) get_post_meta( $post->ID, self::HERO_TEXT_META, true );
		?>
		<p>
			<label for="rucphen_hero_text"><?php esc_html_e( 'Hero-tekst', 'radio-rucphen' ); ?></label>
		</p>
		<textarea id="rucphen_hero_text" name="rucphen_hero_text" class="widefat" rows="3"><?php echo esc_textarea( $text ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Wordt getoond door het Hero-tekst block. Laat leeg om de samenvatting te gebruiken.', 'radio-rucphen' ); ?></p>
		<?php
	}

	public static function render_program_box( \WP_Post $post ): void {
		wp_nonce_field( self::PROGRAM_NONCE, self::PROGRAM_NONCE );

		$airtimes = self::airtimes_by_day( self::program_airtimes( $post->ID ) );
		$presenter_ids = array_map( 'intval', (array) get_post_meta( $post->ID, '_rucphen_program_presenter_ids', true ) );
		$presenters = get_posts(
			[
				'post_type'      => PostTypes::PRESENTER,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			]
		);
		?>
		<p><?php esc_html_e( 'Beheer hier de uitzendmomenten en vaste presentatoren van dit programma.', 'radio-rucphen' ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Dag', 'radio-rucphen' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Start', 'radio-rucphen' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Einde', 'radio-rucphen' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( self::day_options() as $day => $label ) :
					$row = $airtimes[ $day ] ?? null;
					?>
					<tr>
						<td>
							<label>
								<input type="checkbox" name="rucphen_program_airtimes[<?php echo esc_attr( $day ); ?>][enabled]" value="1" <?php checked( $row !== null ); ?>>
								<?php echo esc_html( $label ); ?>
							</label>
						</td>
						<td><input type="time" name="rucphen_program_airtimes[<?php echo esc_attr( $day ); ?>][start]" value="<?php echo esc_attr( (string) ( $row['start'] ?? '' ) ); ?>"></td>
						<td><input type="time" name="rucphen_program_airtimes[<?php echo esc_attr( $day ); ?>][end]" value="<?php echo esc_attr( (string) ( $row['end'] ?? '' ) ); ?>"></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Presentatoren', 'radio-rucphen' ); ?></h3>
		<?php if ( $presenters === [] ) : ?>
			<p><?php esc_html_e( 'Nog geen presentatoren aangemaakt.', 'radio-rucphen' ); ?></p>
		<?php else : ?>
			<?php foreach ( $presenters as $presenter ) : ?>
				<p>
					<label>
						<input type="checkbox" name="rucphen_program_presenter_ids[]" value="<?php echo esc_attr( (string) $presenter->ID ); ?>" <?php checked( in_array( $presenter->ID, $presenter_ids, true ) ); ?>>
						<?php echo esc_html( get_the_title( $presenter ) ); ?>
					</label>
				</p>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php
	}

	public static function render_presenter_programs_box( \WP_Post $post ): void {
		wp_nonce_field( self::PRESENTER_NONCE, self::PRESENTER_NONCE );

		$programs = get_posts(
			[
				'post_type'      => PostTypes::PROGRAM,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			]
		);

		if ( $programs === [] ) {
			echo '<p>' . esc_html__( 'Nog geen programma\'s aangemaakt.', 'radio-rucphen' ) . '</p>';
			return;
		}

		foreach ( $programs as $program ) {
			$ids = array_map( 'intval', (array) get_post_meta( $program->ID, '_rucphen_program_presenter_ids', true ) );
			?>
			<p>
				<label>
					<input type="checkbox" name="rucphen_presenter_program_ids[]" value="<?php echo esc_attr( (string) $program->ID ); ?>" <?php checked( in_array( $post->ID, $ids, true ) ); ?>>
					<?php echo esc_html( get_the_title( $program ) ); ?>
				</label>
			</p>
			<?php
		}
	}

	public static function save_hero_meta( int $post_id, \WP_Post $post ): void {
		if ( ! in_array( $post->post_type, self::hero_post_types(), true ) || ! self::can_save( $post_id, self::HERO_NONCE ) ) {
			return;
		}

		$text = isset( $_POST['rucphen_hero_text'] )
			? sanitize_textarea_field( (string) wp_unslash( $_POST['rucphen_hero_text'] ) )
			: '';

		if ( $text === '' ) {
			delete_post_meta( $post_id, self::HERO_TEXT_META );
			return;
		}

		update_post_meta( $post_id, self::HERO_TEXT_META, $text );
	}

	public static function save_program_meta( int $post_id, \WP_Post $post ): void {
		if ( ! self::can_save( $post_id, self::PROGRAM_NONCE ) ) {
			return;
		}

		$raw_rows = isset( $_POST['rucphen_program_airtimes'] ) && is_array( $_POST['rucphen_program_airtimes'] )
			? (array) wp_unslash( $_POST['rucphen_program_airtimes'] )
			: [];
		$airtimes = [];
		foreach ( array_keys( self::day_options() ) as $day ) {
			$row = isset( $raw_rows[ $day ] ) && is_array( $raw_rows[ $day ] ) ? $raw_rows[ $day ] : [];
			if ( empty( $row['enabled'] ) ) {
				continue;
			}

			$start = self::sanitize_time( (string) ( $row['start'] ?? '' ) );
			$end   = self::sanitize_time( (string) ( $row['end'] ?? '' ) );
			if ( $start === '' || $end === '' ) {
				continue;
			}

			$airtimes[] = [
				'day'   => $day,
				'start' => $start,
				'end'   => $end,
			];
		}

		update_post_meta( $post_id, '_rucphen_program_airtimes', $airtimes );
		if ( $airtimes !== [] ) {
			update_post_meta( $post_id, '_rucphen_program_default_start', $airtimes[0]['start'] );
			update_post_meta( $post_id, '_rucphen_program_default_end', $airtimes[0]['end'] );
		} else {
			delete_post_meta( $post_id, '_rucphen_program_default_start' );
			delete_post_meta( $post_id, '_rucphen_program_default_end' );
		}

		$raw_presenters = isset( $_POST['rucphen_program_presenter_ids'] ) && is_array( $_POST['rucphen_program_presenter_ids'] )
			? (array) wp_unslash( $_POST['rucphen_program_presenter_ids'] )
			: [];
		$presenter_ids = array_values( array_unique( array_filter( array_map( 'intval', $raw_presenters ) ) ) );
		update_post_meta( $post_id, '_rucphen_program_presenter_ids', $presenter_ids );
	}

	public static function save_presenter_programs( int $post_id, \WP_Post $post ): void {
		if ( ! self::can_save( $post_id, self::PRESENTER_NONCE ) ) {
			return;
		}

		$raw_programs = isset( $_POST['rucphen_presenter_program_ids'] ) && is_array( $_POST['rucphen_presenter_program_ids'] )
			? (array) wp_unslash( $_POST['rucphen_presenter_program_ids'] )
			: [];
		$selected = array_values( array_unique( array_filter( array_map( 'intval', $raw_programs ) ) ) );

		$program_ids = get_posts(
			[
				'post_type'      => PostTypes::PROGRAM,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			]
		);

		foreach ( $program_ids as $program_id ) {
			$ids = array_values( array_unique( array_map( 'intval', (array) get_post_meta( (int) $program_id, '_rucphen_program_presenter_ids', true ) ) ) );
			if ( in_array( (int) $program_id, $selected, true ) ) {
				$ids[] = $post_id;
			} else {
				$ids = array_values( array_diff( $ids, [ $post_id ] ) );
			}
			update_post_meta( (int) $program_id, '_rucphen_program_presenter_ids', array_values( array_unique( $ids ) ) );
		}
	}

	/**
	 * @param mixed $value
	 * @return array<int, array{day:string,start:string,end:string}>
	 */
	public static function sanitize_airtimes( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$valid_days = array_keys( self::day_options() );
		$airtimes = [];
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$day   = sanitize_key( (string) ( $row['day'] ?? '' ) );
			$start = self::sanitize_time( (string) ( $row['start'] ?? '' ) );
			$end   = self::sanitize_time( (string) ( $row['end'] ?? '' ) );
			if ( ! in_array( $day, $valid_days, true ) || $start === '' || $end === '' ) {
				continue;
			}

			$airtimes[] = [
				'day'   => $day,
				'start' => $start,
				'end'   => $end,
			];
		}

		return $airtimes;
	}

	private static function register_field( string $post_type, string $key, string $type ): void {
		$args = [
			'type'              => str_starts_with( $type, 'array_' ) ? 'array' : $type,
			'single'            => true,
			'show_in_rest'      => $type === 'array_int'
				? [ 'schema' => [ 'type' => 'array', 'items' => [ 'type' => 'integer' ] ] ]
				: true,
			'auth_callback'     => static fn(): bool => self::can_edit_type( $post_type ),
			'sanitize_callback' => self::sanitizer_for( $type ),
		];

		register_post_meta( $post_type, $key, $args );
	}

	private static function register_airtimes_field(): void {
		register_post_meta(
			PostTypes::PROGRAM,
			'_rucphen_program_airtimes',
			[
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'day'   => [ 'type' => 'string' ],
								'start' => [ 'type' => 'string' ],
								'end'   => [ 'type' => 'string' ],
							],
						],
					],
				],
				'auth_callback'     => static fn(): bool => self::can_edit_type( PostTypes::PROGRAM ),
				'sanitize_callback' => [ self::class, 'sanitize_airtimes' ],
			]
		);
	}

	private static function register_podcast_tracks_field(): void {
		register_post_meta(
			PostTypes::PODCAST,
			'_rucphen_podcast_tracks',
			[
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'time'   => [ 'type' => 'string' ],
								'artist' => [ 'type' => 'string' ],
								'title'  => [ 'type' => 'string' ],
							],
						],
					],
				],
				'auth_callback'     => static fn(): bool => self::can_edit_type( PostTypes::PODCAST ),
				'sanitize_callback' => [ self::class, 'sanitize_podcast_tracks' ],
			]
		);
	}

	/**
	 * @param mixed $value
	 * @return array<int, array{time:string,artist:string,title:string}>
	 */
	public static function sanitize_podcast_tracks( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$tracks = [];
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$time   = sanitize_text_field( (string) ( $row['time'] ?? '' ) );
			$artist = sanitize_text_field( (string) ( $row['artist'] ?? '' ) );
			$title  = sanitize_text_field( (string) ( $row['title'] ?? '' ) );
			if ( $time === '' || ( $artist === '' && $title === '' ) ) {
				continue;
			}

			$tracks[] = [
				'time'   => $time,
				'artist' => $artist,
				'title'  => $title,
			];
		}

		return $tracks;
	}

	private static function can_edit_type( string $post_type ): bool {
		$obj = get_post_type_object( $post_type );
		return $obj !== null && current_user_can( $obj->cap->edit_posts );
	}

	private static function can_save( int $post_id, string $nonce_name ): bool {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return false;
		}

		$nonce = isset( $_POST[ $nonce_name ] ) ? (string) wp_unslash( $_POST[ $nonce_name ] ) : '';
		if ( $nonce === '' || ! wp_verify_nonce( $nonce, $nonce_name ) ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	private static function sanitizer_for( string $type ): callable {
		return match ( $type ) {
			'integer'   => static fn( $v ) => (int) $v,
			'boolean'   => static fn( $v ) => (bool) $v,
			'textarea'  => static fn( $v ) => is_string( $v ) ? sanitize_textarea_field( $v ) : '',
			'array_int' => static function ( $v ): array {
				if ( ! is_array( $v ) ) {
					return [];
				}
				return array_values( array_unique( array_filter( array_map( 'intval', $v ) ) ) );
			},
			default     => static fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '',
		};
	}

	/**
	 * @return array<int, string>
	 */
	private static function hero_post_types(): array {
		return [
			'page',
			'post',
			PostTypes::PROGRAM,
			PostTypes::PRESENTER,
			PostTypes::PODCAST,
			PostTypes::EVENT,
		];
	}

	/**
	 * @return array<string, string>
	 */
	private static function day_options(): array {
		return [
			'monday'    => __( 'Maandag', 'radio-rucphen' ),
			'tuesday'   => __( 'Dinsdag', 'radio-rucphen' ),
			'wednesday' => __( 'Woensdag', 'radio-rucphen' ),
			'thursday'  => __( 'Donderdag', 'radio-rucphen' ),
			'friday'    => __( 'Vrijdag', 'radio-rucphen' ),
			'saturday'  => __( 'Zaterdag', 'radio-rucphen' ),
			'sunday'    => __( 'Zondag', 'radio-rucphen' ),
		];
	}

	/**
	 * @return array<int, array{day:string,start:string,end:string}>
	 */
	private static function program_airtimes( int $post_id ): array {
		return self::sanitize_airtimes( get_post_meta( $post_id, '_rucphen_program_airtimes', true ) );
	}

	/**
	 * @param array<int, array{day:string,start:string,end:string}> $airtimes
	 * @return array<string, array{day:string,start:string,end:string}>
	 */
	private static function airtimes_by_day( array $airtimes ): array {
		$by_day = [];
		foreach ( $airtimes as $airtime ) {
			if ( ! isset( $by_day[ $airtime['day'] ] ) ) {
				$by_day[ $airtime['day'] ] = $airtime;
			}
		}
		return $by_day;
	}

	private static function sanitize_time( string $value ): string {
		$value = trim( $value );
		return preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value ) ? $value : '';
	}
}
