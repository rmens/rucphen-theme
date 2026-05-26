<?php
/**
 * Dynamic blocks die de markup van de static Radio Rucphen site
 * (rucphen.localhost:4173 / radiorucphen.nl) reproduceren.
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
			'site-header'       => [ self::class, 'render_site_header' ],
			'site-footer'       => [ self::class, 'render_site_footer' ],
			'live-hero'         => [ self::class, 'render_live_hero' ],
			'sticky-player'     => [ self::class, 'render_sticky_player' ],
			'program-schedule'  => [ self::class, 'render_program_schedule' ],
			'featured-programs' => [ self::class, 'render_featured_programs' ],
			'news-mixed-grid'   => [ self::class, 'render_news_mixed_grid' ],
			'video-grid'        => [ self::class, 'render_video_grid' ],
			'events-grid'       => [ self::class, 'render_events_grid' ],
			'frequency-grid'    => [ self::class, 'render_frequency_grid' ],
			'whatsapp-cta'      => [ self::class, 'render_whatsapp_cta' ],
			'newsletter-cta'    => [ self::class, 'render_newsletter_cta' ],
			'program-quick-links' => [ self::class, 'render_program_quick_links' ],
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

	private static function theme_img( string $rel ): string {
		return RUCPHEN_THEME_URI . 'assets/img/' . ltrim( $rel, '/' );
	}

	private static function program_cover( \WP_Post $program ): string {
		if ( has_post_thumbnail( $program ) ) {
			return (string) get_the_post_thumbnail_url( $program, 'rucphen-card' );
		}
		return self::theme_img( 'programs/' . $program->post_name . '.jpg' );
	}

	public static function render_site_header(): string {
		$station = Settings::get( Settings::OPTION_STATION );
		$contact = Settings::get( Settings::OPTION_CONTACT );
		$freq    = Settings::get( Settings::OPTION_FREQUENCIES );

		$studio_email = (string) ( $contact['email_studio'] ?? '' );
		$fm           = (string) ( $freq['fm_mhz'] ?? '' );
		$coverage     = (string) ( $freq['coverage'] ?? '' );
		$station_name = (string) ( $station['name'] ?? 'Radio Rucphen' );

		$primary_menu = self::render_wp_menu( 'primary', 'main-nav', __( 'Hoofdnavigatie', 'radio-rucphen' ) );

		ob_start();
		?>
		<a class="skip-link" href="#maincontent"><?php esc_html_e( 'Naar hoofdcontent', 'radio-rucphen' ); ?></a>
		<header class="site-header">
			<div class="utility-bar">
				<div class="container utility-inner">
					<?php if ( $studio_email !== '' ) : ?><a href="mailto:<?php echo esc_attr( $studio_email ); ?>"><?php echo esc_html( $studio_email ); ?></a><?php endif; ?>
					<?php if ( $fm !== '' ) : ?><span><?php echo esc_html( $fm ); ?> FM</span><?php endif; ?>
					<?php if ( $coverage !== '' ) : ?><span><?php echo esc_html( $coverage ); ?></span><?php endif; ?>
				</div>
			</div>
			<div class="container header-top">
				<a class="wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $station_name . ' home' ); ?>">
					<img class="wordmark-logo" src="<?php echo esc_url( self::theme_img( 'logo-menu.png' ) ); ?>" width="119" height="44" alt="<?php echo esc_attr( $station_name ); ?>">
				</a>
				<?php echo $primary_menu; ?>
				<button class="search-pill" type="button" aria-label="<?php esc_attr_e( 'Zoeken', 'radio-rucphen' ); ?>" data-search-open>
					<svg class="search-pill-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M10.8 4a6.8 6.8 0 0 1 5.4 10.9l3.4 3.4-1.3 1.3-3.4-3.4A6.8 6.8 0 1 1 10.8 4Zm0 1.8a5 5 0 1 0 0 10 5 5 0 0 0 0-10Z"/></svg>
					<span class="search-pill-text"><?php esc_html_e( 'Zoeken', 'radio-rucphen' ); ?></span>
				</button>
				<div class="header-actions">
					<button class="hamburger" type="button" aria-label="<?php esc_attr_e( 'Menu', 'radio-rucphen' ); ?>" data-mobile-toggle aria-expanded="false">&#9776;</button>
				</div>
			</div>
			<div class="nav-strip">
				<div class="container mobile-panel" data-mobile-panel hidden>
					<?php echo self::render_wp_menu_links( 'primary' ); ?>
				</div>
			</div>
			<div class="live-bar">
				<div class="container"><span class="live-dot" aria-hidden="true"></span><strong><?php esc_html_e( 'Nu live:', 'radio-rucphen' ); ?></strong> <span data-live-now><?php echo esc_html( $station_name . ', ' . ( $station['tagline'] ?? '' ) ); ?></span></div>
			</div>
		</header>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_wp_menu( string $location, string $class, string $aria_label ): string {
		$locations = get_nav_menu_locations();
		if ( empty( $locations[ $location ] ) ) {
			return '';
		}

		$items = wp_get_nav_menu_items( $locations[ $location ] );
		if ( ! is_array( $items ) || $items === [] ) {
			return '';
		}

		$current = home_url( add_query_arg( null, null ) );

		ob_start();
		?>
		<nav class="<?php echo esc_attr( $class ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>">
			<?php foreach ( $items as $item ) :
				$is_current = trailingslashit( $item->url ) === trailingslashit( $current );
				?>
				<a href="<?php echo esc_url( $item->url ); ?>"
					<?php if ( $is_current ) : ?> aria-current="page" class="active"<?php endif; ?>>
					<?php echo esc_html( $item->title ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_wp_menu_links( string $location ): string {
		$locations = get_nav_menu_locations();
		if ( empty( $locations[ $location ] ) ) {
			return '';
		}
		$items = wp_get_nav_menu_items( $locations[ $location ] );
		if ( ! is_array( $items ) ) {
			return '';
		}

		ob_start();
		foreach ( $items as $item ) {
			printf( '<a href="%s">%s</a>', esc_url( $item->url ), esc_html( $item->title ) );
		}
		return (string) ob_get_clean();
	}

	public static function render_site_footer(): string {
		$station = Settings::get( Settings::OPTION_STATION );
		$org     = Settings::get( Settings::OPTION_ORGANIZATION );

		$copy_year = (int) wp_date( 'Y' );

		ob_start();
		?>
		<footer class="site-footer">
			<div class="container">
				<div class="footer-grid">
					<div>
						<h2><?php echo esc_html( (string) ( $station['name'] ?? 'Radio Rucphen' ) ); ?></h2>
						<p><?php echo esc_html( (string) ( $station['tagline'] ?? '' ) ); ?> <?php esc_html_e( 'Lokale radio voor de gemeente Rucphen en de directe regio.', 'radio-rucphen' ); ?></p>
					</div>
					<?php
					$footer_menus = [
						'footer_listen'      => __( 'Luisteren', 'radio-rucphen' ),
						'footer_participate' => __( 'Meedoen', 'radio-rucphen' ),
						'footer_news'        => __( 'Nieuws', 'radio-rucphen' ),
						'footer_legal'       => __( 'Juridisch', 'radio-rucphen' ),
					];
					foreach ( $footer_menus as $loc => $title ) :
						$locations = get_nav_menu_locations();
						if ( empty( $locations[ $loc ] ) ) {
							continue;
						}
						$items = wp_get_nav_menu_items( $locations[ $loc ] );
						if ( ! is_array( $items ) || $items === [] ) {
							continue;
						}
						?>
						<div>
							<h3><?php echo esc_html( $title ); ?></h3>
							<ul>
								<?php foreach ( $items as $item ) : ?>
									<li><a href="<?php echo esc_url( $item->url ); ?>"><?php echo esc_html( $item->title ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="footer-bottom">&copy; <?php echo esc_html( (string) $copy_year ); ?> <?php echo esc_html( (string) ( $org['legal_name'] ?? 'Stichting Rucphen RTV' ) ); ?>.
					<?php if ( ! empty( $org['anbi'] ) ) : ?> <?php esc_html_e( 'ANBI: ja.', 'radio-rucphen' ); ?><?php endif; ?>
					<?php if ( ! empty( $org['kvk'] ) ) : ?> KvK: <?php echo esc_html( (string) $org['kvk'] ); ?>.<?php endif; ?>
					<?php if ( ! empty( $org['rsin'] ) ) : ?> RSIN: <?php echo esc_html( (string) $org['rsin'] ); ?>.<?php endif; ?>
				</p>
			</div>
		</footer>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_live_hero(): string {
		$station = Settings::get( Settings::OPTION_STATION );
		$hero_bg = self::theme_img( 'hero/rucphen-live-background.jpg' );

		// Huidig programma (op basis van vandaag + nu)
		$current = self::current_slot_program();

		ob_start();
		?>
		<section class="radio-hero radio-hero-bg" style="--hero-bg:url('<?php echo esc_url( $hero_bg ); ?>')">
			<div class="container radio-hero-inner">
				<div class="radio-hero-copy">
					<span class="radio-hero-badge"><?php esc_html_e( 'Nu live', 'radio-rucphen' ); ?></span>
					<div class="radio-hero-title">
						<button class="radio-hero-play" type="button" data-hero-play aria-label="<?php esc_attr_e( 'Luister live', 'radio-rucphen' ); ?>">&#9654;</button>
						<div>
							<p class="eyebrow"><?php echo esc_html( (string) ( $station['name'] ?? 'Radio Rucphen' ) ); ?></p>
							<h1><?php echo esc_html( $current['title'] ?? (string) ( $station['tagline'] ?? 'Het geluid van Rucphen' ) ); ?></h1>
							<?php if ( ! empty( $current['subtitle'] ) ) : ?>
								<p><?php echo esc_html( $current['subtitle'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
					<div class="radio-now-card" data-hero-now-card>
						<span><?php esc_html_e( 'Nu speelt', 'radio-rucphen' ); ?></span>
						<img data-hero-cover src="<?php echo esc_url( self::theme_img( 'logo-square.png' ) ); ?>" width="64" height="64" alt="">
						<div>
							<strong data-hero-title><?php echo esc_html( (string) ( $station['name'] ?? 'Radio Rucphen' ) ); ?> - Live</strong>
							<span data-hero-artist><?php echo esc_html( (string) ( $station['tagline'] ?? 'Het geluid van Rucphen' ) ); ?></span>
						</div>
					</div>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @return array{title?: string, subtitle?: string}
	 */
	private static function current_slot_program(): array {
		$now    = current_datetime();
		$day_en = strtolower( $now->format( 'l' ) ); // monday..sunday
		$hhmm   = $now->format( 'H:i' );

		$slots = get_posts(
			[
				'post_type'      => PostTypes::SLOT,
				'posts_per_page' => -1,
				'meta_query'     => [
					[ 'key' => '_rucphen_slot_day', 'value' => $day_en ],
				],
				'no_found_rows'  => true,
			]
		);

		foreach ( $slots as $slot ) {
			$start = (string) get_post_meta( $slot->ID, '_rucphen_slot_start', true );
			$end   = (string) get_post_meta( $slot->ID, '_rucphen_slot_end', true );
			if ( $start === '' || $end === '' ) {
				continue;
			}

			$is_now = $end > $start
				? ( $hhmm >= $start && $hhmm < $end )
				: ( $hhmm >= $start || $hhmm < $end ); // over middernacht

			if ( $is_now ) {
				$program_id = (int) get_post_meta( $slot->ID, '_rucphen_slot_program_id', true );
				$program    = $program_id > 0 ? get_post( $program_id ) : null;
				return [
					'title'    => $program instanceof \WP_Post ? get_the_title( $program ) : '',
					'subtitle' => sprintf( '%s %s tot %s uur', self::day_label( $day_en ), $start, $end ),
				];
			}
		}

		return [];
	}

	private static function day_label( string $en ): string {
		$labels = [
			'monday'    => 'Maandag',
			'tuesday'   => 'Dinsdag',
			'wednesday' => 'Woensdag',
			'thursday'  => 'Donderdag',
			'friday'    => 'Vrijdag',
			'saturday'  => 'Zaterdag',
			'sunday'    => 'Zondag',
		];
		return $labels[ $en ] ?? ucfirst( $en );
	}

	public static function render_sticky_player(): string {
		$station = Settings::get( Settings::OPTION_STATION );
		$contact = Settings::get( Settings::OPTION_CONTACT );

		$wa_number = preg_replace( '/\D+/', '', (string) ( $contact['whatsapp_number'] ?? '' ) );
		$wa_text   = rawurlencode( (string) ( $contact['whatsapp_default_text'] ?? '' ) );
		$wa_url    = 'https://wa.me/' . $wa_number . '?text=' . $wa_text;

		ob_start();
		?>
		<div class="player" data-component="sticky-player" aria-label="<?php esc_attr_e( 'Live audio player', 'radio-rucphen' ); ?>">
			<div class="container player-inner">
				<img class="player-cover" data-player-cover src="<?php echo esc_url( self::theme_img( 'logo-square.png' ) ); ?>" width="96" height="96" alt="">
				<div class="player-copy" aria-live="polite" aria-atomic="true">
					<div class="player-meta-row">
						<span class="player-live-pill"><?php esc_html_e( 'Nu speelt', 'radio-rucphen' ); ?></span>
						<span class="player-artist" data-player-artist><?php echo esc_html( (string) ( $station['tagline'] ?? '' ) ); ?></span>
					</div>
					<div class="player-title" data-player-title><?php echo esc_html( (string) ( $station['name'] ?? 'Radio Rucphen' ) ); ?> - Live</div>
				</div>
				<button class="player-button" type="button" data-player-toggle aria-label="<?php esc_attr_e( 'Afspelen of pauzeren', 'radio-rucphen' ); ?>">
					<span data-player-icon-play>&#9654;</span>
					<span data-player-icon-pause hidden>&#10074;&#10074;</span>
				</button>
				<div class="player-actions">
					<a class="player-icon player-whatsapp" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'WhatsApp de studio', 'radio-rucphen' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4a8 8 0 0 0-6.8 12.2L4 20l3.9-1.1A8 8 0 1 0 12 4Zm0 1.8a6.2 6.2 0 1 1-3.4 11.4l-.3-.2-1.7.5.5-1.6-.2-.3A6.2 6.2 0 0 1 12 5.8Zm-2.3 3c-.2 0-.5.1-.7.4-.2.3-.7.8-.7 1.8s.7 2.1.8 2.2c.1.2 1.4 2.3 3.5 3.1 1.7.7 2 .5 2.4.5.4-.1 1.2-.5 1.4-1 .2-.5.2-.9.1-1l-.6-.3-1.2-.6c-.2-.1-.4-.1-.5.1l-.6.8c-.1.2-.3.2-.5.1-.3-.1-1-.4-1.8-1.1-.7-.6-1.1-1.4-1.3-1.6-.1-.2 0-.4.1-.5l.4-.5c.1-.1.1-.3.2-.4.1-.1 0-.3 0-.4l-.6-1.3c-.1-.3-.2-.3-.4-.3Z"/></svg>
					</a>
					<label class="player-volume">
						<span class="player-volume-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 9v6h4l5 4V5L8 9H4Zm12.5-.8-1.3 1.3a3.5 3.5 0 0 1 0 5l1.3 1.3a5.3 5.3 0 0 0 0-7.6Z"/></svg></span>
						<span class="sr-only"><?php esc_html_e( 'Volume', 'radio-rucphen' ); ?></span>
						<input type="range" min="0" max="100" value="80" data-player-volume aria-label="<?php esc_attr_e( 'Volume', 'radio-rucphen' ); ?>">
					</label>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_featured_programs(): string {
		$query = new \WP_Query(
			[
				'post_type'      => PostTypes::PROGRAM,
				'post_status'    => 'publish',
				'posts_per_page' => 4,
				'meta_query'     => [
					[ 'key' => '_rucphen_program_featured', 'value' => '1' ],
				],
				'no_found_rows'  => true,
			]
		);

		$first_slot_label = function ( int $program_id ): string {
			$slots = get_posts(
				[
					'post_type'      => PostTypes::SLOT,
					'posts_per_page' => 1,
					'meta_query'     => [
						[ 'key' => '_rucphen_slot_program_id', 'value' => $program_id ],
					],
					'orderby'        => 'meta_value',
					'meta_key'       => '_rucphen_slot_start',
					'order'          => 'ASC',
					'no_found_rows'  => true,
				]
			);
			if ( $slots === [] ) {
				return '';
			}
			$slot = $slots[0];
			$day  = (string) get_post_meta( $slot->ID, '_rucphen_slot_day', true );
			$st   = (string) get_post_meta( $slot->ID, '_rucphen_slot_start', true );
			$en   = (string) get_post_meta( $slot->ID, '_rucphen_slot_end', true );
			$abbr = substr( self::day_label( $day ), 0, 2 );
			return $abbr . ' ' . $st . ' - ' . $en;
		};

		ob_start();
		?>
		<section class="section">
			<div class="container">
				<div class="section-head">
					<div>
						<h2><?php esc_html_e( 'Uitgelicht', 'radio-rucphen' ); ?></h2>
						<p><?php esc_html_e( 'Vier programma\'s die deze week extra aandacht krijgen.', 'radio-rucphen' ); ?></p>
					</div>
					<a class="button button-secondary" href="<?php echo esc_url( get_post_type_archive_link( PostTypes::PROGRAM ) ?: '#' ); ?>"><?php esc_html_e( 'Alle programma\'s', 'radio-rucphen' ); ?></a>
				</div>
				<div class="feature-mosaic">
					<?php
					$index = 0;
					foreach ( $query->posts as $post ) :
						$is_large = $index === 0;
						$presenters = self::program_presenters( $post );
						$cover      = self::program_cover( $post );
						$slot_label = $first_slot_label( $post->ID );
						?>
						<a class="feature-tile <?php echo $is_large ? 'feature-tile-large' : ''; ?>" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
							<img src="<?php echo esc_url( $cover ); ?>" width="900" height="560" loading="<?php echo $is_large ? 'eager' : 'lazy'; ?>" decoding="async" alt="">
							<span class="feature-overlay"></span>
							<span class="feature-copy">
								<?php if ( $slot_label !== '' ) : ?>
									<span class="badge badge-redactie"><?php echo esc_html( $slot_label ); ?></span>
								<?php endif; ?>
								<strong><?php echo esc_html( get_the_title( $post ) ); ?></strong>
								<?php if ( $presenters !== '' ) : ?>
									<span><?php echo esc_html( $presenters ); ?></span>
								<?php endif; ?>
							</span>
						</a>
						<?php
						$index++;
					endforeach;
					?>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	private static function program_presenters( \WP_Post $program ): string {
		// Verzamel via gekoppelde slots: distincte presentatoren
		$slots = get_posts(
			[
				'post_type'      => PostTypes::SLOT,
				'posts_per_page' => -1,
				'meta_query'     => [
					[ 'key' => '_rucphen_slot_program_id', 'value' => (string) $program->ID ],
				],
				'no_found_rows'  => true,
			]
		);

		$names = [];
		foreach ( $slots as $slot ) {
			$ids = (array) get_post_meta( $slot->ID, '_rucphen_slot_presenter_ids', true );
			foreach ( $ids as $pid ) {
				$p = get_post( (int) $pid );
				if ( $p instanceof \WP_Post ) {
					$names[ $p->ID ] = get_the_title( $p );
				}
			}
		}

		return implode( ', ', $names );
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

		ob_start();
		?>
		<section class="section section-alt">
			<div class="container">
				<div class="section-head">
					<div>
						<h2><?php esc_html_e( 'Programmagids', 'radio-rucphen' ); ?></h2>
						<p><?php esc_html_e( 'Vandaag op Radio Rucphen.', 'radio-rucphen' ); ?></p>
					</div>
					<a class="button button-secondary" href="<?php echo esc_url( get_post_type_archive_link( PostTypes::PROGRAM ) ?: '#' ); ?>"><?php esc_html_e( 'Hele week', 'radio-rucphen' ); ?></a>
				</div>
				<div class="schedule" data-component="schedule" data-today="<?php echo esc_attr( $today ); ?>">
					<nav class="schedule-tabs" role="tablist">
						<?php foreach ( $days as $slug => $label ) : ?>
							<button type="button"
								role="tab"
								data-day="<?php echo esc_attr( $slug ); ?>"
								aria-selected="<?php echo $slug === $today ? 'true' : 'false'; ?>">
								<?php echo esc_html( $label ); ?>
							</button>
						<?php endforeach; ?>
					</nav>
					<?php foreach ( $days as $slug => $label ) : ?>
						<div class="schedule-day" data-day-panel="<?php echo esc_attr( $slug ); ?>" <?php echo $slug === $today ? '' : 'hidden'; ?>>
							<?php if ( empty( $by_day[ $slug ] ) ) : ?>
								<p class="meta"><?php esc_html_e( 'Geen programma\'s gepland.', 'radio-rucphen' ); ?></p>
							<?php else : ?>
								<ol class="schedule-list">
									<?php foreach ( $by_day[ $slug ] as $slot ) : ?>
										<li class="schedule-row">
											<span class="schedule-time">
												<?php echo esc_html( $slot['start'] ); ?> - <?php echo esc_html( $slot['end'] ); ?>
											</span>
											<span class="schedule-title">
												<?php if ( ! empty( $slot['program_url'] ) ) : ?>
													<a href="<?php echo esc_url( $slot['program_url'] ); ?>"><?php echo esc_html( $slot['program_title'] ); ?></a>
												<?php else : ?>
													<?php echo esc_html( $slot['program_title'] ); ?>
												<?php endif; ?>
											</span>
											<?php if ( ! empty( $slot['presenters'] ) ) : ?>
												<span class="schedule-presenters meta">
													<?php echo esc_html( implode( ', ', $slot['presenters'] ) ); ?>
												</span>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ol>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
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

	public static function render_news_mixed_grid(): string {
		$cards = [];

		foreach ( get_posts( [ 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 4, 'no_found_rows' => true ] ) as $post ) {
			$cards[] = [
				'title'     => get_the_title( $post ),
				'url'       => get_permalink( $post ),
				'image'     => has_post_thumbnail( $post )
					? (string) get_the_post_thumbnail_url( $post, 'rucphen-card' )
					: self::theme_img( 'nieuws/' . $post->post_name . '.jpg' ),
				'published' => (string) get_post_time( 'c', true, $post ),
				'pretty'    => wp_date( 'j F Y', (int) get_post_time( 'U', true, $post ) ),
				'meta'      => __( 'Redactie', 'radio-rucphen' ),
				'source'    => '',
				'external'  => false,
			];
		}

		foreach ( ZuidwestImporter::get_news_cache() as $item ) {
			$ts = strtotime( (string) ( $item['published_at'] ?? '' ) );
			$cards[] = [
				'title'     => (string) ( $item['title'] ?? '' ),
				'url'       => (string) ( $item['source_url'] ?? '' ),
				'image'     => (string) ( $item['image_url'] ?? '' ),
				'published' => (string) ( $item['published_at'] ?? '' ),
				'pretty'    => $ts ? wp_date( 'j F Y', $ts ) : '',
				'meta'      => (string) ( $item['region_label'] ?? '' ),
				'source'    => (string) ( $item['source_name'] ?? '' ),
				'external'  => true,
			];
		}

		usort( $cards, static fn( $a, $b ) => strcmp( (string) $b['published'], (string) $a['published'] ) );
		$cards = array_slice( $cards, 0, 7 );

		ob_start();
		?>
		<section class="section front-news-section">
			<div class="container">
				<div class="section-head">
					<div>
						<h2><?php esc_html_e( 'Lokaal nieuws', 'radio-rucphen' ); ?></h2>
						<p><?php esc_html_e( 'Eigen redactie en korte verwijzingen naar Zuidwest Update, altijd duidelijk gelabeld.', 'radio-rucphen' ); ?></p>
					</div>
					<a class="button button-secondary" href="<?php echo esc_url( home_url( '/nieuws/' ) ); ?>"><?php esc_html_e( 'Alle nieuws', 'radio-rucphen' ); ?></a>
				</div>
				<div class="front-news-grid">
					<?php
					$idx = 0;
					foreach ( $cards as $card ) :
						$is_lead = $idx === 0;
						$attrs   = $card['external'] ? ' target="_blank" rel="noopener nofollow"' : '';
						?>
						<article class="front-news-tile <?php echo $is_lead ? 'front-news-lead' : ''; ?>">
							<a href="<?php echo esc_url( $card['url'] ); ?>"<?php echo $attrs; ?>>
								<?php if ( $card['image'] !== '' ) : ?>
									<img src="<?php echo esc_url( $card['image'] ); ?>" width="900" height="560" loading="<?php echo $is_lead ? 'eager' : 'lazy'; ?>" decoding="async" alt="">
								<?php endif; ?>
								<span class="front-news-body">
									<?php if ( $card['meta'] !== '' ) : ?>
										<span class="front-news-meta"><?php echo esc_html( $card['meta'] ); ?></span>
									<?php endif; ?>
									<strong><?php echo esc_html( $card['title'] ); ?></strong>
									<span class="front-news-source-meta">
										<?php if ( $card['source'] !== '' ) : ?>
											<?php echo esc_html( $card['source'] ); ?> &middot;
										<?php endif; ?>
										<?php echo esc_html( $card['pretty'] ); ?>
										<?php if ( $card['external'] ) : ?> &#8599;<?php endif; ?>
									</span>
								</span>
							</a>
						</article>
						<?php
						$idx++;
					endforeach;
					?>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_video_grid(): string {
		$videos = ZuidwestImporter::get_videos_cache();

		ob_start();
		?>
		<section class="section front-video-section">
			<div class="container">
				<div class="section-head">
					<div>
						<h2><?php esc_html_e( 'Video\'s uit de regio', 'radio-rucphen' ); ?></h2>
						<p><?php esc_html_e( 'Actuele beelden uit Etten-Leur, Halderberge, Roosendaal, Rucphen en Zundert.', 'radio-rucphen' ); ?></p>
					</div>
					<a class="button button-secondary" href="<?php echo esc_url( home_url( '/video/' ) ); ?>"><?php esc_html_e( 'Alle video\'s', 'radio-rucphen' ); ?></a>
				</div>
				<div class="front-video-grid">
					<?php
					$idx = 0;
					foreach ( $videos as $video ) :
						$is_large  = $idx === 0;
						$link      = (string) ( $video['video_embed_url'] ?? $video['source_url'] ?? '' );
						$ts        = strtotime( (string) ( $video['published_at'] ?? '' ) );
						$pretty    = $ts ? wp_date( 'j F Y', $ts ) : '';
						$meta_line = trim( (string) ( $video['region_label'] ?? '' ) . ( $pretty !== '' ? ' &middot; ' . $pretty : '' ) );
						?>
						<article class="front-video-card <?php echo $is_large ? 'front-video-card-large' : ''; ?>">
							<a class="front-video-button" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener nofollow">
								<span class="front-video-media">
									<?php if ( ! empty( $video['image_url'] ) ) : ?>
										<img src="<?php echo esc_url( (string) $video['image_url'] ); ?>" width="768" height="432" loading="lazy" decoding="async" alt="">
									<?php endif; ?>
									<span class="video-play-mark" aria-hidden="true">&#9654;</span>
								</span>
								<span class="front-video-content">
									<?php if ( $meta_line !== '' ) : ?>
										<span class="front-video-meta"><?php echo wp_kses_post( $meta_line ); ?></span>
									<?php endif; ?>
									<strong><?php echo esc_html( (string) ( $video['title'] ?? '' ) ); ?></strong>
									<?php if ( $is_large && ! empty( $video['excerpt'] ) ) : ?>
										<span class="front-video-excerpt"><?php echo esc_html( (string) $video['excerpt'] ); ?></span>
									<?php endif; ?>
								</span>
							</a>
						</article>
						<?php
						$idx++;
					endforeach;
					?>
				</div>
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
					[ 'key' => '_rucphen_event_start', 'value' => $now->format( 'c' ), 'compare' => '>=', 'type' => 'DATETIME' ],
				],
				'orderby'        => 'meta_value',
				'meta_key'       => '_rucphen_event_start',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			]
		);

		ob_start();
		?>
		<section class="section">
			<div class="container">
				<div class="section-head">
					<div>
						<h2><?php esc_html_e( 'Lokale agenda', 'radio-rucphen' ); ?></h2>
						<p><?php esc_html_e( 'Komende activiteiten in Rucphen en omgeving.', 'radio-rucphen' ); ?></p>
					</div>
					<a class="button button-secondary" href="<?php echo esc_url( get_post_type_archive_link( PostTypes::EVENT ) ?: '#' ); ?>"><?php esc_html_e( 'Hele agenda', 'radio-rucphen' ); ?></a>
				</div>
				<div class="grid grid-3">
					<?php foreach ( $query->posts as $post ) :
						$start_iso = (string) get_post_meta( $post->ID, '_rucphen_event_start', true );
						$location  = (string) get_post_meta( $post->ID, '_rucphen_event_location', true );
						$url       = (string) get_post_meta( $post->ID, '_rucphen_event_url', true );
						$ts        = $start_iso !== '' ? strtotime( $start_iso ) : false;
						?>
						<article class="card">
							<div class="card-body" style="display:grid;grid-template-columns:auto 1fr;gap:1rem;align-items:start">
								<?php if ( $ts !== false ) : ?>
									<div class="date-block" aria-hidden="true">
										<span><?php echo esc_html( strtolower( wp_date( 'M', $ts ) ) ); ?></span>
										<strong><?php echo esc_html( wp_date( 'j', $ts ) ); ?></strong>
									</div>
								<?php endif; ?>
								<div>
									<h3><?php echo esc_html( get_the_title( $post ) ); ?></h3>
									<?php if ( $ts !== false || $location !== '' ) : ?>
										<p class="meta">
											<?php if ( $ts !== false ) : ?>
												<?php echo esc_html( wp_date( 'j F Y', $ts ) ); ?>
											<?php endif; ?>
											<?php if ( $location !== '' ) : ?> &middot; <?php echo esc_html( $location ); ?><?php endif; ?>
										</p>
									<?php endif; ?>
									<p><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $post ) ?: $post->post_content ) ); ?></p>
									<?php if ( $url !== '' ) : ?>
										<div class="button-row" style="margin-top:1rem">
											<a class="button button-secondary" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Meer informatie', 'radio-rucphen' ); ?></a>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_frequency_grid(): string {
		$f = Settings::get( Settings::OPTION_FREQUENCIES );

		ob_start();
		?>
		<section class="section section-alt">
			<div class="container">
				<div class="section-head"><div><h2><?php esc_html_e( 'Frequenties', 'radio-rucphen' ); ?></h2><p><?php esc_html_e( 'Zo luister je naar Radio Rucphen.', 'radio-rucphen' ); ?></p></div></div>
				<div class="grid grid-4">
					<article class="card"><div class="card-body"><p class="meta">FM</p><h3><?php echo esc_html( (string) $f['fm_mhz'] ); ?> MHz</h3></div></article>
					<article class="card"><div class="card-body"><p class="meta">DAB+</p><h3><?php echo esc_html( (string) $f['dab_blocks'] ); ?></h3></div></article>
					<article class="card"><div class="card-body"><p class="meta"><?php esc_html_e( 'Kabel', 'radio-rucphen' ); ?></p><h3><?php echo esc_html( trim( (string) $f['cable_provider'] . ' ' . (string) $f['cable_channel'] ) ); ?></h3></div></article>
					<article class="card"><div class="card-body"><p class="meta"><?php esc_html_e( 'Dekking', 'radio-rucphen' ); ?></p><h3><?php echo esc_html( (string) $f['coverage'] ); ?></h3></div></article>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_whatsapp_cta(): string {
		$contact = Settings::get( Settings::OPTION_CONTACT );
		$number  = preg_replace( '/\D+/', '', (string) ( $contact['whatsapp_number'] ?? '' ) );
		$text    = rawurlencode( (string) ( $contact['whatsapp_default_text'] ?? '' ) );
		$href    = 'https://wa.me/' . $number . '?text=' . $text;

		ob_start();
		?>
		<section class="section-sm section-green">
			<div class="container">
				<div class="cta-banner">
					<div>
						<h2><?php esc_html_e( 'Verzoekje?', 'radio-rucphen' ); ?></h2>
						<p><?php esc_html_e( 'Stuur de studio een berichtje via WhatsApp. Je nummer wordt alleen gebruikt voor je verzoekje.', 'radio-rucphen' ); ?></p>
					</div>
					<a class="button button-light" href="<?php echo esc_url( $href ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp de studio', 'radio-rucphen' ); ?></a>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_newsletter_cta(): string {
		$news    = Settings::get( Settings::OPTION_NEWSLETTER );
		$contact = Settings::get( Settings::OPTION_CONTACT );
		$number  = preg_replace( '/\D+/', '', (string) ( $contact['whatsapp_number'] ?? '' ) );
		$text    = rawurlencode( (string) ( $news['fallback_whatsapp_text'] ?? '' ) );
		$href    = 'https://wa.me/' . $number . '?text=' . $text;

		ob_start();
		?>
		<section class="section section-warm">
			<div class="container">
				<div class="cta-banner">
					<div>
						<h2><?php esc_html_e( 'Nieuwsbrief', 'radio-rucphen' ); ?></h2>
						<p><?php esc_html_e( 'De nieuwsbrief komt binnenkort. Je kunt je alvast melden via WhatsApp.', 'radio-rucphen' ); ?></p>
					</div>
					<a class="button button-light" href="<?php echo esc_url( $href ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Schrijf je in', 'radio-rucphen' ); ?></a>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_program_quick_links(): string {
		$query = new \WP_Query(
			[
				'post_type'      => PostTypes::PROGRAM,
				'post_status'    => 'publish',
				'posts_per_page' => 4,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			]
		);

		ob_start();
		?>
		<section class="section section-alt">
			<div class="container">
				<div class="section-head"><div><h2><?php esc_html_e( 'Snelle links', 'radio-rucphen' ); ?></h2><p><?php esc_html_e( 'Vaste programma\'s direct bij de hand.', 'radio-rucphen' ); ?></p></div></div>
				<div class="grid grid-4">
					<?php foreach ( $query->posts as $post ) :
						$cover      = self::program_cover( $post );
						$presenters = self::program_presenters( $post );
						$short      = (string) get_post_meta( $post->ID, '_rucphen_program_short_description', true );
						?>
						<article class="card">
							<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="plain-link">
								<img class="media" src="<?php echo esc_url( $cover ); ?>" width="640" height="360" loading="lazy" decoding="async" alt="<?php echo esc_attr( get_the_title( $post ) ); ?>">
							</a>
							<div class="card-body">
								<h3><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h3>
								<?php if ( $short !== '' ) : ?>
									<p><?php echo esc_html( $short ); ?></p>
								<?php endif; ?>
								<?php if ( $presenters !== '' ) : ?>
									<p class="meta"><?php echo esc_html( $presenters ); ?></p>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
