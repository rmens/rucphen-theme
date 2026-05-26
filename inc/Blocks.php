<?php
/**
 * Dynamic blocks via server-side render callbacks.
 *
 * MVP-set: live-hero, sticky-player, program-schedule, news-mixed-grid,
 * video-grid, events-grid, featured-programs, frequency-grid, whatsapp-cta.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

final class Blocks {

	public const NAMESPACE = 'rucphen';

	public static function register(): void {
		add_action( 'init', [ self::class, 'register_blocks' ] );
	}

	public static function register_blocks(): void {
		$blocks = [
			'live-hero'         => [ self::class, 'render_live_hero' ],
			'sticky-player'     => [ self::class, 'render_sticky_player' ],
			'program-schedule'  => [ self::class, 'render_program_schedule' ],
			'featured-programs' => [ self::class, 'render_featured_programs' ],
			'news-mixed-grid'   => [ self::class, 'render_news_mixed_grid' ],
			'video-grid'        => [ self::class, 'render_video_grid' ],
			'events-grid'       => [ self::class, 'render_events_grid' ],
			'frequency-grid'    => [ self::class, 'render_frequency_grid' ],
			'whatsapp-cta'      => [ self::class, 'render_whatsapp_cta' ],
		];

		foreach ( $blocks as $name => $callback ) {
			register_block_type(
				self::NAMESPACE . '/' . $name,
				[
					'api_version'     => 3,
					'render_callback' => $callback,
				]
			);
		}
	}

	public static function render_live_hero(): string {
		$station = Settings::get( Settings::OPTION_STATION );

		$hero_id = (int) ( $station['hero_background_id'] ?? 0 );
		$bg      = $hero_id > 0 ? wp_get_attachment_image_url( $hero_id, 'rucphen-hero' ) : '';

		ob_start();
		?>
		<section class="rucphen-live-hero" data-component="live-hero" <?php echo $bg ? 'style="background-image: url(' . esc_url( $bg ) . ')"' : ''; ?>>
			<div class="rucphen-live-hero__inner">
				<p class="rucphen-live-hero__eyebrow"><?php esc_html_e( 'Nu live op', 'radio-rucphen' ); ?> <?php echo esc_html( (string) ( $station['name'] ?? 'Radio Rucphen' ) ); ?></p>
				<h1 class="rucphen-live-hero__title" data-hero-title><?php echo esc_html( (string) ( $station['tagline'] ?? 'Het geluid van Rucphen' ) ); ?></h1>
				<p class="rucphen-live-hero__now" data-hero-now></p>
				<button type="button" class="rucphen-live-hero__play" data-hero-play aria-label="<?php esc_attr_e( 'Speel live af', 'radio-rucphen' ); ?>">
					<?php IconRegistry::print( 'play', [ 'size' => 28 ] ); ?>
					<span><?php esc_html_e( 'Luister live', 'radio-rucphen' ); ?></span>
				</button>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_sticky_player(): string {
		ob_start();
		?>
		<div class="rucphen-sticky-player" data-component="sticky-player" hidden>
			<div class="rucphen-sticky-player__cover" data-player-cover></div>
			<div class="rucphen-sticky-player__meta">
				<span class="rucphen-sticky-player__title" data-player-title><?php esc_html_e( 'Radio Rucphen', 'radio-rucphen' ); ?></span>
				<span class="rucphen-sticky-player__artist" data-player-artist></span>
			</div>
			<button type="button" class="rucphen-sticky-player__toggle" data-player-toggle aria-label="<?php esc_attr_e( 'Afspelen/pauzeren', 'radio-rucphen' ); ?>">
				<?php IconRegistry::print( 'play', [ 'class' => 'icon icon-play', 'size' => 28 ] ); ?>
				<?php IconRegistry::print( 'pause', [ 'class' => 'icon icon-pause', 'size' => 28 ] ); ?>
			</button>
			<label class="rucphen-sticky-player__volume">
				<span class="screen-reader-text"><?php esc_html_e( 'Volume', 'radio-rucphen' ); ?></span>
				<input type="range" min="0" max="100" value="80" data-player-volume>
			</label>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_program_schedule(): string {
		$days = [
			'monday'    => __( 'Maandag', 'radio-rucphen' ),
			'tuesday'   => __( 'Dinsdag', 'radio-rucphen' ),
			'wednesday' => __( 'Woensdag', 'radio-rucphen' ),
			'thursday'  => __( 'Donderdag', 'radio-rucphen' ),
			'friday'    => __( 'Vrijdag', 'radio-rucphen' ),
			'saturday'  => __( 'Zaterdag', 'radio-rucphen' ),
			'sunday'    => __( 'Zondag', 'radio-rucphen' ),
		];

		$today    = strtolower( wp_date( 'l' ) );
		$by_day   = self::slots_grouped_by_day();
		$today_en = strtr( $today, [ 'monday' => 'monday', 'maandag' => 'monday', 'dinsdag' => 'tuesday', 'woensdag' => 'wednesday', 'donderdag' => 'thursday', 'vrijdag' => 'friday', 'zaterdag' => 'saturday', 'zondag' => 'sunday' ] );

		ob_start();
		?>
		<section class="rucphen-schedule" data-component="schedule" data-today="<?php echo esc_attr( $today_en ); ?>">
			<header class="rucphen-schedule__head">
				<h2><?php esc_html_e( 'Programmagids', 'radio-rucphen' ); ?></h2>
			</header>
			<nav class="rucphen-schedule__tabs" role="tablist">
				<?php foreach ( $days as $slug => $label ) : ?>
					<button type="button"
						role="tab"
						data-day="<?php echo esc_attr( $slug ); ?>"
						aria-selected="<?php echo $slug === $today_en ? 'true' : 'false'; ?>">
						<?php echo esc_html( $label ); ?>
					</button>
				<?php endforeach; ?>
			</nav>
			<?php foreach ( $days as $slug => $label ) : ?>
				<div class="rucphen-schedule__day" data-day-panel="<?php echo esc_attr( $slug ); ?>" <?php echo $slug === $today_en ? '' : 'hidden'; ?>>
					<?php if ( empty( $by_day[ $slug ] ) ) : ?>
						<p class="rucphen-schedule__empty"><?php esc_html_e( 'Geen programma\'s gepland.', 'radio-rucphen' ); ?></p>
					<?php else : ?>
						<ol class="rucphen-schedule__list">
							<?php foreach ( $by_day[ $slug ] as $slot ) : ?>
								<li class="rucphen-schedule__item">
									<span class="rucphen-schedule__time">
										<?php echo esc_html( $slot['start'] ); ?> - <?php echo esc_html( $slot['end'] ); ?>
									</span>
									<span class="rucphen-schedule__title">
										<?php if ( ! empty( $slot['program_url'] ) ) : ?>
											<a href="<?php echo esc_url( $slot['program_url'] ); ?>"><?php echo esc_html( $slot['program_title'] ); ?></a>
										<?php else : ?>
											<?php echo esc_html( $slot['program_title'] ); ?>
										<?php endif; ?>
									</span>
									<?php if ( ! empty( $slot['presenters'] ) ) : ?>
										<span class="rucphen-schedule__presenters">
											<?php echo esc_html( implode( ', ', $slot['presenters'] ) ); ?>
										</span>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private static function slots_grouped_by_day(): array {
		$query = new \WP_Query(
			[
				'post_type'      => PostTypes::SLOT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			]
		);

		$grouped = [];
		foreach ( $query->posts as $post ) {
			$day = (string) get_post_meta( $post->ID, '_rucphen_slot_day', true );
			if ( $day === '' ) {
				continue;
			}

			$program_id = (int) get_post_meta( $post->ID, '_rucphen_slot_program_id', true );
			$program    = $program_id > 0 ? get_post( $program_id ) : null;

			$presenter_ids = (array) get_post_meta( $post->ID, '_rucphen_slot_presenter_ids', true );
			$presenters    = [];
			foreach ( $presenter_ids as $pid ) {
				$p = get_post( (int) $pid );
				if ( $p instanceof \WP_Post ) {
					$presenters[] = get_the_title( $p );
				}
			}

			$grouped[ $day ][] = [
				'start'         => (string) get_post_meta( $post->ID, '_rucphen_slot_start', true ),
				'end'           => (string) get_post_meta( $post->ID, '_rucphen_slot_end', true ),
				'program_title' => $program instanceof \WP_Post ? get_the_title( $program ) : get_the_title( $post ),
				'program_url'   => $program instanceof \WP_Post ? get_permalink( $program ) : '',
				'presenters'    => $presenters,
			];
		}

		foreach ( $grouped as $day => $slots ) {
			usort( $grouped[ $day ], static fn( $a, $b ) => strcmp( $a['start'], $b['start'] ) );
		}

		return $grouped;
	}

	public static function render_featured_programs(): string {
		$query = new \WP_Query(
			[
				'post_type'      => PostTypes::PROGRAM,
				'post_status'    => 'publish',
				'posts_per_page' => 6,
				'meta_query'     => [
					[
						'key'   => '_rucphen_program_featured',
						'value' => '1',
					],
				],
				'no_found_rows'  => true,
			]
		);

		ob_start();
		?>
		<section class="rucphen-featured-programs">
			<header><h2><?php esc_html_e( 'Uitgelicht', 'radio-rucphen' ); ?></h2></header>
			<div class="rucphen-featured-programs__grid">
				<?php foreach ( $query->posts as $post ) :
					$short = (string) get_post_meta( $post->ID, '_rucphen_program_short_description', true );
					?>
					<a class="rucphen-card" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
						<?php if ( has_post_thumbnail( $post ) ) : ?>
							<?php echo get_the_post_thumbnail( $post, 'rucphen-card', [ 'class' => 'rucphen-card__image', 'loading' => 'lazy' ] ); ?>
						<?php endif; ?>
						<div class="rucphen-card__body">
							<h3 class="rucphen-card__title"><?php echo esc_html( get_the_title( $post ) ); ?></h3>
							<?php if ( $short !== '' ) : ?>
								<p class="rucphen-card__excerpt"><?php echo esc_html( $short ); ?></p>
							<?php endif; ?>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_news_mixed_grid(): string {
		$native = get_posts(
			[
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 4,
				'no_found_rows'  => true,
			]
		);

		$cards = [];
		foreach ( $native as $post ) {
			$cards[] = [
				'title'        => get_the_title( $post ),
				'url'          => get_permalink( $post ),
				'excerpt'      => wp_strip_all_tags( get_the_excerpt( $post ) ),
				'image'        => has_post_thumbnail( $post ) ? get_the_post_thumbnail_url( $post, 'rucphen-card' ) : '',
				'published_at' => get_post_time( 'c', true, $post ),
				'source'       => 'Radio Rucphen',
				'region'       => '',
				'external'     => false,
			];
		}

		foreach ( ZuidwestImporter::get_news_cache() as $item ) {
			$cards[] = [
				'title'        => (string) ( $item['title'] ?? '' ),
				'url'          => (string) ( $item['source_url'] ?? '' ),
				'excerpt'      => (string) ( $item['excerpt'] ?? '' ),
				'image'        => (string) ( $item['image_url'] ?? '' ),
				'published_at' => (string) ( $item['published_at'] ?? '' ),
				'source'       => (string) ( $item['source_name'] ?? '' ),
				'region'       => (string) ( $item['region_label'] ?? '' ),
				'external'     => true,
			];
		}

		usort( $cards, static fn( $a, $b ) => strcmp( (string) $b['published_at'], (string) $a['published_at'] ) );
		$cards = array_slice( $cards, 0, 12 );

		ob_start();
		?>
		<section class="rucphen-news-grid">
			<header><h2><?php esc_html_e( 'Nieuws uit de regio', 'radio-rucphen' ); ?></h2></header>
			<div class="rucphen-news-grid__items">
				<?php foreach ( $cards as $card ) : ?>
					<a class="rucphen-card<?php echo $card['external'] ? ' is-external' : ''; ?>"
						href="<?php echo esc_url( $card['url'] ); ?>"
						<?php echo $card['external'] ? 'target="_blank" rel="noopener nofollow"' : ''; ?>>
						<?php if ( $card['image'] !== '' ) : ?>
							<img class="rucphen-card__image" src="<?php echo esc_url( $card['image'] ); ?>" alt="" loading="lazy">
						<?php endif; ?>
						<div class="rucphen-card__body">
							<?php if ( $card['region'] !== '' ) : ?>
								<span class="rucphen-card__pill"><?php echo esc_html( $card['region'] ); ?></span>
							<?php endif; ?>
							<h3 class="rucphen-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
							<?php if ( $card['excerpt'] !== '' ) : ?>
								<p class="rucphen-card__excerpt"><?php echo esc_html( wp_html_excerpt( $card['excerpt'], 160, '...' ) ); ?></p>
							<?php endif; ?>
							<footer class="rucphen-card__footer">
								<span class="rucphen-card__source"><?php echo esc_html( $card['source'] ); ?></span>
								<?php if ( $card['external'] ) : IconRegistry::print( 'external-link', [ 'size' => 14, 'label' => __( 'Externe link', 'radio-rucphen' ) ] ); endif; ?>
							</footer>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_video_grid(): string {
		$videos = ZuidwestImporter::get_videos_cache();

		ob_start();
		?>
		<section class="rucphen-video-grid">
			<header><h2><?php esc_html_e( 'Video\'s uit de regio', 'radio-rucphen' ); ?></h2></header>
			<div class="rucphen-video-grid__items">
				<?php foreach ( $videos as $video ) : ?>
					<a class="rucphen-card is-video"
						href="<?php echo esc_url( (string) ( $video['video_embed_url'] ?? $video['source_url'] ?? '' ) ); ?>"
						target="_blank" rel="noopener nofollow">
						<?php if ( ! empty( $video['image_url'] ) ) : ?>
							<img class="rucphen-card__image" src="<?php echo esc_url( (string) $video['image_url'] ); ?>" alt="" loading="lazy">
						<?php endif; ?>
						<span class="rucphen-card__play" aria-hidden="true">
							<?php IconRegistry::print( 'play', [ 'size' => 32 ] ); ?>
						</span>
						<div class="rucphen-card__body">
							<?php if ( ! empty( $video['region_label'] ) ) : ?>
								<span class="rucphen-card__pill"><?php echo esc_html( (string) $video['region_label'] ); ?></span>
							<?php endif; ?>
							<h3 class="rucphen-card__title"><?php echo esc_html( (string) ( $video['title'] ?? '' ) ); ?></h3>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_events_grid(): string {
		$now = current_datetime();

		$query = new \WP_Query(
			[
				'post_type'      => PostTypes::EVENT,
				'post_status'    => 'publish',
				'posts_per_page' => 12,
				'meta_query'     => [
					[
						'key'     => '_rucphen_event_start',
						'value'   => $now->format( 'c' ),
						'compare' => '>=',
						'type'    => 'DATETIME',
					],
				],
				'orderby'        => 'meta_value',
				'meta_key'       => '_rucphen_event_start',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			]
		);

		ob_start();
		?>
		<section class="rucphen-events">
			<header><h2><?php esc_html_e( 'Agenda', 'radio-rucphen' ); ?></h2></header>
			<ul class="rucphen-events__list">
				<?php foreach ( $query->posts as $post ) :
					$start    = (string) get_post_meta( $post->ID, '_rucphen_event_start', true );
					$location = (string) get_post_meta( $post->ID, '_rucphen_event_location', true );
					$url      = (string) get_post_meta( $post->ID, '_rucphen_event_url', true );
					$ts       = $start !== '' ? strtotime( $start ) : false;
					?>
					<li class="rucphen-events__item">
						<?php if ( $ts !== false ) : ?>
							<time class="rucphen-events__date" datetime="<?php echo esc_attr( $start ); ?>">
								<span class="rucphen-events__day"><?php echo esc_html( wp_date( 'd', $ts ) ); ?></span>
								<span class="rucphen-events__month"><?php echo esc_html( wp_date( 'M', $ts ) ); ?></span>
							</time>
						<?php endif; ?>
						<div class="rucphen-events__body">
							<h3 class="rucphen-events__title"><?php echo esc_html( get_the_title( $post ) ); ?></h3>
							<?php if ( $location !== '' ) : ?>
								<p class="rucphen-events__location"><?php echo esc_html( $location ); ?></p>
							<?php endif; ?>
							<?php if ( $url !== '' ) : ?>
								<a class="rucphen-events__link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Meer informatie', 'radio-rucphen' ); ?></a>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_frequency_grid(): string {
		$f = Settings::get( Settings::OPTION_FREQUENCIES );

		ob_start();
		?>
		<section class="rucphen-frequencies">
			<header><h2><?php esc_html_e( 'Frequenties', 'radio-rucphen' ); ?></h2></header>
			<dl class="rucphen-frequencies__grid">
				<div><dt>FM</dt><dd><?php echo esc_html( (string) $f['fm_mhz'] ); ?> MHz</dd></div>
				<div><dt>DAB+</dt><dd><?php echo esc_html( (string) $f['dab_blocks'] ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Dekking', 'radio-rucphen' ); ?></dt><dd><?php echo esc_html( (string) $f['coverage'] ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Kabel', 'radio-rucphen' ); ?></dt><dd><?php echo esc_html( trim( (string) $f['cable_provider'] . ' ' . (string) $f['cable_channel'] ) ); ?></dd></div>
			</dl>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_whatsapp_cta(): string {
		$contact = Settings::get( Settings::OPTION_CONTACT );
		$number  = preg_replace( '/\D+/', '', (string) ( $contact['whatsapp_number'] ?? '' ) );
		$text    = (string) ( $contact['whatsapp_default_text'] ?? '' );
		$href    = 'https://wa.me/' . $number . '?text=' . rawurlencode( $text );

		ob_start();
		?>
		<a class="rucphen-cta-whatsapp" href="<?php echo esc_url( $href ); ?>" target="_blank" rel="noopener">
			<?php IconRegistry::print( 'whatsapp', [ 'size' => 24 ] ); ?>
			<span><?php esc_html_e( 'Verzoekje via WhatsApp', 'radio-rucphen' ); ?></span>
		</a>
		<?php
		return (string) ob_get_clean();
	}
}
