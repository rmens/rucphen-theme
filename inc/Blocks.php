<?php
/**
 * Dynamic blocks die Radio Rucphen renderen met Tailwind utility classes.
 *
 * Designtokens leven in src/css/app.css (@theme): kleuren brand/brand-dark/
 * accent/cyan/ink/ink-soft/surface/bg-app/bg-warm/bg-green/bg-dark/line/
 * success/whatsapp, fonts sans/display, radius sm/card.
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
			'site-header'         => [ self::class, 'render_site_header' ],
			'site-footer'         => [ self::class, 'render_site_footer' ],
			'live-hero'           => [ self::class, 'render_live_hero' ],
			'sticky-player'       => [ self::class, 'render_sticky_player' ],
			'program-schedule'    => [ self::class, 'render_program_schedule' ],
			'featured-programs'   => [ self::class, 'render_featured_programs' ],
			'news-mixed-grid'     => [ self::class, 'render_news_mixed_grid' ],
			'video-grid'          => [ self::class, 'render_video_grid' ],
			'events-grid'         => [ self::class, 'render_events_grid' ],
			'frequency-grid'      => [ self::class, 'render_frequency_grid' ],
			'whatsapp-cta'        => [ self::class, 'render_whatsapp_cta' ],
			'newsletter-cta'      => [ self::class, 'render_newsletter_cta' ],
			'program-quick-links' => [ self::class, 'render_program_quick_links' ],
		];

		$titles = self::block_titles();

		foreach ( $blocks as $name => $callback ) {
			register_block_type(
				self::NAMESPACE . '/' . $name,
				[
					'api_version'     => 3,
					'title'           => $titles[ $name ] ?? ucfirst( str_replace( '-', ' ', $name ) ),
					'category'        => str_starts_with( $name, 'site-' ) ? 'theme' : 'radio-rucphen',
					'icon'            => 'microphone',
					'description'     => sprintf( __( 'Radio Rucphen: %s.', 'radio-rucphen' ), $titles[ $name ] ?? $name ),
					'supports'        => [
						'html'  => false,
						'align' => false,
					],
					'render_callback' => $callback,
				]
			);
		}
	}

	/**
	 * @return array<string, string>
	 */
	private static function block_titles(): array {
		return [
			'site-header'         => __( 'Site header', 'radio-rucphen' ),
			'site-footer'         => __( 'Site footer', 'radio-rucphen' ),
			'live-hero'           => __( 'Live hero', 'radio-rucphen' ),
			'sticky-player'       => __( 'Sticky player', 'radio-rucphen' ),
			'program-schedule'    => __( 'Programmagids met dagtabs', 'radio-rucphen' ),
			'featured-programs'   => __( 'Uitgelichte programma\'s', 'radio-rucphen' ),
			'news-mixed-grid'     => __( 'Lokaal nieuws (eigen + Zuidwest Update)', 'radio-rucphen' ),
			'video-grid'          => __( 'Video\'s uit de regio', 'radio-rucphen' ),
			'events-grid'         => __( 'Agenda', 'radio-rucphen' ),
			'frequency-grid'      => __( 'Frequenties', 'radio-rucphen' ),
			'whatsapp-cta'        => __( 'WhatsApp verzoekje CTA', 'radio-rucphen' ),
			'newsletter-cta'      => __( 'Nieuwsbrief CTA', 'radio-rucphen' ),
			'program-quick-links' => __( 'Snelle programma-links', 'radio-rucphen' ),
		];
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

	private static function section_head( string $title, string $sub, ?string $action_label = null, ?string $action_url = null ): string {
		$action = '';
		if ( $action_label !== null && $action_url !== null ) {
			$action = sprintf(
				'<a class="inline-flex items-center gap-2 rounded-md border border-line bg-white px-4 py-2 text-sm font-bold text-ink no-underline hover:bg-surface" href="%s">%s</a>',
				esc_url( $action_url ),
				esc_html( $action_label )
			);
		}

		return sprintf(
			'<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
				<div>
					<h2 class="font-display text-3xl font-extrabold leading-tight md:text-4xl">%s</h2>
					<p class="mt-1 text-ink-soft">%s</p>
				</div>%s
			</div>',
			esc_html( $title ),
			esc_html( $sub ),
			$action
		);
	}

	public static function render_site_header(): string {
		$station = Settings::get( Settings::OPTION_STATION );
		$contact = Settings::get( Settings::OPTION_CONTACT );
		$freq    = Settings::get( Settings::OPTION_FREQUENCIES );

		$studio_email = (string) ( $contact['email_studio'] ?? '' );
		$fm           = (string) ( $freq['fm_mhz'] ?? '' );
		$coverage     = (string) ( $freq['coverage'] ?? '' );
		$station_name = (string) ( $station['name'] ?? 'Radio Rucphen' );
		$tagline      = (string) ( $station['tagline'] ?? '' );

		$primary_menu = self::render_wp_menu_inline( 'primary' );

		ob_start();
		?>
		<a class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-3 focus:z-50 focus:rounded-md focus:bg-white focus:px-3 focus:py-2 focus:font-extrabold focus:text-brand-dark focus:shadow-lg" href="#maincontent"><?php esc_html_e( 'Naar hoofdcontent', 'radio-rucphen' ); ?></a>
		<header class="sticky top-0 z-40 bg-bg-dark text-white shadow-lg">
			<div class="border-b border-white/10 bg-black/20 text-xs text-white/80">
				<div class="rucphen-container flex flex-wrap items-center gap-x-4 gap-y-1 py-1.5">
					<?php if ( $studio_email !== '' ) : ?><a class="no-underline hover:text-accent" href="mailto:<?php echo esc_attr( $studio_email ); ?>"><?php echo esc_html( $studio_email ); ?></a><?php endif; ?>
					<?php if ( $fm !== '' ) : ?><span><?php echo esc_html( $fm ); ?> FM</span><?php endif; ?>
					<?php if ( $coverage !== '' ) : ?><span><?php echo esc_html( $coverage ); ?></span><?php endif; ?>
				</div>
			</div>
			<div class="rucphen-container flex min-h-[76px] items-center justify-between gap-4">
				<a class="inline-flex items-center gap-2 no-underline" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $station_name . ' home' ); ?>">
					<img class="h-10 w-auto" src="<?php echo esc_url( self::theme_img( 'logo-menu.png' ) ); ?>" width="119" height="44" alt="<?php echo esc_attr( $station_name ); ?>">
				</a>
				<?php echo $primary_menu; ?>
				<div class="flex items-center gap-2">
					<button class="hidden items-center gap-2 rounded-md border border-white/15 bg-white/5 px-3 py-2 text-sm font-bold text-white hover:bg-white/10 md:inline-flex" type="button" aria-label="<?php esc_attr_e( 'Zoeken', 'radio-rucphen' ); ?>" data-search-open>
						<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10.8 4a6.8 6.8 0 0 1 5.4 10.9l3.4 3.4-1.3 1.3-3.4-3.4A6.8 6.8 0 1 1 10.8 4Zm0 1.8a5 5 0 1 0 0 10 5 5 0 0 0 0-10Z"/></svg>
						<span><?php esc_html_e( 'Zoeken', 'radio-rucphen' ); ?></span>
					</button>
					<button class="inline-grid h-11 w-11 place-items-center rounded-md border border-white/15 bg-white/5 text-white md:hidden" type="button" aria-label="<?php esc_attr_e( 'Menu', 'radio-rucphen' ); ?>" data-mobile-toggle aria-expanded="false">&#9776;</button>
				</div>
			</div>
			<div class="md:hidden">
				<div class="rucphen-container grid gap-1 py-2" data-mobile-panel hidden>
					<?php echo self::render_wp_menu_links( 'primary', 'block rounded-md px-3 py-2 text-base font-bold text-white hover:bg-white/10 no-underline' ); ?>
				</div>
			</div>
			<div class="border-t-4 border-brand bg-surface text-ink">
				<div class="rucphen-container flex min-h-[38px] items-center gap-2 text-sm">
					<span class="rucphen-live-dot" aria-hidden="true"></span>
					<strong class="font-bold"><?php esc_html_e( 'Nu live:', 'radio-rucphen' ); ?></strong>
					<span data-live-now><?php echo esc_html( $station_name . ', ' . $tagline ); ?></span>
				</div>
			</div>
		</header>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_wp_menu_inline( string $location ): string {
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
		<nav class="hidden flex-1 items-center justify-end gap-1 md:flex" aria-label="<?php esc_attr_e( 'Hoofdnavigatie', 'radio-rucphen' ); ?>">
			<?php foreach ( $items as $item ) :
				$is_current = trailingslashit( $item->url ) === trailingslashit( $current );
				$current_cls = $is_current ? 'bg-white/15' : 'hover:bg-white/10';
				?>
				<a class="inline-flex min-h-[44px] items-center rounded-md px-3 py-2 text-sm font-extrabold text-white no-underline <?php echo esc_attr( $current_cls ); ?>"
					href="<?php echo esc_url( $item->url ); ?>"
					<?php if ( $is_current ) : ?> aria-current="page"<?php endif; ?>>
					<?php echo esc_html( $item->title ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_wp_menu_links( string $location, string $link_class = '' ): string {
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
			printf(
				'<a class="%s" href="%s">%s</a>',
				esc_attr( $link_class ),
				esc_url( $item->url ),
				esc_html( $item->title )
			);
		}
		return (string) ob_get_clean();
	}

	public static function render_site_footer(): string {
		$station = Settings::get( Settings::OPTION_STATION );
		$org     = Settings::get( Settings::OPTION_ORGANIZATION );
		$year    = (int) wp_date( 'Y' );

		$footer_menus = [
			'footer_listen'      => __( 'Luisteren', 'radio-rucphen' ),
			'footer_participate' => __( 'Meedoen', 'radio-rucphen' ),
			'footer_news'        => __( 'Nieuws', 'radio-rucphen' ),
			'footer_legal'       => __( 'Juridisch', 'radio-rucphen' ),
		];

		ob_start();
		?>
		<footer class="bg-bg-dark text-white">
			<div class="rucphen-container py-14">
				<div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-5">
					<div class="lg:col-span-2">
						<h2 class="font-display text-2xl font-extrabold"><?php echo esc_html( (string) ( $station['name'] ?? 'Radio Rucphen' ) ); ?></h2>
						<p class="mt-2 max-w-md text-white/75"><?php echo esc_html( (string) ( $station['tagline'] ?? '' ) ); ?> <?php esc_html_e( 'Lokale radio voor de gemeente Rucphen en de directe regio.', 'radio-rucphen' ); ?></p>
					</div>
					<?php foreach ( $footer_menus as $loc => $title ) :
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
							<h3 class="font-display text-base font-extrabold uppercase tracking-wider"><?php echo esc_html( $title ); ?></h3>
							<ul class="mt-3 grid gap-2">
								<?php foreach ( $items as $item ) : ?>
									<li><a class="text-white/85 no-underline hover:text-accent" href="<?php echo esc_url( $item->url ); ?>"><?php echo esc_html( $item->title ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="mt-10 border-t border-white/10 pt-6 text-sm text-white/60">&copy; <?php echo esc_html( (string) $year ); ?> <?php echo esc_html( (string) ( $org['legal_name'] ?? 'Stichting Rucphen RTV' ) ); ?>.
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
		$current = self::current_slot_program();

		ob_start();
		?>
		<section class="rucphen-hero-bg relative text-white" style="--hero-bg:url('<?php echo esc_url( $hero_bg ); ?>')">
			<div class="rucphen-container py-16 lg:py-24">
				<div class="max-w-3xl">
					<span class="inline-flex items-center gap-2 rounded-full bg-accent px-3 py-1 text-xs font-extrabold uppercase tracking-wider text-ink"><?php esc_html_e( 'Nu live', 'radio-rucphen' ); ?></span>
					<div class="mt-6 flex items-start gap-5">
						<button class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-accent text-ink shadow-md transition hover:scale-105" type="button" data-hero-play aria-label="<?php esc_attr_e( 'Luister live', 'radio-rucphen' ); ?>">
							<svg class="h-7 w-7 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
						</button>
						<div>
							<p class="text-sm font-extrabold uppercase tracking-[0.18em] text-white/80"><?php echo esc_html( (string) ( $station['name'] ?? 'Radio Rucphen' ) ); ?></p>
							<h1 class="mt-1 font-display text-4xl font-extrabold leading-[1.05] md:text-6xl"><?php echo esc_html( $current['title'] ?? (string) ( $station['tagline'] ?? 'Het geluid van Rucphen' ) ); ?></h1>
							<?php if ( ! empty( $current['subtitle'] ) ) : ?>
								<p class="mt-2 text-white/85"><?php echo esc_html( $current['subtitle'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
					<div class="mt-8 inline-flex items-center gap-4 rounded-card bg-black/35 px-4 py-3 backdrop-blur-sm">
						<span class="text-xs font-extrabold uppercase tracking-wider text-white/70"><?php esc_html_e( 'Nu speelt', 'radio-rucphen' ); ?></span>
						<img class="h-12 w-12 rounded-md object-cover" data-hero-cover src="<?php echo esc_url( self::theme_img( 'logo-square.png' ) ); ?>" width="64" height="64" alt="">
						<div class="leading-tight">
							<strong class="block font-bold" data-hero-title><?php echo esc_html( (string) ( $station['name'] ?? 'Radio Rucphen' ) ); ?> - Live</strong>
							<span class="block text-sm text-white/80" data-hero-artist><?php echo esc_html( (string) ( $station['tagline'] ?? 'Het geluid van Rucphen' ) ); ?></span>
						</div>
					</div>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	private static function current_slot_program(): array {
		$now    = current_datetime();
		$day_en = strtolower( $now->format( 'l' ) );
		$hhmm   = $now->format( 'H:i' );

		$slots = get_posts(
			[
				'post_type'      => PostTypes::SLOT,
				'posts_per_page' => -1,
				'meta_query'     => [ [ 'key' => '_rucphen_slot_day', 'value' => $day_en ] ],
				'no_found_rows'  => true,
			]
		);

		foreach ( $slots as $slot ) {
			$start = (string) get_post_meta( $slot->ID, '_rucphen_slot_start', true );
			$end   = (string) get_post_meta( $slot->ID, '_rucphen_slot_end', true );
			if ( $start === '' || $end === '' ) continue;

			$is_now = $end > $start
				? ( $hhmm >= $start && $hhmm < $end )
				: ( $hhmm >= $start || $hhmm < $end );

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
			'monday' => 'Maandag', 'tuesday' => 'Dinsdag', 'wednesday' => 'Woensdag',
			'thursday' => 'Donderdag', 'friday' => 'Vrijdag', 'saturday' => 'Zaterdag', 'sunday' => 'Zondag',
		];
		return $labels[ $en ] ?? ucfirst( $en );
	}

	public static function render_sticky_player(): string {
		$station = Settings::get( Settings::OPTION_STATION );
		$contact = Settings::get( Settings::OPTION_CONTACT );
		$number  = preg_replace( '/\D+/', '', (string) ( $contact['whatsapp_number'] ?? '' ) );
		$text    = rawurlencode( (string) ( $contact['whatsapp_default_text'] ?? '' ) );
		$wa_url  = 'https://wa.me/' . $number . '?text=' . $text;

		ob_start();
		?>
		<div class="fixed inset-x-0 bottom-0 z-50 border-t border-white/10 bg-brand text-white shadow-[0_-4px_14px_rgb(15_23_42_/_0.25)]" data-component="sticky-player" aria-label="<?php esc_attr_e( 'Live audio player', 'radio-rucphen' ); ?>">
			<div class="rucphen-container flex items-center gap-3 py-3">
				<img class="h-14 w-14 shrink-0 rounded-md object-cover" data-player-cover src="<?php echo esc_url( self::theme_img( 'logo-square.png' ) ); ?>" width="56" height="56" alt="">
				<div class="min-w-0 flex-1 leading-tight" aria-live="polite" aria-atomic="true">
					<div class="flex items-center gap-2">
						<span class="inline-flex items-center rounded-full bg-accent px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-ink"><?php esc_html_e( 'Nu speelt', 'radio-rucphen' ); ?></span>
						<span class="truncate text-sm text-white/80" data-player-artist><?php echo esc_html( (string) ( $station['tagline'] ?? '' ) ); ?></span>
					</div>
					<div class="mt-0.5 truncate font-bold" data-player-title><?php echo esc_html( (string) ( $station['name'] ?? 'Radio Rucphen' ) ); ?> - Live</div>
				</div>
				<button class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-accent text-ink shadow-md transition hover:scale-105" type="button" data-player-toggle aria-label="<?php esc_attr_e( 'Afspelen of pauzeren', 'radio-rucphen' ); ?>">
					<svg class="h-6 w-6 translate-x-0.5" data-player-icon-play viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
					<svg class="h-6 w-6" data-player-icon-pause viewBox="0 0 24 24" fill="currentColor" hidden><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>
				</button>
				<a class="hidden h-10 w-10 place-items-center rounded-md bg-white/10 text-white hover:bg-white/20 sm:grid" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'WhatsApp de studio', 'radio-rucphen' ); ?>">
					<svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 4a8 8 0 0 0-6.8 12.2L4 20l3.9-1.1A8 8 0 1 0 12 4Zm0 1.8a6.2 6.2 0 1 1-3.4 11.4l-.3-.2-1.7.5.5-1.6-.2-.3A6.2 6.2 0 0 1 12 5.8Z"/></svg>
				</a>
				<label class="hidden items-center gap-2 lg:flex">
					<span class="sr-only"><?php esc_html_e( 'Volume', 'radio-rucphen' ); ?></span>
					<input class="h-1 w-24 cursor-pointer appearance-none rounded-full bg-white/30 accent-accent" type="range" min="0" max="100" value="80" data-player-volume aria-label="<?php esc_attr_e( 'Volume', 'radio-rucphen' ); ?>">
				</label>
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
				'meta_query'     => [ [ 'key' => '_rucphen_program_featured', 'value' => '1' ] ],
				'no_found_rows'  => true,
			]
		);

		$slot_label = function ( int $program_id ): string {
			$slots = get_posts(
				[
					'post_type'      => PostTypes::SLOT,
					'posts_per_page' => 1,
					'meta_query'     => [ [ 'key' => '_rucphen_slot_program_id', 'value' => $program_id ] ],
					'orderby'        => 'meta_value',
					'meta_key'       => '_rucphen_slot_start',
					'order'          => 'ASC',
					'no_found_rows'  => true,
				]
			);
			if ( $slots === [] ) return '';
			$slot = $slots[0];
			$day  = (string) get_post_meta( $slot->ID, '_rucphen_slot_day', true );
			$st   = (string) get_post_meta( $slot->ID, '_rucphen_slot_start', true );
			$en   = (string) get_post_meta( $slot->ID, '_rucphen_slot_end', true );
			return substr( self::day_label( $day ), 0, 2 ) . ' ' . $st . ' - ' . $en;
		};

		ob_start();
		?>
		<section class="bg-bg-app py-16">
			<div class="rucphen-container">
				<?php echo self::section_head(
					__( 'Uitgelicht', 'radio-rucphen' ),
					__( 'Vier programma\'s die deze week extra aandacht krijgen.', 'radio-rucphen' ),
					__( 'Alle programma\'s', 'radio-rucphen' ),
					get_post_type_archive_link( PostTypes::PROGRAM ) ?: '#'
				); ?>
				<div class="rucphen-mosaic">
					<?php
					$idx = 0;
					foreach ( $query->posts as $post ) :
						$is_large   = $idx === 0;
						$cover      = self::program_cover( $post );
						$presenters = self::program_presenters( $post );
						$label      = $slot_label( $post->ID );
						?>
						<a class="<?php echo $is_large ? 'rucphen-mosaic-large ' : ''; ?>group relative block overflow-hidden rounded-card no-underline shadow-md" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
							<img class="absolute inset-0 h-full w-full object-cover transition group-hover:scale-105" src="<?php echo esc_url( $cover ); ?>" loading="<?php echo $is_large ? 'eager' : 'lazy'; ?>" alt="">
							<span class="absolute inset-0 bg-gradient-to-t from-bg-dark/85 via-bg-dark/30 to-transparent"></span>
							<span class="absolute inset-x-0 bottom-0 grid gap-1 p-4 text-white">
								<?php if ( $label !== '' ) : ?>
									<span class="inline-flex w-fit items-center rounded-full bg-accent px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-ink"><?php echo esc_html( $label ); ?></span>
								<?php endif; ?>
								<strong class="font-display <?php echo $is_large ? 'text-2xl' : 'text-lg'; ?> font-extrabold leading-tight"><?php echo esc_html( get_the_title( $post ) ); ?></strong>
								<?php if ( $presenters !== '' ) : ?>
									<span class="text-sm text-white/80"><?php echo esc_html( $presenters ); ?></span>
								<?php endif; ?>
							</span>
						</a>
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

	private static function program_presenters( \WP_Post $program ): string {
		$slots = get_posts(
			[
				'post_type'      => PostTypes::SLOT,
				'posts_per_page' => -1,
				'meta_query'     => [ [ 'key' => '_rucphen_slot_program_id', 'value' => (string) $program->ID ] ],
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

		$today  = strtolower( wp_date( 'l' ) );
		$by_day = self::slots_grouped_by_day();

		ob_start();
		?>
		<section class="bg-surface py-16">
			<div class="rucphen-container">
				<?php echo self::section_head( __( 'Programmagids', 'radio-rucphen' ), __( 'Bekijk wat er op Radio Rucphen te horen is.', 'radio-rucphen' ) ); ?>
				<div class="rounded-card bg-white p-6 shadow-sm" data-component="schedule" data-today="<?php echo esc_attr( $today ); ?>">
					<nav class="flex flex-wrap gap-2" role="tablist">
						<?php foreach ( $days as $slug => $label ) : ?>
							<button class="rounded-full border border-line bg-surface px-4 py-2 text-sm font-extrabold text-ink transition aria-selected:border-brand aria-selected:bg-brand aria-selected:text-white hover:bg-line/40"
								type="button"
								role="tab"
								data-day="<?php echo esc_attr( $slug ); ?>"
								aria-selected="<?php echo $slug === $today ? 'true' : 'false'; ?>">
								<?php echo esc_html( $label ); ?>
							</button>
						<?php endforeach; ?>
					</nav>
					<?php foreach ( $days as $slug => $label ) : ?>
						<div class="mt-4" data-day-panel="<?php echo esc_attr( $slug ); ?>" <?php echo $slug === $today ? '' : 'hidden'; ?>>
							<?php if ( empty( $by_day[ $slug ] ) ) : ?>
								<p class="text-ink-soft"><?php esc_html_e( 'Geen programma\'s gepland.', 'radio-rucphen' ); ?></p>
							<?php else : ?>
								<ol class="grid gap-2">
									<?php foreach ( $by_day[ $slug ] as $slot ) : ?>
										<li class="grid grid-cols-[7rem_1fr] items-baseline gap-4 rounded-md bg-surface px-4 py-3 sm:grid-cols-[7rem_1fr_auto]">
											<span class="font-extrabold text-brand"><?php echo esc_html( $slot['start'] ); ?> - <?php echo esc_html( $slot['end'] ); ?></span>
											<span class="font-bold">
												<?php if ( ! empty( $slot['program_url'] ) ) : ?>
													<a class="no-underline hover:underline" href="<?php echo esc_url( $slot['program_url'] ); ?>"><?php echo esc_html( $slot['program_title'] ); ?></a>
												<?php else : ?>
													<?php echo esc_html( $slot['program_title'] ); ?>
												<?php endif; ?>
											</span>
											<?php if ( ! empty( $slot['presenters'] ) ) : ?>
												<span class="col-span-2 text-sm text-ink-soft sm:col-span-1 sm:text-right"><?php echo esc_html( implode( ', ', $slot['presenters'] ) ); ?></span>
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
			if ( $day === '' ) continue;

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
		<section class="bg-white py-16">
			<div class="rucphen-container">
				<?php echo self::section_head(
					__( 'Lokaal nieuws', 'radio-rucphen' ),
					__( 'Eigen redactie en korte verwijzingen naar Zuidwest Update, altijd duidelijk gelabeld.', 'radio-rucphen' ),
					__( 'Alle nieuws', 'radio-rucphen' ),
					home_url( '/nieuws/' )
				); ?>
				<div class="rucphen-news-grid">
					<?php
					$idx = 0;
					foreach ( $cards as $card ) :
						$is_lead = $idx === 0;
						$ext     = $card['external'] ? ' target="_blank" rel="noopener nofollow"' : '';
						?>
						<article class="<?php echo $is_lead ? 'rucphen-news-lead ' : ''; ?>group relative overflow-hidden rounded-card bg-surface shadow-sm transition hover:shadow-md">
							<a class="block no-underline" href="<?php echo esc_url( $card['url'] ); ?>"<?php echo $ext; ?>>
								<?php if ( $card['image'] !== '' ) : ?>
									<img class="aspect-[16/9] w-full object-cover transition group-hover:scale-105" src="<?php echo esc_url( $card['image'] ); ?>" loading="<?php echo $is_lead ? 'eager' : 'lazy'; ?>" alt="">
								<?php endif; ?>
								<div class="grid gap-2 p-4">
									<?php if ( $card['meta'] !== '' ) : ?>
										<span class="inline-flex w-fit items-center rounded-full bg-cyan/15 px-2 py-0.5 text-xs font-extrabold uppercase tracking-wider text-brand"><?php echo esc_html( $card['meta'] ); ?></span>
									<?php endif; ?>
									<strong class="font-display <?php echo $is_lead ? 'text-2xl md:text-3xl' : 'text-lg'; ?> font-extrabold leading-tight text-ink"><?php echo esc_html( $card['title'] ); ?></strong>
									<span class="text-sm text-ink-soft">
										<?php if ( $card['source'] !== '' ) : ?><?php echo esc_html( $card['source'] ); ?> &middot; <?php endif; ?>
										<?php echo esc_html( $card['pretty'] ); ?>
										<?php if ( $card['external'] ) : ?> &#8599;<?php endif; ?>
									</span>
								</div>
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
		<section class="bg-brand-dark text-white py-16">
			<div class="rucphen-container">
				<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
					<div>
						<h2 class="font-display text-3xl font-extrabold leading-tight md:text-4xl"><?php esc_html_e( 'Video\'s uit de regio', 'radio-rucphen' ); ?></h2>
						<p class="mt-1 text-white/75"><?php esc_html_e( 'Actuele beelden uit Etten-Leur, Halderberge, Roosendaal, Rucphen en Zundert.', 'radio-rucphen' ); ?></p>
					</div>
					<a class="inline-flex w-fit items-center gap-2 rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-bold no-underline hover:bg-white/15" href="<?php echo esc_url( home_url( '/video/' ) ); ?>"><?php esc_html_e( 'Alle video\'s', 'radio-rucphen' ); ?></a>
				</div>
				<div class="rucphen-video-grid">
					<?php
					$idx = 0;
					foreach ( $videos as $video ) :
						$is_large = $idx === 0;
						$link     = (string) ( $video['video_embed_url'] ?? $video['source_url'] ?? '' );
						$ts       = strtotime( (string) ( $video['published_at'] ?? '' ) );
						$pretty   = $ts ? wp_date( 'j F Y', $ts ) : '';
						$meta     = trim( (string) ( $video['region_label'] ?? '' ) . ( $pretty !== '' ? ' &middot; ' . $pretty : '' ) );
						?>
						<article class="<?php echo $is_large ? 'rucphen-video-large ' : ''; ?>group relative overflow-hidden rounded-card bg-white/5 shadow-md transition hover:bg-white/10">
							<a class="block no-underline text-white" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener nofollow">
								<div class="relative aspect-[16/9] overflow-hidden">
									<?php if ( ! empty( $video['image_url'] ) ) : ?>
										<img class="h-full w-full object-cover transition group-hover:scale-105" src="<?php echo esc_url( (string) $video['image_url'] ); ?>" loading="lazy" alt="">
									<?php endif; ?>
									<span class="absolute inset-0 grid place-items-center bg-black/20">
										<span class="grid h-14 w-14 place-items-center rounded-full bg-accent text-ink shadow-md">
											<svg class="h-6 w-6 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
										</span>
									</span>
								</div>
								<div class="grid gap-2 p-4">
									<?php if ( $meta !== '' ) : ?>
										<span class="text-xs font-bold uppercase tracking-wider text-white/70"><?php echo wp_kses_post( $meta ); ?></span>
									<?php endif; ?>
									<strong class="font-display <?php echo $is_large ? 'text-2xl' : 'text-lg'; ?> font-extrabold leading-tight"><?php echo esc_html( (string) ( $video['title'] ?? '' ) ); ?></strong>
									<?php if ( $is_large && ! empty( $video['excerpt'] ) ) : ?>
										<span class="text-sm text-white/80"><?php echo esc_html( (string) $video['excerpt'] ); ?></span>
									<?php endif; ?>
								</div>
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
				'meta_query'     => [ [ 'key' => '_rucphen_event_start', 'value' => $now->format( 'c' ), 'compare' => '>=', 'type' => 'DATETIME' ] ],
				'orderby'        => 'meta_value',
				'meta_key'       => '_rucphen_event_start',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			]
		);

		ob_start();
		?>
		<section class="bg-white py-16">
			<div class="rucphen-container">
				<?php echo self::section_head(
					__( 'Lokale agenda', 'radio-rucphen' ),
					__( 'Komende activiteiten in Rucphen en omgeving.', 'radio-rucphen' ),
					__( 'Hele agenda', 'radio-rucphen' ),
					get_post_type_archive_link( PostTypes::EVENT ) ?: '#'
				); ?>
				<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
					<?php foreach ( $query->posts as $post ) :
						$start_iso = (string) get_post_meta( $post->ID, '_rucphen_event_start', true );
						$location  = (string) get_post_meta( $post->ID, '_rucphen_event_location', true );
						$url       = (string) get_post_meta( $post->ID, '_rucphen_event_url', true );
						$ts        = $start_iso !== '' ? strtotime( $start_iso ) : false;
						?>
						<article class="grid grid-cols-[5rem_1fr] items-start gap-4 rounded-card bg-surface p-5 shadow-sm">
							<?php if ( $ts !== false ) : ?>
								<div class="grid place-items-center rounded-md bg-brand p-3 text-center text-white">
									<span class="text-xs font-bold uppercase tracking-wider"><?php echo esc_html( strtolower( wp_date( 'M', $ts ) ) ); ?></span>
									<strong class="font-display text-3xl font-extrabold leading-none"><?php echo esc_html( wp_date( 'j', $ts ) ); ?></strong>
								</div>
							<?php endif; ?>
							<div>
								<h3 class="font-display text-lg font-extrabold leading-tight"><?php echo esc_html( get_the_title( $post ) ); ?></h3>
								<?php if ( $ts !== false || $location !== '' ) : ?>
									<p class="mt-1 text-sm text-ink-soft">
										<?php if ( $ts !== false ) : ?><?php echo esc_html( wp_date( 'j F Y', $ts ) ); ?><?php endif; ?>
										<?php if ( $location !== '' ) : ?> &middot; <?php echo esc_html( $location ); ?><?php endif; ?>
									</p>
								<?php endif; ?>
								<p class="mt-2 text-sm text-ink-soft"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $post ) ?: $post->post_content ) ); ?></p>
								<?php if ( $url !== '' ) : ?>
									<a class="mt-3 inline-flex items-center gap-1 text-sm font-extrabold text-brand no-underline hover:underline" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Meer informatie', 'radio-rucphen' ); ?> &rarr;</a>
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

	public static function render_frequency_grid(): string {
		$f = Settings::get( Settings::OPTION_FREQUENCIES );

		ob_start();
		?>
		<section class="bg-surface py-16">
			<div class="rucphen-container">
				<?php echo self::section_head( __( 'Frequenties', 'radio-rucphen' ), __( 'Zo luister je naar Radio Rucphen.', 'radio-rucphen' ) ); ?>
				<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
					<?php
					$tiles = [
						[ 'label' => 'FM',                                  'value' => (string) $f['fm_mhz'] . ' MHz' ],
						[ 'label' => 'DAB+',                                'value' => (string) $f['dab_blocks'] ],
						[ 'label' => __( 'Kabel', 'radio-rucphen' ),         'value' => trim( (string) $f['cable_provider'] . ' ' . (string) $f['cable_channel'] ) ],
						[ 'label' => __( 'Dekking', 'radio-rucphen' ),       'value' => (string) $f['coverage'] ],
					];
					foreach ( $tiles as $t ) : ?>
						<article class="rounded-card bg-white p-5 shadow-sm">
							<p class="text-xs font-bold uppercase tracking-wider text-ink-soft"><?php echo esc_html( $t['label'] ); ?></p>
							<p class="mt-1 font-display text-2xl font-extrabold text-brand"><?php echo esc_html( $t['value'] ); ?></p>
						</article>
					<?php endforeach; ?>
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
		<section class="bg-bg-green py-12">
			<div class="rucphen-container">
				<div class="flex flex-col items-start gap-4 rounded-card bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
					<div>
						<h2 class="font-display text-2xl font-extrabold leading-tight"><?php esc_html_e( 'Verzoekje?', 'radio-rucphen' ); ?></h2>
						<p class="mt-1 text-ink-soft"><?php esc_html_e( 'Stuur de studio een berichtje via WhatsApp. Je nummer wordt alleen gebruikt voor je verzoekje.', 'radio-rucphen' ); ?></p>
					</div>
					<a class="inline-flex items-center gap-2 rounded-full bg-whatsapp px-5 py-3 font-bold text-white no-underline shadow-md transition hover:brightness-95" href="<?php echo esc_url( $href ); ?>" target="_blank" rel="noopener">
						<svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 4a8 8 0 0 0-6.8 12.2L4 20l3.9-1.1A8 8 0 1 0 12 4Z"/></svg>
						<?php esc_html_e( 'WhatsApp de studio', 'radio-rucphen' ); ?>
					</a>
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
		<section class="bg-bg-warm py-12">
			<div class="rucphen-container">
				<div class="flex flex-col items-start gap-4 rounded-card bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
					<div>
						<h2 class="font-display text-2xl font-extrabold leading-tight"><?php esc_html_e( 'Nieuwsbrief', 'radio-rucphen' ); ?></h2>
						<p class="mt-1 text-ink-soft"><?php esc_html_e( 'De nieuwsbrief komt binnenkort. Je kunt je alvast melden via WhatsApp.', 'radio-rucphen' ); ?></p>
					</div>
					<a class="inline-flex items-center gap-2 rounded-full bg-ink px-5 py-3 font-bold text-white no-underline shadow-md transition hover:brightness-110" href="<?php echo esc_url( $href ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Schrijf je in', 'radio-rucphen' ); ?></a>
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
		<section class="bg-surface py-16">
			<div class="rucphen-container">
				<?php echo self::section_head( __( 'Snelle links', 'radio-rucphen' ), __( 'Vaste programma\'s direct bij de hand.', 'radio-rucphen' ) ); ?>
				<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
					<?php foreach ( $query->posts as $post ) :
						$cover      = self::program_cover( $post );
						$short      = (string) get_post_meta( $post->ID, '_rucphen_program_short_description', true );
						$presenters = self::program_presenters( $post );
						?>
						<article class="group overflow-hidden rounded-card bg-white shadow-sm transition hover:shadow-md">
							<a class="block no-underline" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
								<img class="aspect-[16/9] w-full object-cover transition group-hover:scale-105" src="<?php echo esc_url( $cover ); ?>" loading="lazy" alt="<?php echo esc_attr( get_the_title( $post ) ); ?>">
								<div class="grid gap-2 p-4">
									<h3 class="font-display text-lg font-extrabold leading-tight text-ink"><?php echo esc_html( get_the_title( $post ) ); ?></h3>
									<?php if ( $short !== '' ) : ?>
										<p class="text-sm text-ink-soft"><?php echo esc_html( $short ); ?></p>
									<?php endif; ?>
									<?php if ( $presenters !== '' ) : ?>
										<p class="text-xs font-bold uppercase tracking-wider text-brand"><?php echo esc_html( $presenters ); ?></p>
									<?php endif; ?>
								</div>
							</a>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
