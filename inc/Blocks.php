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
	private const CONTAINER = 'mx-auto w-[min(100%_-_2rem,var(--spacing-container))]';
	private const SECTION_HEAD = 'mb-[1.55rem] flex items-end justify-between gap-4 max-[767px]:flex-col max-[767px]:items-start';
	private const SECTION_TITLE = 'm-0 font-display text-[2.35rem] font-extrabold leading-[1.08] text-brand max-[767px]:text-[2rem]';
	private const SECTION_SUBTITLE = 'mt-[0.45rem] text-ink-soft';
	private const SECTION_ACTION = 'inline-flex min-h-11 w-fit items-center rounded-sm border border-[#c9d7ec] bg-white px-[0.95rem] py-[0.55rem] text-[0.95rem] font-extrabold text-brand no-underline hover:bg-[#e9eef7] hover:text-brand-dark';
	private const LIVE_DOT = 'size-[0.55rem] animate-pulse rounded-full bg-success shadow-[0_0_0_0_rgb(22_163_74_/_0.58)]';

	public static function register(): void {
		add_action( 'init', [ self::class, 'register_blocks' ] );
	}

	public static function register_blocks(): void {
		$blocks = [
			'template-main'       => [ self::class, 'render_template_main' ],
			'site-header'         => [ self::class, 'render_site_header' ],
			'site-footer'         => [ self::class, 'render_site_footer' ],
			'live-hero'           => [ self::class, 'render_live_hero' ],
			'sticky-player'       => [ self::class, 'render_sticky_player' ],
			'program-archive'     => [ self::class, 'render_program_archive' ],
			'program-single'      => [ self::class, 'render_program_single' ],
			'presenter-archive'   => [ self::class, 'render_presenter_archive' ],
			'presenter-single'    => [ self::class, 'render_presenter_single' ],
			'featured-programs'   => [ self::class, 'render_featured_programs' ],
			'recent-podcasts'     => [ self::class, 'render_recent_podcasts' ],
			'podcast-archive'     => [ self::class, 'render_podcast_archive' ],
			'podcast-single'      => [ self::class, 'render_podcast_single' ],
			'news-mixed-grid'     => [ self::class, 'render_news_mixed_grid' ],
			'news-archive'        => [ self::class, 'render_news_archive' ],
			'video-grid'          => [ self::class, 'render_video_grid' ],
			'video-archive'       => [ self::class, 'render_video_archive' ],
			'events-grid'         => [ self::class, 'render_events_grid' ],
			'events-archive'      => [ self::class, 'render_events_archive' ],
			'frequency-grid'      => [ self::class, 'render_frequency_grid' ],
			'frequency-page'      => [ self::class, 'render_frequency_page' ],
			'whatsapp-cta'        => [ self::class, 'render_whatsapp_cta' ],
			'contact-page'        => [ self::class, 'render_contact_page' ],
			'about-page'          => [ self::class, 'render_about_page' ],
			'legal-page'          => [ self::class, 'render_legal_page' ],
			'newsletter-page'     => [ self::class, 'render_newsletter_page' ],
			'newsletter-cta'      => [ self::class, 'render_newsletter_cta' ],
			'program-quick-links' => [ self::class, 'render_program_quick_links' ],
		];

		$titles = self::block_titles();

		foreach ( $blocks as $name => $callback ) {
			$args = [
				'api_version'     => 3,
				'title'           => $titles[ $name ] ?? ucfirst( str_replace( '-', ' ', $name ) ),
				'category'        => str_starts_with( $name, 'site-' ) || $name === 'template-main' ? 'theme' : 'radio-rucphen',
				'icon'            => 'microphone',
				'description'     => sprintf( __( 'Radio Rucphen: %s.', 'radio-rucphen' ), $titles[ $name ] ?? $name ),
				'supports'        => [
					'html'  => false,
					'align' => false,
				],
				'render_callback' => $callback,
			];

			if ( $name === 'template-main' ) {
				$args['attributes'] = [
					'contained' => [
						'type'    => 'boolean',
						'default' => false,
					],
				];
			}

			register_block_type(
				self::NAMESPACE . '/' . $name,
				$args
			);
		}
	}

	/**
	 * @return array<string, string>
	 */
	private static function block_titles(): array {
		return [
			'template-main'       => __( 'Template main', 'radio-rucphen' ),
			'site-header'         => __( 'Site header', 'radio-rucphen' ),
			'site-footer'         => __( 'Site footer', 'radio-rucphen' ),
			'live-hero'           => __( 'Live hero', 'radio-rucphen' ),
			'sticky-player'       => __( 'Sticky player', 'radio-rucphen' ),
			'program-archive'     => __( 'Programmagids', 'radio-rucphen' ),
			'program-single'      => __( 'Programma detail', 'radio-rucphen' ),
			'presenter-archive'   => __( 'DJ\'s en presentatoren', 'radio-rucphen' ),
			'presenter-single'    => __( 'Presentator detail', 'radio-rucphen' ),
			'featured-programs'   => __( 'Uitgelichte programma\'s', 'radio-rucphen' ),
			'recent-podcasts'     => __( 'Gemiste uitzendingen', 'radio-rucphen' ),
			'podcast-archive'     => __( 'Gemist archief', 'radio-rucphen' ),
			'podcast-single'      => __( 'Gemiste uitzending', 'radio-rucphen' ),
			'news-mixed-grid'     => __( 'Lokaal nieuws (Zuidwest Update)', 'radio-rucphen' ),
			'news-archive'        => __( 'Nieuws archief', 'radio-rucphen' ),
			'video-grid'          => __( 'Video\'s uit de regio', 'radio-rucphen' ),
			'video-archive'       => __( 'Video archief', 'radio-rucphen' ),
			'events-grid'         => __( 'Agenda', 'radio-rucphen' ),
			'events-archive'      => __( 'Agenda archief', 'radio-rucphen' ),
			'frequency-grid'      => __( 'Frequenties', 'radio-rucphen' ),
			'frequency-page'      => __( 'Frequenties pagina', 'radio-rucphen' ),
			'whatsapp-cta'        => __( 'WhatsApp verzoekje CTA', 'radio-rucphen' ),
			'contact-page'        => __( 'Contact pagina', 'radio-rucphen' ),
			'about-page'          => __( 'Over ons pagina', 'radio-rucphen' ),
			'legal-page'          => __( 'Juridische pagina', 'radio-rucphen' ),
			'newsletter-page'     => __( 'Nieuwsbrief pagina', 'radio-rucphen' ),
			'newsletter-cta'      => __( 'Nieuwsbrief CTA', 'radio-rucphen' ),
			'program-quick-links' => __( 'Snelle programma-links', 'radio-rucphen' ),
		];
	}

	/**
	 * @param array<string, mixed> $attributes
	 */
	public static function render_template_main( array $attributes = [], string $content = '' ): string {
		$content = trim( $content );

		if ( ! empty( $attributes['contained'] ) ) {
			$content = sprintf(
				'<div class="%s">%s</div>',
				esc_attr( self::CONTAINER . ' py-12' ),
				$content
			);
		}

		return sprintf(
			'<main id="maincontent" class="wp-block-group">%s</main>',
			$content
		);
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

	private static function presenter_cover( \WP_Post $presenter ): string {
		if ( has_post_thumbnail( $presenter ) ) {
			return (string) get_the_post_thumbnail_url( $presenter, 'rucphen-portrait' );
		}
		return self::theme_img( 'djs/' . $presenter->post_name . '.jpg' );
	}

	private static function section_head( string $title, string $sub, ?string $action_label = null, ?string $action_url = null ): string {
		$action = '';
		if ( $action_label !== null && $action_url !== null ) {
			$action = sprintf(
				'<a class="%s" href="%s">%s</a>',
				esc_attr( self::SECTION_ACTION ),
				esc_url( $action_url ),
				esc_html( $action_label )
			);
		}

		return sprintf(
			'<div class="%s">
				<div>
					<h2 class="%s">%s</h2>
					<p class="%s">%s</p>
				</div>%s
			</div>',
			esc_attr( self::SECTION_HEAD ),
			esc_attr( self::SECTION_TITLE ),
			esc_html( $title ),
			esc_attr( self::SECTION_SUBTITLE ),
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

		$primary_menu = self::render_primary_nav();

		ob_start();
		?>
		<a class="absolute left-4 top-3 z-[100] -translate-y-[180%] rounded-sm bg-white px-[0.85rem] py-[0.65rem] font-extrabold text-brand-dark shadow-md focus:translate-y-0" href="#maincontent"><?php esc_html_e( 'Naar hoofdcontent', 'radio-rucphen' ); ?></a>
		<header class="sticky top-0 z-40 bg-white text-brand shadow-[0_1px_0_rgb(0_53_118_/_0.08)]">
			<div class="hidden bg-brand text-[0.9rem] text-white lg:block">
				<div class="<?php echo esc_attr( self::CONTAINER ); ?> flex min-h-9 items-center gap-[1.4rem]">
					<?php if ( $studio_email !== '' ) : ?><a class="font-bold text-white no-underline" href="mailto:<?php echo esc_attr( $studio_email ); ?>"><?php echo esc_html( $studio_email ); ?></a><?php endif; ?>
					<?php if ( $fm !== '' ) : ?><span><?php echo esc_html( $fm ); ?> FM</span><?php endif; ?>
					<?php if ( $coverage !== '' ) : ?><span><?php echo esc_html( $coverage ); ?></span><?php endif; ?>
				</div>
			</div>
			<div class="<?php echo esc_attr( self::CONTAINER ); ?> grid min-h-[72px] grid-cols-[auto_minmax(0,1fr)_minmax(190px,290px)_auto] items-center gap-[1.1rem] max-lg:flex max-lg:justify-between">
				<a class="inline-flex min-h-11 min-w-0 items-center text-brand no-underline" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $station_name . ' home' ); ?>">
					<img class="h-auto w-[119px] max-w-[42vw] object-contain" src="<?php echo esc_url( self::theme_img( 'logo-menu.png' ) ); ?>" width="119" height="44" alt="<?php echo esc_attr( $station_name ); ?>">
				</a>
				<?php echo $primary_menu; ?>
				<button class="ml-auto inline-flex min-h-[42px] w-[min(100%,290px)] items-center justify-start justify-self-end gap-[0.65rem] rounded-full bg-[#e9eef7] px-[1.05rem] font-extrabold text-brand max-lg:size-11 max-lg:shrink-0 max-lg:justify-center max-lg:p-0" type="button" aria-label="<?php esc_attr_e( 'Zoeken', 'radio-rucphen' ); ?>" data-search-open>
					<svg class="size-6 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10.8 4a6.8 6.8 0 0 1 5.4 10.9l3.4 3.4-1.3 1.3-3.4-3.4A6.8 6.8 0 1 1 10.8 4Zm0 1.8a5 5 0 1 0 0 10 5 5 0 0 0 0-10Z"/></svg>
					<span class="max-lg:hidden"><?php esc_html_e( 'Zoeken', 'radio-rucphen' ); ?></span>
				</button>
				<button class="hidden size-11 shrink-0 place-items-center rounded-sm border border-[#d7e4ef] bg-[#f5f8fc] text-[1.15rem] text-brand max-lg:grid" type="button" aria-label="<?php esc_attr_e( 'Menu', 'radio-rucphen' ); ?>" data-mobile-toggle aria-expanded="false">&#9776;</button>
			</div>
			<div class="hidden bg-brand max-lg:block">
				<div class="<?php echo esc_attr( self::CONTAINER ); ?> grid gap-1 py-2" data-mobile-panel hidden>
					<?php echo self::render_mobile_nav_links(); ?>
				</div>
			</div>
			<div class="hidden border-t-4 border-brand bg-surface text-ink">
				<div class="<?php echo esc_attr( self::CONTAINER ); ?> flex min-h-[38px] items-center gap-2 text-sm">
					<span class="<?php echo esc_attr( self::LIVE_DOT ); ?>" aria-hidden="true"></span>
					<strong><?php esc_html_e( 'Nu live:', 'radio-rucphen' ); ?></strong>
					<span data-live-now><?php echo esc_html( $station_name . ', ' . $tagline ); ?></span>
				</div>
			</div>
		</header>
		<dialog id="rucphen-search-overlay" class="w-[min(100%_-_2rem,760px)] rounded-card border-0 p-4 shadow-[0_24px_72px_rgb(15_23_42_/_0.24)] [&::backdrop]:bg-ink/45" data-component="search-overlay">
			<form class="grid grid-cols-[minmax(0,1fr)_auto_auto] gap-3 max-[767px]:grid-cols-1" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-search-form>
				<label class="sr-only" for="rucphen-search-input"><?php esc_html_e( 'Zoek op Radio Rucphen', 'radio-rucphen' ); ?></label>
				<input id="rucphen-search-input" class="min-h-12 rounded-sm border border-line px-3" type="search" name="s" placeholder="<?php esc_attr_e( 'Zoek programma, DJ, podcast of nieuws', 'radio-rucphen' ); ?>" autocomplete="off" data-search-input>
				<button class="min-h-12 rounded-sm border border-[#c9d7ec] bg-brand px-4 font-extrabold text-white" type="submit"><?php esc_html_e( 'Zoeken', 'radio-rucphen' ); ?></button>
				<button class="min-h-12 rounded-sm border border-[#c9d7ec] bg-white px-4 font-extrabold text-brand" type="button" data-search-close><?php esc_html_e( 'Sluiten', 'radio-rucphen' ); ?></button>
			</form>
			<div class="mt-4 grid gap-2 [&_.search-result]:grid [&_.search-result]:gap-1 [&_.search-result]:rounded-sm [&_.search-result]:border [&_.search-result]:border-line [&_.search-result]:p-3 [&_.search-result]:text-ink [&_.search-result]:no-underline" data-search-results aria-live="polite"></div>
		</dialog>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_primary_nav(): string {
		$native = self::render_native_menu( 'primary', 'primary' );
		if ( $native !== '' ) {
			return $native;
		}

		return self::render_primary_nav_fallback();
	}

	private static function render_primary_nav_fallback(): string {
		$current      = home_url( add_query_arg( null, null ) );
		$current_path = self::normalize_menu_path( $current );
		$primary      = self::menu_items_for_location( 'primary', self::default_primary_menu_items() );
		$radio        = self::menu_items_for_location( 'radio', self::default_radio_menu_items() );
		$is_radio     = self::menu_items_contain_current_path( $radio, $current_path );
		$link_class   = 'relative inline-flex min-h-[72px] items-center whitespace-nowrap px-[0.8rem] text-[0.95rem] font-extrabold text-brand no-underline hover:bg-[#f0f5fb] hover:text-brand';
		$menu_class   = 'absolute left-0 top-full z-50 hidden min-w-[210px] rounded-b-card bg-white p-2 shadow-md group-open:grid group-open:gap-1';
		$sub_class    = 'block rounded-sm px-3 py-2 text-sm font-extrabold text-brand no-underline hover:bg-[#f0f5fb]';

		$active_bar = static fn( bool $active ): string => $active ? '<span class="absolute inset-x-[0.8rem] bottom-0 h-[3px] bg-cyan" aria-hidden="true"></span>' : '';

		ob_start();
		?>
		<nav class="flex min-w-0 items-center justify-start gap-[0.1rem] max-lg:hidden" aria-label="<?php esc_attr_e( 'Hoofdnavigatie', 'radio-rucphen' ); ?>">
			<?php foreach ( $primary as $item ) :
				if ( self::is_radio_menu_item( $item ) ) :
					?>
					<details class="group relative">
						<summary class="<?php echo esc_attr( $link_class ); ?> cursor-pointer list-none [&::-webkit-details-marker]:hidden"<?php if ( $is_radio ) : ?> aria-current="page"<?php endif; ?>>
							<?php echo esc_html( $item['title'] ); ?>
							<?php echo $active_bar( $is_radio ); ?>
						</summary>
						<div class="<?php echo esc_attr( $menu_class ); ?>">
							<?php foreach ( $radio as $radio_item ) : ?>
								<a class="<?php echo esc_attr( $sub_class ); ?>" href="<?php echo esc_url( $radio_item['url'] ); ?>"><?php echo esc_html( $radio_item['title'] ); ?></a>
							<?php endforeach; ?>
						</div>
					</details>
					<?php
					continue;
				endif;

				$item_path  = self::normalize_menu_path( $item['url'] );
				$is_current = $item_path === $current_path;
				?>
				<a class="<?php echo esc_attr( $link_class ); ?>" href="<?php echo esc_url( $item['url'] ); ?>"<?php if ( $is_current ) : ?> aria-current="page"<?php endif; ?>>
					<?php echo esc_html( $item['title'] ); ?>
					<?php echo $active_bar( $is_current ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_mobile_nav_links(): string {
		$native = self::render_native_menu( 'mobile', 'mobile' );
		if ( $native !== '' ) {
			return $native;
		}

		$items = self::menu_items_for_location( 'mobile', self::default_mobile_menu_items() );

		ob_start();
		foreach ( $items as $item ) :
			self::print_mobile_nav_link( $item['url'], $item['title'] );
		endforeach;

		return (string) ob_get_clean();
	}

	private static function render_native_menu( string $location, string $variant ): string {
		if ( ! has_nav_menu( $location ) ) {
			return '';
		}

		self::add_native_menu_filters();

		$args = [
			'theme_location'       => $location,
			'container'            => false,
			'fallback_cb'          => false,
			'echo'                 => false,
			'depth'                => $variant === 'primary' ? 2 : 1,
			'menu_class'           => self::native_menu_class( $variant ),
			'items_wrap'           => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			'rucphen_variant'      => $variant,
			'rucphen_current_path' => self::normalize_menu_path( home_url( add_query_arg( null, null ) ) ),
		];

		$html = wp_nav_menu( $args );

		self::remove_native_menu_filters();

		if ( ! is_string( $html ) || trim( $html ) === '' ) {
			return '';
		}

		if ( $variant === 'primary' ) {
			return sprintf(
				'<nav class="flex min-w-0 items-center justify-start gap-[0.1rem] max-lg:hidden" aria-label="%s">%s</nav>',
				esc_attr__( 'Hoofdnavigatie', 'radio-rucphen' ),
				$html
			);
		}

		return $html;
	}

	private static function render_native_radio_submenu(): string {
		$native = self::render_native_menu_fragment( 'radio', 'primary-submenu' );
		if ( $native !== '' ) {
			return $native;
		}

		ob_start();
		?>
		<ul class="<?php echo esc_attr( self::native_menu_class( 'primary-submenu' ) ); ?>">
			<?php foreach ( self::default_radio_menu_items() as $item ) : ?>
				<li><a class="<?php echo esc_attr( self::native_link_class( 'primary-submenu', 0, false ) ); ?>" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_native_menu_fragment( string $location, string $variant ): string {
		if ( ! has_nav_menu( $location ) ) {
			return '';
		}

		$html = wp_nav_menu(
			[
				'theme_location'       => $location,
				'container'            => false,
				'fallback_cb'          => false,
				'echo'                 => false,
				'depth'                => 1,
				'menu_class'           => self::native_menu_class( $variant ),
				'items_wrap'           => '<ul id="%1$s" class="%2$s">%3$s</ul>',
				'rucphen_variant'      => $variant,
				'rucphen_current_path' => self::normalize_menu_path( home_url( add_query_arg( null, null ) ) ),
			]
		);

		return is_string( $html ) ? $html : '';
	}

	private static function add_native_menu_filters(): void {
		add_filter( 'nav_menu_css_class', [ self::class, 'filter_nav_menu_css_class' ], 10, 4 );
		add_filter( 'nav_menu_link_attributes', [ self::class, 'filter_nav_menu_link_attributes' ], 10, 4 );
		add_filter( 'nav_menu_submenu_css_class', [ self::class, 'filter_nav_menu_submenu_css_class' ], 10, 3 );
		add_filter( 'walker_nav_menu_start_el', [ self::class, 'filter_walker_nav_menu_start_el' ], 10, 4 );
	}

	private static function remove_native_menu_filters(): void {
		remove_filter( 'nav_menu_css_class', [ self::class, 'filter_nav_menu_css_class' ], 10 );
		remove_filter( 'nav_menu_link_attributes', [ self::class, 'filter_nav_menu_link_attributes' ], 10 );
		remove_filter( 'nav_menu_submenu_css_class', [ self::class, 'filter_nav_menu_submenu_css_class' ], 10 );
		remove_filter( 'walker_nav_menu_start_el', [ self::class, 'filter_walker_nav_menu_start_el' ], 10 );
	}

	/**
	 * @param array<int, string> $classes
	 * @return array<int, string>
	 */
	public static function filter_nav_menu_css_class( array $classes, $item, $args, int $depth ): array {
		$variant = self::native_menu_variant( $args );
		if ( $variant === '' ) {
			return $classes;
		}

		if ( $variant === 'primary' ) {
			$classes[] = 'relative';
			if ( in_array( 'menu-item-has-children', $classes, true ) || self::is_radio_wp_menu_item( $item ) ) {
				$classes[] = 'group';
			}
		}

		return $classes;
	}

	/**
	 * @param array<string, string> $atts
	 * @return array<string, string>
	 */
	public static function filter_nav_menu_link_attributes( array $atts, $item, $args, int $depth ): array {
		$variant = self::native_menu_variant( $args );
		if ( $variant === '' ) {
			return $atts;
		}

		$is_active     = self::is_active_wp_menu_item( $item );
		$current_path  = isset( $args->rucphen_current_path ) ? (string) $args->rucphen_current_path : '';
		$item_url      = is_object( $item ) && isset( $item->url ) ? (string) $item->url : '';
		$item_path     = self::normalize_menu_path( $item_url );
		$is_hash_link  = isset( $item_url[0] ) && $item_url[0] === '#';
		$is_active     = $is_active || ( ! $is_hash_link && $current_path !== '' && $item_path === $current_path );
		$radio_current = self::is_radio_wp_menu_item( $item ) && self::menu_items_contain_current_path( self::menu_items_for_location( 'radio', self::default_radio_menu_items() ), $current_path );
		$atts['class'] = self::native_link_class( $variant, $depth, $is_active || $radio_current );

		if ( $is_active || $radio_current ) {
			$atts['aria-current'] = 'page';
		}

		return $atts;
	}

	/**
	 * @param array<int, string> $classes
	 * @return array<int, string>
	 */
	public static function filter_nav_menu_submenu_css_class( array $classes, $args, int $depth ): array {
		$variant = self::native_menu_variant( $args );
		if ( $variant !== 'primary' ) {
			return $classes;
		}

		return array_merge( $classes, self::class_list( self::native_menu_class( 'primary-submenu' ) ) );
	}

	public static function filter_walker_nav_menu_start_el( string $item_output, $item, int $depth, $args ): string {
		$variant = self::native_menu_variant( $args );
		if ( $variant !== 'primary' || $depth !== 0 || ! self::is_radio_wp_menu_item( $item ) || self::wp_menu_item_has_children( $item ) ) {
			return $item_output;
		}

		return $item_output . self::render_native_radio_submenu();
	}

	private static function native_menu_variant( $args ): string {
		return is_object( $args ) && isset( $args->rucphen_variant ) ? (string) $args->rucphen_variant : '';
	}

	private static function native_menu_class( string $variant ): string {
		switch ( $variant ) {
			case 'primary':
				return 'm-0 flex list-none items-center gap-[0.1rem] p-0';
			case 'primary-submenu':
				return 'absolute left-0 top-full z-50 m-0 hidden min-w-[210px] list-none rounded-b-card bg-white p-2 shadow-md group-hover:grid group-focus-within:grid group-open:grid group-hover:gap-1 group-focus-within:gap-1';
			case 'mobile':
				return 'm-0 grid list-none gap-1 p-0';
			case 'footer':
				return 'mt-3 grid list-none gap-2 p-0';
			default:
				return '';
		}
	}

	private static function native_link_class( string $variant, int $depth, bool $active ): string {
		if ( $variant === 'primary' && $depth === 0 ) {
			$active_class = $active ? ' after:absolute after:inset-x-[0.8rem] after:bottom-0 after:h-[3px] after:bg-cyan' : '';
			return 'relative inline-flex min-h-[72px] items-center whitespace-nowrap px-[0.8rem] text-[0.95rem] font-extrabold text-brand no-underline hover:bg-[#f0f5fb] hover:text-brand' . $active_class;
		}

		if ( $variant === 'primary' || $variant === 'primary-submenu' ) {
			return 'block rounded-sm px-3 py-2 text-sm font-extrabold text-brand no-underline hover:bg-[#f0f5fb]';
		}

		if ( $variant === 'mobile' ) {
			return 'block rounded-sm px-3 py-2 text-base font-extrabold text-white no-underline hover:bg-white/10';
		}

		if ( $variant === 'footer' ) {
			return 'text-white/85 no-underline hover:text-accent';
		}

		return '';
	}

	/**
	 * @return array<int, string>
	 */
	private static function class_list( string $classes ): array {
		return array_values( array_filter( preg_split( '/\s+/', trim( $classes ) ) ?: [] ) );
	}

	/**
	 * @param array<int, array{url:string,title:string}> $fallback
	 * @return array<int, array{url:string,title:string}>
	 */
	private static function menu_items_for_location( string $location, array $fallback ): array {
		$locations = get_nav_menu_locations();
		if ( empty( $locations[ $location ] ) ) {
			return $fallback;
		}

		$items = wp_get_nav_menu_items( $locations[ $location ] );
		if ( ! is_array( $items ) || $items === [] ) {
			return $fallback;
		}

		$normalized = [];
		foreach ( $items as $item ) {
			if ( (int) $item->menu_item_parent !== 0 ) {
				continue;
			}
			$normalized[] = [
				'url'   => (string) $item->url,
				'title' => (string) $item->title,
			];
		}

		return $normalized === [] ? $fallback : $normalized;
	}

	/**
	 * @return array<int, array{url:string,title:string}>
	 */
	private static function default_primary_menu_items(): array {
		return [
			[ 'url' => home_url( '/' ), 'title' => __( 'Home', 'radio-rucphen' ) ],
			[ 'url' => '#radio-luisteren', 'title' => __( 'Radio luisteren', 'radio-rucphen' ) ],
			[ 'url' => home_url( '/podcasts/' ), 'title' => __( 'Gemist', 'radio-rucphen' ) ],
			[ 'url' => home_url( '/agenda/' ), 'title' => __( 'Acties', 'radio-rucphen' ) ],
		];
	}

	/**
	 * @return array<int, array{url:string,title:string}>
	 */
	private static function default_radio_menu_items(): array {
		return [
			[ 'url' => home_url( '/programma/' ), 'title' => __( 'Programmagids', 'radio-rucphen' ) ],
			[ 'url' => home_url( '/djs/' ), 'title' => __( 'DJ\'s', 'radio-rucphen' ) ],
			[ 'url' => home_url( '/frequenties/' ), 'title' => __( 'Frequenties', 'radio-rucphen' ) ],
		];
	}

	/**
	 * @return array<int, array{url:string,title:string}>
	 */
	private static function default_mobile_menu_items(): array {
		return [
			[ 'url' => home_url( '/' ), 'title' => __( 'Home', 'radio-rucphen' ) ],
			[ 'url' => home_url( '/programma/' ), 'title' => __( 'Programmagids', 'radio-rucphen' ) ],
			[ 'url' => home_url( '/djs/' ), 'title' => __( 'DJ\'s', 'radio-rucphen' ) ],
			[ 'url' => home_url( '/frequenties/' ), 'title' => __( 'Frequenties', 'radio-rucphen' ) ],
			[ 'url' => home_url( '/podcasts/' ), 'title' => __( 'Gemist', 'radio-rucphen' ) ],
			[ 'url' => home_url( '/agenda/' ), 'title' => __( 'Acties en agenda', 'radio-rucphen' ) ],
			[ 'url' => home_url( '/nieuws/' ), 'title' => __( 'Nieuws', 'radio-rucphen' ) ],
			[ 'url' => home_url( '/contact/' ), 'title' => __( 'Contact', 'radio-rucphen' ) ],
		];
	}

	/**
	 * @param array{url:string,title:string} $item
	 */
	private static function is_radio_menu_item( array $item ): bool {
		return sanitize_title( $item['title'] ) === 'radio-luisteren' || $item['url'] === '#radio-luisteren';
	}

	private static function is_radio_wp_menu_item( $item ): bool {
		if ( ! is_object( $item ) ) {
			return false;
		}

		$title = isset( $item->title ) ? (string) $item->title : '';
		$url   = isset( $item->url ) ? (string) $item->url : '';

		return sanitize_title( $title ) === 'radio-luisteren' || $url === '#radio-luisteren';
	}

	private static function is_active_wp_menu_item( $item ): bool {
		if ( ! is_object( $item ) || ! isset( $item->classes ) || ! is_array( $item->classes ) ) {
			return false;
		}

		return count( array_intersect( [ 'current-menu-item', 'current-menu-ancestor', 'current_page_item', 'current_page_ancestor' ], $item->classes ) ) > 0;
	}

	private static function wp_menu_item_has_children( $item ): bool {
		return is_object( $item )
			&& isset( $item->classes )
			&& is_array( $item->classes )
			&& in_array( 'menu-item-has-children', $item->classes, true );
	}

	/**
	 * @param array<int, array{url:string,title:string}> $items
	 */
	private static function menu_items_contain_current_path( array $items, string $current_path ): bool {
		foreach ( $items as $item ) {
			if ( self::normalize_menu_path( $item['url'] ) === $current_path ) {
				return true;
			}
		}
		return false;
	}

	private static function print_mobile_nav_link( string $url, string $title ): void {
		printf(
			'<a class="block rounded-sm px-3 py-2 text-base font-extrabold text-white no-underline hover:bg-white/10" href="%s">%s</a>',
			esc_url( $url ),
			esc_html( $title )
		);
	}

	private static function normalize_menu_path( string $url ): string {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( ! is_string( $path ) || $path === '' ) {
			$path = '/';
		}

		$path = '/' . ltrim( $path, '/' );
		$path = untrailingslashit( $path );

		return $path === '' ? '/' : $path;
	}

	private static function is_external_url( string $url ): bool {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! is_string( $host ) || $host === '' ) {
			return false;
		}

		$home_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		return is_string( $home_host ) && strtolower( $host ) !== strtolower( $home_host );
	}

	public static function render_site_footer(): string {
		$station = Settings::get( Settings::OPTION_STATION );
		$org     = Settings::get( Settings::OPTION_ORGANIZATION );
		$year    = (int) wp_date( 'Y' );
		$tagline = trim( (string) ( $station['tagline'] ?? '' ) );
		$footer_intro = $tagline !== '' ? $tagline . ( str_ends_with( $tagline, '.' ) ? ' ' : '. ' ) : '';

		$footer_menus = [
			'footer_listen'      => __( 'Luisteren', 'radio-rucphen' ),
			'footer_participate' => __( 'Meedoen', 'radio-rucphen' ),
			'footer_news'        => __( 'Nieuws', 'radio-rucphen' ),
			'footer_legal'       => __( 'Juridisch', 'radio-rucphen' ),
		];

		ob_start();
		?>
		<footer class="bg-bg-dark text-white">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?> py-14">
				<div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-5">
					<div class="lg:col-span-2">
						<h2 class="font-display text-2xl font-extrabold"><?php echo esc_html( (string) ( $station['name'] ?? 'Radio Rucphen' ) ); ?></h2>
						<p class="mt-2 max-w-md text-white/75"><?php echo esc_html( $footer_intro ); ?><?php esc_html_e( 'Lokale radio voor de gemeente Rucphen en de directe regio.', 'radio-rucphen' ); ?></p>
					</div>
					<?php foreach ( $footer_menus as $loc => $title ) :
						$menu = self::render_native_menu( $loc, 'footer' );
						if ( $menu === '' ) {
							continue;
						}
						?>
						<div>
							<h3 class="font-display text-base font-extrabold uppercase tracking-wider"><?php echo esc_html( $title ); ?></h3>
							<?php echo $menu; ?>
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
		$current = self::current_program();

		ob_start();
		?>
		<section class="relative isolate overflow-hidden bg-[image:var(--hero-bg)] bg-cover bg-center text-white" style="--hero-bg:url('<?php echo esc_url( $hero_bg ); ?>')">
			<span class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(0_40_88_/_0.92),rgb(0_53_118_/_0.72)_52%,rgb(0_53_118_/_0.38))]" aria-hidden="true"></span>
			<span class="absolute inset-x-0 bottom-0 -z-10 h-[34%] bg-[linear-gradient(0deg,rgb(0_30_70_/_0.62),rgb(0_30_70_/_0))]" aria-hidden="true"></span>
			<div class="<?php echo esc_attr( self::CONTAINER ); ?> grid min-h-[540px] grid-cols-[minmax(0,760px)] items-center">
				<div class="grid max-w-[760px] gap-[1.35rem] py-[3.2rem] max-[767px]:py-[2.6rem]">
					<span class="w-fit rounded-full bg-accent px-[0.9rem] py-[0.45rem] font-extrabold uppercase text-brand"><?php esc_html_e( 'Nu live', 'radio-rucphen' ); ?></span>
					<div class="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-[1.4rem] max-[767px]:grid-cols-1 max-[767px]:gap-4">
						<button class="grid size-[82px] place-items-center rounded-full bg-accent text-brand shadow-[0_18px_34px_rgb(0_0_0_/_0.18)] max-[767px]:size-16" type="button" data-hero-play aria-label="<?php esc_attr_e( 'Luister live', 'radio-rucphen' ); ?>">
							<svg class="size-[34px] translate-x-0.5 max-[767px]:size-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
						</button>
						<div>
							<p class="mb-[0.35rem] text-[0.92rem] font-extrabold uppercase text-[#ccefff]"><?php echo esc_html( (string) ( $station['name'] ?? 'Radio Rucphen' ) ); ?></p>
							<h1 class="m-0 max-w-[10em] [overflow-wrap:anywhere] font-display text-[4.05rem] font-extrabold leading-[1.08] max-[767px]:max-w-full max-[767px]:text-[2.4rem]"><?php echo esc_html( $current['title'] ?? (string) ( $station['tagline'] ?? 'Het geluid van Rucphen' ) ); ?></h1>
							<?php if ( ! empty( $current['subtitle'] ) ) : ?>
								<p class="mt-[0.35rem] text-[1.15rem] font-[850] text-[#f8fbff]"><?php echo esc_html( $current['subtitle'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
					<div class="grid w-[min(100%,620px)] grid-cols-[auto_64px_minmax(0,1fr)] items-center gap-4 rounded-card border-2 border-[#b9e5f9]/75 px-[1.05rem] py-[0.9rem] max-[767px]:grid-cols-[auto_48px_minmax(0,1fr)] max-[767px]:gap-3 max-[767px]:p-3">
						<span><?php esc_html_e( 'Nu speelt', 'radio-rucphen' ); ?></span>
						<img class="size-16 rounded-card object-cover max-[767px]:size-12" data-hero-cover src="<?php echo esc_url( self::theme_img( 'logo-square.png' ) ); ?>" width="64" height="64" alt="">
						<div>
							<strong class="block font-display text-[1.18rem] leading-tight" data-hero-title><?php echo esc_html( (string) ( $station['name'] ?? 'Radio Rucphen' ) ); ?> - Live</strong>
							<span class="block" data-hero-artist><?php echo esc_html( (string) ( $station['tagline'] ?? 'Het geluid van Rucphen' ) ); ?></span>
						</div>
					</div>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	private static function current_program(): array {
		$now    = current_datetime();
		$day_en = strtolower( $now->format( 'l' ) );
		$hhmm   = $now->format( 'H:i' );

		$programs = get_posts(
			[
				'post_type'      => PostTypes::PROGRAM,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			]
		);

		foreach ( $programs as $program ) {
			foreach ( self::program_airtimes( $program ) as $airtime ) {
				if ( $airtime['day'] !== $day_en ) {
					continue;
				}

				$start  = $airtime['start'];
				$end    = $airtime['end'];
				$is_now = $end > $start
					? ( $hhmm >= $start && $hhmm < $end )
					: ( $hhmm >= $start || $hhmm < $end );
				if ( ! $is_now ) {
					continue;
				}

				return [
					'title'    => get_the_title( $program ),
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
		<div class="fixed inset-x-0 bottom-0 z-50 min-h-[var(--player-height)] border-t border-white/15 bg-[linear-gradient(90deg,#003576_0%,#082c68_58%,#002858_100%)] text-white shadow-[0_-10px_28px_rgb(0_21_50_/_0.22)]" data-component="sticky-player" aria-label="<?php esc_attr_e( 'Live audio player', 'radio-rucphen' ); ?>">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?> relative grid min-h-[var(--player-height)] grid-cols-[64px_minmax(0,1fr)_minmax(190px,auto)] items-center gap-4 max-[767px]:grid-cols-[52px_minmax(0,1fr)_58px] max-[767px]:gap-[0.7rem]">
				<img class="size-16 rounded-sm object-cover shadow-[0_0_0_1px_rgb(255_255_255_/_0.14)] max-[767px]:size-12" data-player-cover src="<?php echo esc_url( self::theme_img( 'logo-square.png' ) ); ?>" width="64" height="64" alt="">
				<div class="min-w-0" aria-live="polite" aria-atomic="true">
					<div class="flex min-w-0 items-center gap-[0.7rem] max-[767px]:gap-[0.45rem]">
						<span class="inline-flex shrink-0 rounded-full bg-accent px-2 py-[0.18rem] text-[0.72rem] font-extrabold uppercase leading-none text-brand max-[767px]:hidden"><?php esc_html_e( 'Nu speelt', 'radio-rucphen' ); ?></span>
						<span class="min-w-0 truncate text-[0.9rem] font-extrabold text-[#d5edff] max-[767px]:text-[0.86rem]" data-player-artist><?php echo esc_html( (string) ( $station['tagline'] ?? '' ) ); ?></span>
					</div>
					<div class="mt-[0.18rem] truncate font-display text-[1.12rem] font-extrabold leading-tight text-white max-[767px]:mt-0 max-[767px]:text-base" data-player-title><?php echo esc_html( (string) ( $station['name'] ?? 'Radio Rucphen' ) ); ?> - Live</div>
				</div>
				<button class="absolute left-1/2 top-1/2 grid size-[58px] -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-accent text-brand shadow-[0_10px_24px_rgb(255_222_0_/_0.18)] hover:bg-white max-[767px]:size-[50px]" type="button" data-player-toggle aria-label="<?php esc_attr_e( 'Afspelen of pauzeren', 'radio-rucphen' ); ?>">
					<svg class="size-6 translate-x-px" data-player-icon-play viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
					<svg class="size-6" data-player-icon-pause viewBox="0 0 24 24" fill="currentColor" hidden><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>
				</button>
				<div class="col-start-3 flex items-center justify-self-end gap-3 max-[767px]:hidden">
					<a class="grid size-9 place-items-center rounded-full text-white no-underline hover:bg-white/10" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'WhatsApp de studio', 'radio-rucphen' ); ?>">
						<svg class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 4a8 8 0 0 0-6.8 12.2L4 20l3.9-1.1A8 8 0 1 0 12 4Zm0 1.8a6.2 6.2 0 1 1-3.4 11.4l-.3-.2-1.7.5.5-1.6-.2-.3A6.2 6.2 0 0 1 12 5.8Z"/></svg>
					</a>
					<label class="inline-flex items-center gap-[0.65rem] text-white">
						<span class="sr-only"><?php esc_html_e( 'Volume', 'radio-rucphen' ); ?></span>
						<svg class="size-6 fill-current" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4Zm12.5 3a4.5 4.5 0 0 0-2.1-3.8v7.6a4.5 4.5 0 0 0 2.1-3.8Z"/></svg>
						<input class="w-[126px] accent-white" type="range" min="0" max="100" value="80" data-player-volume aria-label="<?php esc_attr_e( 'Volume', 'radio-rucphen' ); ?>">
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
				'meta_query'     => [ [ 'key' => '_rucphen_program_featured', 'value' => '1' ] ],
				'meta_key'       => '_rucphen_program_default_start',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			]
		);

		ob_start();
		?>
		<section class="bg-bg-app py-16">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<?php echo self::section_head(
					__( 'Uitgelicht', 'radio-rucphen' ),
					__( 'Vier programma\'s die deze week extra aandacht krijgen.', 'radio-rucphen' ),
					__( 'Alle programma\'s', 'radio-rucphen' ),
					get_post_type_archive_link( PostTypes::PROGRAM ) ?: '#'
				); ?>
				<div class="grid grid-cols-[2fr_1fr] grid-rows-[repeat(3,150px)] gap-4 max-[767px]:grid-cols-1 max-[767px]:grid-rows-none">
					<?php
					$idx = 0;
					foreach ( $query->posts as $post ) :
						$is_large   = $idx === 0;
						$cover      = self::program_cover( $post );
						$presenters = self::program_presenters( $post );
						$label      = self::program_broadcast_label( $post );
						?>
						<a class="group relative overflow-hidden rounded-card bg-brand text-white no-underline shadow-sm max-[767px]:min-h-[260px] <?php echo $is_large ? 'row-span-3 max-[767px]:row-auto' : ''; ?>" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
							<img class="h-full w-full object-cover transition duration-[220ms] group-hover:scale-[1.035]" src="<?php echo esc_url( $cover ); ?>" loading="<?php echo $is_large ? 'eager' : 'lazy'; ?>" alt="">
							<span class="absolute inset-0 bg-[linear-gradient(0deg,rgb(0_24_65_/_0.84),rgb(0_24_65_/_0.08)_62%)]" aria-hidden="true"></span>
							<span class="absolute inset-x-4 bottom-4 grid gap-[0.35rem] text-white">
								<?php if ( $label !== '' ) : ?>
									<span class="inline-flex min-h-[28px] w-full items-center rounded-full bg-accent px-[0.72rem] py-[0.22rem] text-[0.72rem] font-extrabold uppercase text-brand"><?php echo esc_html( $label ); ?></span>
								<?php endif; ?>
								<strong class="max-w-[760px] font-display <?php echo $is_large ? 'text-[2.45rem] max-[767px]:text-[2rem]' : 'text-[1.08rem] max-[767px]:text-xl'; ?> font-extrabold leading-[1.08]"><?php echo esc_html( get_the_title( $post ) ); ?></strong>
								<?php if ( $presenters !== '' ) : ?>
									<span><?php echo esc_html( $presenters ); ?></span>
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

	public static function render_recent_podcasts(): string {
		$query = self::podcast_query( 4 );

		ob_start();
		?>
		<section class="bg-bg-app py-16">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<?php echo self::section_head(
					__( 'Gemiste uitzendingen', 'radio-rucphen' ),
					__( 'Luister recente uitzendingen terug wanneer het jou uitkomt.', 'radio-rucphen' ),
					__( 'Naar alle gemist', 'radio-rucphen' ),
					get_post_type_archive_link( PostTypes::PODCAST ) ?: home_url( '/podcasts/' )
				); ?>
				<div class="grid grid-cols-4 gap-5 max-[1023px]:grid-cols-2 max-[767px]:grid-cols-1">
					<?php foreach ( $query->posts as $podcast ) :
						if ( ! $podcast instanceof \WP_Post ) {
							continue;
						}
						echo self::podcast_card( $podcast );
					endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_program_single(): string {
		$program = get_queried_object();
		if ( ! $program instanceof \WP_Post || $program->post_type !== PostTypes::PROGRAM ) {
			return '';
		}

		$presenters      = self::program_presenter_posts( $program );
		$broadcast       = self::program_broadcast_label( $program );
		$short           = self::program_short_description( $program );
		$long            = trim( (string) get_post_meta( $program->ID, '_rucphen_program_long_description', true ) );
		if ( $long === '' ) {
			$long = $program->post_content;
		}
		$wa_url = self::whatsapp_url();

		ob_start();
		?>
		<section class="bg-bg-app py-12">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<nav class="mb-6 flex flex-wrap items-center gap-2 text-sm font-bold text-ink-soft" aria-label="<?php esc_attr_e( 'Kruimelpad', 'radio-rucphen' ); ?>">
					<a class="text-brand no-underline hover:underline" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'radio-rucphen' ); ?></a>
					<span aria-hidden="true">&rsaquo;</span>
					<a class="text-brand no-underline hover:underline" href="<?php echo esc_url( get_post_type_archive_link( PostTypes::PROGRAM ) ?: home_url( '/programma/' ) ); ?>"><?php esc_html_e( 'Programma', 'radio-rucphen' ); ?></a>
					<span aria-hidden="true">&rsaquo;</span>
					<span><?php echo esc_html( get_the_title( $program ) ); ?></span>
				</nav>

				<div class="grid grid-cols-[360px_minmax(0,1fr)] gap-8 max-[767px]:grid-cols-1">
					<div class="order-2 max-[767px]:order-auto">
						<p class="mb-[0.8rem] inline-flex items-center rounded-full bg-accent px-[0.72rem] py-[0.28rem] text-[0.78rem] font-black uppercase text-brand"><?php esc_html_e( 'Programma', 'radio-rucphen' ); ?></p>
						<h1 class="m-0 font-display text-[clamp(2.2rem,2rem_+_1.6vw,4rem)] font-extrabold leading-[1.08] text-ink"><?php echo esc_html( get_the_title( $program ) ); ?></h1>
						<?php if ( $broadcast !== '' ) : ?>
							<p class="mt-3 text-[1.05rem] font-extrabold text-brand"><?php echo esc_html( $broadcast ); ?></p>
						<?php endif; ?>
						<?php if ( $short !== '' ) : ?>
							<p class="mt-5 max-w-[720px] text-[1.14rem] leading-7 text-ink-soft"><?php echo esc_html( $short ); ?></p>
						<?php endif; ?>
						<div class="mt-6 flex flex-wrap gap-3">
							<a class="inline-flex min-h-11 items-center rounded-sm bg-brand px-4 py-2 font-extrabold text-white no-underline hover:bg-brand-dark" href="#radio-luisteren"><?php esc_html_e( 'Luister live', 'radio-rucphen' ); ?></a>
							<a class="inline-flex min-h-11 items-center rounded-sm border border-[#c9d7ec] bg-white px-4 py-2 font-extrabold text-brand no-underline hover:bg-[#e9eef7]" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Verzoekje via WhatsApp', 'radio-rucphen' ); ?></a>
						</div>

						<section class="mt-10">
							<h2 class="font-display text-2xl font-extrabold leading-tight text-ink"><?php esc_html_e( 'Over het programma', 'radio-rucphen' ); ?></h2>
							<div class="mt-4 grid gap-4 text-ink-soft">
								<?php echo wp_kses_post( wpautop( $long ) ); ?>
								<?php if ( $short !== '' && ! str_contains( wp_strip_all_tags( $long ), $short ) ) : ?>
									<p><?php echo esc_html( $short ); ?></p>
								<?php endif; ?>
							</div>
						</section>
					</div>

					<aside class="order-1 grid content-start gap-5 max-[767px]:order-auto">
						<img class="aspect-[4/3] w-full rounded-card bg-[#eef3f8] object-cover shadow-sm" src="<?php echo esc_url( self::program_cover( $program ) ); ?>" alt="<?php echo esc_attr( get_the_title( $program ) ); ?>">
						<?php if ( $presenters !== [] ) : ?>
							<div class="rounded-card border border-line bg-white p-5 shadow-sm">
								<h2 class="font-display text-xl font-extrabold text-ink"><?php echo count( $presenters ) === 1 ? esc_html__( 'Presentator', 'radio-rucphen' ) : esc_html__( 'Presentatoren', 'radio-rucphen' ); ?></h2>
								<div class="mt-4 grid gap-3">
									<?php foreach ( $presenters as $presenter ) : ?>
										<a class="grid grid-cols-[58px_minmax(0,1fr)] items-center gap-3 text-ink no-underline hover:text-brand" href="<?php echo esc_url( get_permalink( $presenter ) ); ?>">
											<img class="size-[58px] rounded-full object-cover" src="<?php echo esc_url( self::presenter_cover( $presenter ) ); ?>" alt="">
											<span>
												<strong class="block font-display text-lg font-extrabold"><?php echo esc_html( get_the_title( $presenter ) ); ?></strong>
												<?php $tagline = (string) get_post_meta( $presenter->ID, '_rucphen_presenter_tagline', true ); ?>
												<?php if ( $tagline !== '' ) : ?><span class="text-sm text-ink-soft"><?php echo esc_html( $tagline ); ?></span><?php endif; ?>
											</span>
										</a>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
						<div class="rounded-card border border-line bg-white p-5 shadow-sm">
							<h2 class="font-display text-xl font-extrabold text-ink"><?php esc_html_e( 'Uitzendingen', 'radio-rucphen' ); ?></h2>
							<p class="mt-3 text-ink-soft"><?php echo esc_html( $broadcast !== '' ? $broadcast : __( 'Uitzendtijden volgen binnenkort.', 'radio-rucphen' ) ); ?></p>
							<a class="mt-4 inline-flex min-h-10 items-center rounded-sm border border-[#c9d7ec] bg-white px-4 py-2 text-sm font-extrabold text-brand no-underline hover:bg-[#e9eef7]" href="<?php echo esc_url( get_post_type_archive_link( PostTypes::PROGRAM ) ?: home_url( '/programma/' ) ); ?>"><?php esc_html_e( 'Bekijk de gids', 'radio-rucphen' ); ?></a>
						</div>
					</aside>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_presenter_archive(): string {
		$query = new \WP_Query(
			[
				'post_type'      => PostTypes::PRESENTER,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_key'       => '_rucphen_presenter_order',
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			]
		);

		ob_start();
		?>
		<section class="relative isolate grid min-h-[320px] items-end overflow-hidden bg-brand-dark py-16 text-white">
			<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="<?php echo esc_url( self::theme_img( 'programs/muziek-cafe.jpg' ) ); ?>" alt="">
			<span class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(15_23_42_/_0.88),rgb(0_53_118_/_0.64)_58%,rgb(15_23_42_/_0.42))]" aria-hidden="true"></span>
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="max-w-[760px]">
					<p class="mb-[0.8rem] inline-flex items-center gap-[0.45rem] text-[0.88rem] font-black uppercase text-[#bfdbfe]"><?php esc_html_e( 'Radio luisteren', 'radio-rucphen' ); ?></p>
					<h1 class="m-0 font-display text-[clamp(2.2rem,2rem_+_1.6vw,4rem)] font-extrabold leading-[1.08]"><?php esc_html_e( 'DJ\'s en presentatoren', 'radio-rucphen' ); ?></h1>
					<p class="mt-4 max-w-[720px] text-[1.18rem] text-[#e2e8f0]"><?php esc_html_e( 'Maak kennis met de stemmen van Radio Rucphen.', 'radio-rucphen' ); ?></p>
				</div>
			</div>
		</section>

		<section class="bg-bg-app py-16">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="grid grid-cols-4 gap-5 max-[1023px]:grid-cols-2 max-[767px]:grid-cols-1">
					<?php foreach ( $query->posts as $presenter ) :
						if ( ! $presenter instanceof \WP_Post ) {
							continue;
						}
						$tagline = (string) get_post_meta( $presenter->ID, '_rucphen_presenter_tagline', true );
						?>
						<article class="overflow-hidden rounded-card border border-[#dce6f2] bg-white shadow-sm">
							<a class="block text-ink no-underline" href="<?php echo esc_url( get_permalink( $presenter ) ); ?>">
								<img class="aspect-square w-full bg-[#eef3f8] object-cover" src="<?php echo esc_url( self::presenter_cover( $presenter ) ); ?>" loading="lazy" alt="<?php echo esc_attr( get_the_title( $presenter ) ); ?>">
								<span class="grid gap-2 p-4">
									<strong class="font-display text-[1.18rem] font-extrabold leading-[1.12] text-ink"><?php echo esc_html( get_the_title( $presenter ) ); ?></strong>
									<?php if ( $tagline !== '' ) : ?>
										<span class="text-ink-soft"><?php echo esc_html( $tagline ); ?></span>
									<?php endif; ?>
								</span>
							</a>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_presenter_single(): string {
		$presenter = get_queried_object();
		if ( ! $presenter instanceof \WP_Post || $presenter->post_type !== PostTypes::PRESENTER ) {
			return '';
		}

		$tagline  = (string) get_post_meta( $presenter->ID, '_rucphen_presenter_tagline', true );
		$programs = self::presenter_programs( $presenter );

		ob_start();
		?>
		<section class="bg-bg-app py-12">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<nav class="mb-6 flex flex-wrap items-center gap-2 text-sm font-bold text-ink-soft" aria-label="<?php esc_attr_e( 'Kruimelpad', 'radio-rucphen' ); ?>">
					<a class="text-brand no-underline hover:underline" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'radio-rucphen' ); ?></a>
					<span aria-hidden="true">&rsaquo;</span>
					<a class="text-brand no-underline hover:underline" href="<?php echo esc_url( get_post_type_archive_link( PostTypes::PRESENTER ) ?: home_url( '/djs/' ) ); ?>"><?php esc_html_e( 'DJ\'s', 'radio-rucphen' ); ?></a>
					<span aria-hidden="true">&rsaquo;</span>
					<span><?php echo esc_html( get_the_title( $presenter ) ); ?></span>
				</nav>

				<div class="grid grid-cols-[360px_minmax(0,1fr)] gap-8 max-[767px]:grid-cols-1">
					<div class="order-2 max-[767px]:order-auto">
						<p class="mb-[0.8rem] inline-flex items-center rounded-full bg-accent px-[0.72rem] py-[0.28rem] text-[0.78rem] font-black uppercase text-brand"><?php esc_html_e( 'Presentator', 'radio-rucphen' ); ?></p>
						<h1 class="m-0 font-display text-[clamp(2.2rem,2rem_+_1.6vw,4rem)] font-extrabold leading-[1.08] text-ink"><?php echo esc_html( get_the_title( $presenter ) ); ?></h1>
						<?php if ( $tagline !== '' ) : ?>
							<p class="mt-3 text-[1.12rem] font-extrabold text-brand"><?php echo esc_html( $tagline ); ?></p>
						<?php endif; ?>
						<a class="mt-6 inline-flex min-h-11 items-center rounded-sm border border-[#c9d7ec] bg-white px-4 py-2 font-extrabold text-brand no-underline hover:bg-[#e9eef7]" href="<?php echo esc_url( get_post_type_archive_link( PostTypes::PROGRAM ) ?: home_url( '/programma/' ) ); ?>"><?php esc_html_e( 'Bekijk de gids', 'radio-rucphen' ); ?></a>

						<section class="mt-10">
							<h2 class="font-display text-2xl font-extrabold leading-tight text-ink"><?php esc_html_e( 'Bio', 'radio-rucphen' ); ?></h2>
							<div class="mt-4 grid gap-4 text-ink-soft"><?php echo wp_kses_post( wpautop( $presenter->post_content ) ); ?></div>
						</section>

						<?php if ( $programs !== [] ) : ?>
							<section class="mt-10">
								<h2 class="font-display text-2xl font-extrabold leading-tight text-ink"><?php printf( esc_html__( 'Programma\'s van %s', 'radio-rucphen' ), esc_html( get_the_title( $presenter ) ) ); ?></h2>
								<div class="mt-4 grid gap-3">
									<?php foreach ( $programs as $program ) : ?>
										<a class="grid grid-cols-[145px_minmax(0,1fr)] gap-4 rounded-card border border-line bg-white p-4 text-ink no-underline hover:border-[#bfdbfe] hover:shadow-sm max-[767px]:grid-cols-1" href="<?php echo esc_url( get_permalink( $program ) ); ?>">
											<span class="font-black text-brand-dark"><?php echo esc_html( self::program_broadcast_label( $program ) ); ?></span>
											<span>
												<strong class="block font-display text-[1.18rem] font-extrabold leading-[1.08]"><?php echo esc_html( get_the_title( $program ) ); ?></strong>
												<span class="mt-1 block text-sm text-ink-soft"><?php echo esc_html( self::program_short_description( $program ) ); ?></span>
											</span>
										</a>
									<?php endforeach; ?>
								</div>
							</section>
						<?php endif; ?>
					</div>

					<aside class="order-1 max-[767px]:order-auto">
						<img class="aspect-[4/5] w-full rounded-card bg-[#eef3f8] object-cover shadow-sm" src="<?php echo esc_url( self::presenter_cover( $presenter ) ); ?>" alt="<?php echo esc_attr( get_the_title( $presenter ) ); ?>">
					</aside>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_podcast_archive(): string {
		$query    = self::podcast_query( -1 );
		$programs = self::podcast_program_options( $query->posts );

		ob_start();
		?>
		<section class="relative isolate grid min-h-[320px] items-end overflow-hidden bg-brand-dark py-16 text-white">
			<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="<?php echo esc_url( self::theme_img( 'programs/lunchradio.jpg' ) ); ?>" alt="">
			<span class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(15_23_42_/_0.88),rgb(0_53_118_/_0.64)_58%,rgb(15_23_42_/_0.42))]" aria-hidden="true"></span>
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="max-w-[760px]">
					<p class="mb-[0.8rem] inline-flex items-center gap-[0.45rem] text-[0.88rem] font-black uppercase text-[#bfdbfe]"><?php esc_html_e( 'Gemist', 'radio-rucphen' ); ?></p>
					<h1 class="m-0 font-display text-[clamp(2.2rem,2rem_+_1.6vw,4rem)] font-extrabold leading-[1.08]"><?php esc_html_e( 'Gemiste uitzendingen', 'radio-rucphen' ); ?></h1>
					<p class="mt-4 max-w-[720px] text-[1.18rem] text-[#e2e8f0]"><?php esc_html_e( 'Luister eerdere uitzendingen terug. Een podcast-player pauzeert de live-stream.', 'radio-rucphen' ); ?></p>
				</div>
			</div>
		</section>

		<section class="bg-bg-app py-16" data-component="podcast-archive">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<nav class="mb-6 flex flex-wrap gap-2" aria-label="<?php esc_attr_e( 'Filter podcasts op programma', 'radio-rucphen' ); ?>">
					<button class="min-h-11 rounded-full border border-line bg-white px-[0.8rem] py-[0.45rem] font-black text-ink transition aria-pressed:border-brand aria-pressed:bg-brand aria-pressed:text-white hover:border-[#bfdbfe]" type="button" data-podcast-filter="all" aria-pressed="true"><?php esc_html_e( 'Alle', 'radio-rucphen' ); ?></button>
					<?php foreach ( $programs as $slug => $title ) : ?>
						<button class="min-h-11 rounded-full border border-line bg-white px-[0.8rem] py-[0.45rem] font-black text-ink transition aria-pressed:border-brand aria-pressed:bg-brand aria-pressed:text-white hover:border-[#bfdbfe]" type="button" data-podcast-filter="<?php echo esc_attr( $slug ); ?>" aria-pressed="false"><?php echo esc_html( $title ); ?></button>
					<?php endforeach; ?>
				</nav>
				<div class="grid gap-4">
					<?php foreach ( $query->posts as $podcast ) :
						if ( ! $podcast instanceof \WP_Post ) {
							continue;
						}
						echo self::podcast_row( $podcast );
					endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_podcast_single(): string {
		$podcast = get_queried_object();
		if ( ! $podcast instanceof \WP_Post ) {
			return '';
		}

		$program = self::podcast_program( $podcast );
		$presenters = $program instanceof \WP_Post ? self::program_presenters( $program ) : '';
		$meta_parts = array_filter( [ $presenters, self::podcast_meta_label( $podcast ) ] );
		$audio_url = (string) get_post_meta( $podcast->ID, '_rucphen_podcast_audio_url', true );
		$tracks = Meta::sanitize_podcast_tracks( get_post_meta( $podcast->ID, '_rucphen_podcast_tracks', true ) );

		ob_start();
		?>
		<section class="bg-bg-app py-12">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?> max-w-[920px]">
				<nav class="mb-6 flex flex-wrap items-center gap-2 text-sm font-bold text-ink-soft" aria-label="<?php esc_attr_e( 'Kruimelpad', 'radio-rucphen' ); ?>">
					<a class="text-brand no-underline hover:underline" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'radio-rucphen' ); ?></a>
					<span aria-hidden="true">&rsaquo;</span>
					<a class="text-brand no-underline hover:underline" href="<?php echo esc_url( get_post_type_archive_link( PostTypes::PODCAST ) ?: home_url( '/podcasts/' ) ); ?>"><?php esc_html_e( 'Podcasts', 'radio-rucphen' ); ?></a>
					<span aria-hidden="true">&rsaquo;</span>
					<span><?php echo esc_html( get_the_title( $podcast ) ); ?></span>
				</nav>
				<h1 class="m-0 font-display text-[clamp(2.1rem,1.85rem_+_1.5vw,3.8rem)] font-extrabold leading-[1.08] text-ink"><?php echo esc_html( get_the_title( $podcast ) ); ?></h1>
				<?php if ( $meta_parts !== [] ) : ?>
					<p class="mt-3 text-[1rem] font-bold text-ink-soft"><?php echo esc_html( implode( ' · ', $meta_parts ) ); ?></p>
				<?php endif; ?>

				<?php if ( $audio_url !== '' ) : ?>
					<div class="mt-6 rounded-card border border-line bg-white p-5 shadow-sm">
						<p class="mb-3 text-sm font-bold text-ink-soft"><?php esc_html_e( 'Je pauzeert hiermee de live-uitzending.', 'radio-rucphen' ); ?></p>
						<audio class="w-full" controls preload="metadata" src="<?php echo esc_url( $audio_url ); ?>" data-podcast-audio></audio>
					</div>
				<?php endif; ?>

				<div class="prose mt-8 max-w-none text-ink">
					<h2 class="font-display text-2xl font-extrabold"><?php esc_html_e( 'Beschrijving', 'radio-rucphen' ); ?></h2>
					<?php echo wp_kses_post( wpautop( $podcast->post_content ) ); ?>
				</div>

				<?php if ( $tracks !== [] ) : ?>
					<section class="mt-8">
						<h2 class="font-display text-2xl font-extrabold text-ink"><?php esc_html_e( 'Tracks in deze uitzending', 'radio-rucphen' ); ?></h2>
						<ul class="mt-4 grid gap-2">
							<?php foreach ( $tracks as $track ) : ?>
								<li class="grid grid-cols-[70px_minmax(0,1fr)] gap-3 rounded-sm bg-white p-3 text-ink">
									<span class="font-black text-brand-dark"><?php echo esc_html( $track['time'] ); ?></span>
									<span><?php echo esc_html( trim( $track['artist'] . ' - ' . $track['title'], ' -' ) ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	private static function podcast_query( int $limit ): \WP_Query {
		return new \WP_Query(
			[
				'post_type'      => PostTypes::PODCAST,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'meta_key'       => '_rucphen_podcast_date',
				'orderby'        => 'meta_value',
				'order'          => 'DESC',
				'no_found_rows'  => $limit > 0,
			]
		);
	}

	private static function podcast_card( \WP_Post $podcast ): string {
		ob_start();
		?>
		<article class="overflow-hidden rounded-card border border-[#dce6f2] bg-white shadow-sm" data-podcast-card>
			<a class="grid h-full grid-rows-[auto_minmax(0,1fr)] text-ink no-underline" href="<?php echo esc_url( get_permalink( $podcast ) ); ?>">
				<img class="aspect-[16/9] w-full bg-[#eef3f8] object-cover" src="<?php echo esc_url( self::podcast_cover( $podcast ) ); ?>" loading="lazy" alt="">
				<span class="grid gap-2 p-4">
					<span class="text-[0.82rem] font-extrabold text-[#64748b]"><?php echo esc_html( self::podcast_meta_label( $podcast ) ); ?></span>
					<strong class="font-display text-[1.08rem] font-extrabold leading-[1.14] text-ink"><?php echo esc_html( get_the_title( $podcast ) ); ?></strong>
					<span class="line-clamp-3 text-[0.95rem] text-ink-soft"><?php echo esc_html( self::podcast_description( $podcast ) ); ?></span>
				</span>
			</a>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	private static function podcast_row( \WP_Post $podcast ): string {
		$slug = (string) get_post_meta( $podcast->ID, '_rucphen_podcast_program_slug', true );

		ob_start();
		?>
		<article class="grid grid-cols-[92px_minmax(0,1fr)_auto] items-center gap-5 rounded-card border border-line bg-white p-4 shadow-sm max-[767px]:grid-cols-[72px_minmax(0,1fr)] max-[767px]:items-start" data-podcast-card data-podcast-program="<?php echo esc_attr( $slug ); ?>">
			<img class="size-[92px] rounded-sm bg-[#eef3f8] object-cover max-[767px]:size-[72px]" src="<?php echo esc_url( self::podcast_cover( $podcast ) ); ?>" loading="lazy" alt="">
			<div class="min-w-0">
				<p class="m-0 text-[0.82rem] font-extrabold text-[#64748b]"><?php echo esc_html( self::podcast_meta_label( $podcast ) ); ?></p>
				<h2 class="mt-1 font-display text-[1.28rem] font-extrabold leading-[1.12] text-ink"><a class="text-ink no-underline hover:text-brand" href="<?php echo esc_url( get_permalink( $podcast ) ); ?>"><?php echo esc_html( get_the_title( $podcast ) ); ?></a></h2>
				<p class="mt-1 text-ink-soft"><?php echo esc_html( self::podcast_description( $podcast ) ); ?></p>
			</div>
			<a class="inline-flex min-h-11 items-center justify-center rounded-sm border border-[#c9d7ec] bg-white px-4 py-2 text-sm font-extrabold text-brand no-underline hover:bg-[#e9eef7] max-[767px]:col-span-2 max-[767px]:w-full" href="<?php echo esc_url( get_permalink( $podcast ) ); ?>"><?php esc_html_e( 'Afspelen', 'radio-rucphen' ); ?></a>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @param array<int, mixed> $podcasts
	 * @return array<string, string>
	 */
	private static function podcast_program_options( array $podcasts ): array {
		$options = [];
		foreach ( $podcasts as $podcast ) {
			if ( ! $podcast instanceof \WP_Post ) {
				continue;
			}
			$slug = (string) get_post_meta( $podcast->ID, '_rucphen_podcast_program_slug', true );
			$program = self::podcast_program( $podcast );
			if ( $slug !== '' && $program instanceof \WP_Post ) {
				$options[ $slug ] = get_the_title( $program );
			}
		}

		return $options;
	}

	private static function podcast_program( \WP_Post $podcast ): ?\WP_Post {
		$program_id = (int) get_post_meta( $podcast->ID, '_rucphen_podcast_program_id', true );
		$program = $program_id > 0 ? get_post( $program_id ) : null;
		if ( $program instanceof \WP_Post && $program->post_type === PostTypes::PROGRAM ) {
			return $program;
		}

		$slug = (string) get_post_meta( $podcast->ID, '_rucphen_podcast_program_slug', true );
		if ( $slug === '' ) {
			return null;
		}

		$posts = get_posts(
			[
				'post_type'      => PostTypes::PROGRAM,
				'name'           => $slug,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			]
		);

		return $posts[0] ?? null;
	}

	private static function podcast_cover( \WP_Post $podcast ): string {
		if ( has_post_thumbnail( $podcast ) ) {
			return (string) get_the_post_thumbnail_url( $podcast, 'rucphen-card' );
		}

		$program = self::podcast_program( $podcast );
		if ( $program instanceof \WP_Post ) {
			return self::program_cover( $program );
		}

		$slug = (string) get_post_meta( $podcast->ID, '_rucphen_podcast_program_slug', true );
		return $slug !== '' ? self::theme_img( 'programs/' . $slug . '.jpg' ) : self::theme_img( 'programs/wakker-met-rucphen.jpg' );
	}

	private static function podcast_description( \WP_Post $podcast ): string {
		$program = self::podcast_program( $podcast );
		if ( $program instanceof \WP_Post ) {
			$short = self::program_short_description( $program );
			if ( $short !== '' ) {
				return $short;
			}
		}

		$text = $podcast->post_excerpt !== '' ? $podcast->post_excerpt : $podcast->post_content;
		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) ?? '' );
	}

	private static function podcast_meta_label( \WP_Post $podcast ): string {
		$date = (string) get_post_meta( $podcast->ID, '_rucphen_podcast_date', true );
		$ts = $date !== '' ? strtotime( $date ) : strtotime( $podcast->post_date );
		$pretty = $ts ? wp_date( 'j F Y', $ts ) : '';
		$duration = self::podcast_duration_label( (int) get_post_meta( $podcast->ID, '_rucphen_podcast_duration_seconds', true ) );

		return trim( $pretty . ( $pretty !== '' && $duration !== '' ? ' · ' : '' ) . $duration );
	}

	private static function podcast_duration_label( int $seconds ): string {
		if ( $seconds <= 0 ) {
			return '';
		}
		$hours = intdiv( $seconds, HOUR_IN_SECONDS );
		$minutes = intdiv( $seconds % HOUR_IN_SECONDS, MINUTE_IN_SECONDS );

		if ( $hours > 0 && $minutes === 0 ) {
			return sprintf( _n( '%d uur', '%d uur', $hours, 'radio-rucphen' ), $hours );
		}
		if ( $hours > 0 ) {
			return sprintf( __( '%1$d uur %2$d min', 'radio-rucphen' ), $hours, $minutes );
		}
		return sprintf( __( '%d min', 'radio-rucphen' ), max( 1, $minutes ) );
	}

	private static function program_presenters( \WP_Post $program ): string {
		$names = [];
		foreach ( self::program_presenter_posts( $program ) as $p ) {
			$names[ $p->ID ] = get_the_title( $p );
		}

		return implode( ', ', $names );
	}

	/**
	 * @return array<int, \WP_Post>
	 */
	private static function program_presenter_posts( \WP_Post $program ): array {
		$ids = array_map( 'intval', (array) get_post_meta( $program->ID, '_rucphen_program_presenter_ids', true ) );
		$posts = [];
		foreach ( $ids as $pid ) {
			$p = get_post( $pid );
			if ( $p instanceof \WP_Post && $p->post_type === PostTypes::PRESENTER ) {
				$posts[ $p->ID ] = $p;
			}
		}

		return array_values( $posts );
	}

	/**
	 * @return array<int, \WP_Post>
	 */
	private static function presenter_programs( \WP_Post $presenter ): array {
		$query = new \WP_Query(
			[
				'post_type'      => PostTypes::PROGRAM,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'meta_query'     => [
					'relation' => 'OR',
					[
						'key'     => '_rucphen_program_presenter_ids',
						'value'   => 'i:' . $presenter->ID . ';',
						'compare' => 'LIKE',
					],
					[
						'key'     => '_rucphen_program_presenter_ids',
						'value'   => '"' . $presenter->ID . '"',
						'compare' => 'LIKE',
					],
				],
			]
		);

		return array_values( array_filter( $query->posts, static fn( $post ): bool => $post instanceof \WP_Post ) );
	}

	private static function whatsapp_url( string $text = '' ): string {
		$contact = Settings::get( Settings::OPTION_CONTACT );
		$number  = preg_replace( '/\D+/', '', (string) ( $contact['whatsapp_number'] ?? '' ) );
		if ( $number === '' ) {
			$number = '31600000000';
		}

		if ( $text === '' ) {
			$text = (string) ( $contact['whatsapp_default_text'] ?? '' );
		}

		return 'https://wa.me/' . $number . '?text=' . rawurlencode( $text );
	}

	public static function render_program_archive(): string {
		$days = [
			'monday'    => [ __( 'Maandag', 'radio-rucphen' ), __( 'Ma', 'radio-rucphen' ) ],
			'tuesday'   => [ __( 'Dinsdag', 'radio-rucphen' ), __( 'Di', 'radio-rucphen' ) ],
			'wednesday' => [ __( 'Woensdag', 'radio-rucphen' ), __( 'Wo', 'radio-rucphen' ) ],
			'thursday'  => [ __( 'Donderdag', 'radio-rucphen' ), __( 'Do', 'radio-rucphen' ) ],
			'friday'    => [ __( 'Vrijdag', 'radio-rucphen' ), __( 'Vr', 'radio-rucphen' ) ],
			'saturday'  => [ __( 'Zaterdag', 'radio-rucphen' ), __( 'Za', 'radio-rucphen' ) ],
			'sunday'    => [ __( 'Zondag', 'radio-rucphen' ), __( 'Zo', 'radio-rucphen' ) ],
		];

		$day_by_number = [
			1 => 'monday',
			2 => 'tuesday',
			3 => 'wednesday',
			4 => 'thursday',
			5 => 'friday',
			6 => 'saturday',
			7 => 'sunday',
		];
		$today         = $day_by_number[ (int) wp_date( 'N' ) ] ?? 'monday';
		$by_day        = self::programs_grouped_by_airtime_day();

		ob_start();
		?>
		<section class="relative isolate grid min-h-[320px] items-end overflow-hidden bg-brand-dark py-16 text-white">
			<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="<?php echo esc_url( self::theme_img( 'programs/drivetime.jpg' ) ); ?>" alt="">
			<span class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(15_23_42_/_0.88),rgb(0_53_118_/_0.64)_58%,rgb(15_23_42_/_0.42))]" aria-hidden="true"></span>
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="max-w-[760px]">
					<p class="mb-[0.8rem] inline-flex items-center gap-[0.45rem] text-[0.88rem] font-black uppercase text-[#bfdbfe]"><?php esc_html_e( 'Radio luisteren', 'radio-rucphen' ); ?></p>
					<h1 class="m-0 font-display text-[clamp(2.2rem,2rem_+_1.6vw,4rem)] font-extrabold leading-[1.08]"><?php esc_html_e( 'Programmagids', 'radio-rucphen' ); ?></h1>
					<p class="mt-4 max-w-[720px] text-[1.18rem] text-[#e2e8f0]"><?php esc_html_e( 'Bekijk per dag welke programma\'s er op Radio Rucphen te horen zijn.', 'radio-rucphen' ); ?></p>
				</div>
			</div>
		</section>

		<section class="bg-bg-app py-16" data-component="program-guide">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<nav class="my-[1.4rem] flex flex-wrap gap-2" aria-label="<?php esc_attr_e( 'Dagen', 'radio-rucphen' ); ?>">
					<?php foreach ( $days as $slug => $labels ) : ?>
						<button class="min-h-11 rounded-full border border-line bg-white px-[0.8rem] py-[0.45rem] font-black text-ink transition aria-pressed:border-brand aria-pressed:bg-brand aria-pressed:text-white aria-selected:border-brand aria-selected:bg-brand aria-selected:text-white hover:border-[#bfdbfe]"
							type="button"
							data-day="<?php echo esc_attr( $slug ); ?>"
							aria-pressed="<?php echo $slug === $today ? 'true' : 'false'; ?>"
							aria-selected="<?php echo $slug === $today ? 'true' : 'false'; ?>">
							<?php echo esc_html( $labels[1] ); ?>
						</button>
					<?php endforeach; ?>
				</nav>

				<?php foreach ( $days as $slug => $labels ) : ?>
					<section data-day-panel="<?php echo esc_attr( $slug ); ?>" <?php echo $slug === $today ? '' : 'hidden'; ?>>
						<h2 class="mb-5 font-display text-[clamp(1.75rem,1.6rem_+_0.8vw,2.55rem)] font-extrabold leading-[1.08] text-ink"><?php echo esc_html( $labels[0] ); ?></h2>
						<?php if ( empty( $by_day[ $slug ] ) ) : ?>
							<p class="text-ink-soft"><?php esc_html_e( 'Geen programma\'s gepland.', 'radio-rucphen' ); ?></p>
						<?php else : ?>
							<div class="grid gap-3">
								<?php foreach ( $by_day[ $slug ] as $row ) : ?>
									<?php $is_live = self::is_airtime_live( $slug, (string) $row['start'], (string) $row['end'] ); ?>
									<a class="grid grid-cols-[145px_minmax(0,1fr)_auto] items-center gap-4 rounded-card border border-line bg-white p-4 text-ink no-underline transition hover:border-[#bfdbfe] hover:shadow-sm max-[767px]:grid-cols-1 max-[767px]:items-start"
										href="<?php echo esc_url( $row['program_url'] ); ?>"
										data-guide-row
										data-day="<?php echo esc_attr( $slug ); ?>"
										data-from="<?php echo esc_attr( $row['start'] ); ?>"
										data-to="<?php echo esc_attr( $row['end'] ); ?>">
										<span class="font-black text-brand-dark"><?php echo esc_html( $row['start'] ); ?> - <?php echo esc_html( $row['end'] ); ?></span>
										<span>
											<strong class="font-display text-[1.18rem] font-extrabold leading-[1.08] text-ink"><?php echo esc_html( $row['program_title'] ); ?></strong>
											<?php if ( ! empty( $row['program_meta_parts'] ) ) : ?>
												<br><span class="text-sm text-ink-soft"><?php echo wp_kses_post( implode( ' &middot; ', array_map( 'esc_html', $row['program_meta_parts'] ) ) ); ?></span>
											<?php endif; ?>
										</span>
										<span class="<?php echo $is_live ? '' : 'hidden '; ?>rounded-full bg-success/15 px-3 py-1 text-sm font-bold text-success" data-live-badge><?php esc_html_e( 'Nu live', 'radio-rucphen' ); ?></span>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</section>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	private static function is_airtime_live( string $day, string $start, string $end ): bool {
		$day_index = self::airtime_day_index( $day );
		if ( $day_index === 0 ) {
			return false;
		}

		$start_minutes = self::airtime_minutes( $start );
		$end_minutes   = self::airtime_minutes( $end );
		if ( $start_minutes < 0 || $end_minutes < 0 ) {
			return false;
		}

		$today = (int) wp_date( 'N' );
		$now   = ( (int) wp_date( 'G' ) * 60 ) + (int) wp_date( 'i' );

		if ( $end_minutes <= $start_minutes ) {
			$next_day = $day_index === 7 ? 1 : $day_index + 1;
			return ( $today === $day_index && $now >= $start_minutes )
				|| ( $today === $next_day && $now < $end_minutes );
		}

		return $today === $day_index && $now >= $start_minutes && $now < $end_minutes;
	}

	private static function airtime_day_index( string $day ): int {
		return [
			'monday'    => 1,
			'tuesday'   => 2,
			'wednesday' => 3,
			'thursday'  => 4,
			'friday'    => 5,
			'saturday'  => 6,
			'sunday'    => 7,
		][ $day ] ?? 0;
	}

	private static function airtime_minutes( string $time ): int {
		if ( ! preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', trim( $time ), $matches ) ) {
			return -1;
		}

		return ( (int) $matches[1] * 60 ) + (int) $matches[2];
	}

	private static function program_short_description( \WP_Post $program ): string {
		$short = trim( (string) get_post_meta( $program->ID, '_rucphen_program_short_description', true ) );
		if ( $short === '' ) {
			$short = $program->post_excerpt !== '' ? $program->post_excerpt : $program->post_content;
		}

		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $short ) ) ?? '' );
	}

	private static function programs_grouped_by_airtime_day(): array {
		$query = new \WP_Query(
			[
				'post_type'      => PostTypes::PROGRAM,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			]
		);

		$grouped = [];
		foreach ( $query->posts as $program ) {
			if ( ! $program instanceof \WP_Post ) {
				continue;
			}

			$presenters = self::program_presenters( $program );
			$short      = self::program_short_description( $program );
			$meta_parts = [];
			if ( $presenters !== '' ) {
				$meta_parts[] = $presenters;
			}
			if ( $short !== '' ) {
				$meta_parts[] = $short;
			}

			foreach ( self::program_airtimes( $program ) as $airtime ) {
				$grouped[ $airtime['day'] ][] = [
					'start'              => $airtime['start'],
					'end'                => $airtime['end'],
					'program_title'      => get_the_title( $program ),
					'program_url'        => get_permalink( $program ),
					'program_meta_parts' => $meta_parts,
				];
			}
		}

		foreach ( $grouped as $day => $rows ) {
			usort( $grouped[ $day ], static fn( $a, $b ) => strcmp( $a['start'], $b['start'] ) );
		}

		return $grouped;
	}

	private static function program_broadcast_label( \WP_Post $program ): string {
		$airtimes = self::program_airtimes( $program );
		if ( $airtimes === [] ) {
			return '';
		}

		$grouped = [];
		foreach ( $airtimes as $airtime ) {
			$key = $airtime['start'] . '|' . $airtime['end'];
			$grouped[ $key ][] = $airtime['day'];
		}

		$labels = [];
		foreach ( $grouped as $time => $days ) {
			[ $start, $end ] = explode( '|', $time, 2 );
			$labels[] = self::day_range_label( $days ) . ' ' . $start . ' - ' . $end;
		}
		if ( count( $labels ) > 2 ) {
			$extra  = count( $labels ) - 2;
			$labels = array_slice( $labels, 0, 2 );
			$labels[] = sprintf( '+%d', $extra );
		}

		return implode( ', ', $labels );
	}

	/**
	 * @return array<int, array{day:string,start:string,end:string}>
	 */
	private static function program_airtimes( \WP_Post $program ): array {
		$raw = get_post_meta( $program->ID, '_rucphen_program_airtimes', true );
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$order = array_flip( [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ] );
		$airtimes = [];
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$day   = sanitize_key( (string) ( $row['day'] ?? '' ) );
			$start = (string) ( $row['start'] ?? '' );
			$end   = (string) ( $row['end'] ?? '' );
			if ( ! isset( $order[ $day ] ) || ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start ) || ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end ) ) {
				continue;
			}

			$airtimes[] = [
				'day'   => $day,
				'start' => $start,
				'end'   => $end,
			];
		}

		usort(
			$airtimes,
			static fn( $a, $b ) => ( $order[ $a['day'] ] <=> $order[ $b['day'] ] ) ?: strcmp( $a['start'], $b['start'] )
		);

		return $airtimes;
	}

	private static function day_short_label( string $en ): string {
		$labels = [
			'monday'    => 'Ma',
			'tuesday'   => 'Di',
			'wednesday' => 'Wo',
			'thursday'  => 'Do',
			'friday'    => 'Vr',
			'saturday'  => 'Za',
			'sunday'    => 'Zo',
		];
		return $labels[ $en ] ?? ucfirst( $en );
	}

	/**
	 * @param array<int, string> $days
	 */
	private static function day_range_label( array $days ): string {
		$order = [
			'monday'    => 1,
			'tuesday'   => 2,
			'wednesday' => 3,
			'thursday'  => 4,
			'friday'    => 5,
			'saturday'  => 6,
			'sunday'    => 7,
		];

		$days = array_values( array_unique( array_filter( $days, static fn( $day ) => isset( $order[ $day ] ) ) ) );
		usort( $days, static fn( $a, $b ) => $order[ $a ] <=> $order[ $b ] );

		$ranges = [];
		$start = null;
		$previous = null;
		foreach ( $days as $day ) {
			if ( $start === null ) {
				$start = $day;
				$previous = $day;
				continue;
			}

			if ( $order[ $day ] === $order[ $previous ] + 1 ) {
				$previous = $day;
				continue;
			}

			$ranges[] = $start === $previous ? self::day_short_label( $start ) : self::day_short_label( $start ) . '-' . self::day_short_label( $previous );
			$start = $day;
			$previous = $day;
		}

		if ( $start !== null && $previous !== null ) {
			$ranges[] = $start === $previous ? self::day_short_label( $start ) : self::day_short_label( $start ) . '-' . self::day_short_label( $previous );
		}

		return implode( ', ', $ranges );
	}

	public static function render_news_mixed_grid(): string {
		$cards = [];

		foreach ( ZuidwestImporter::get_news_cache() as $item ) {
			$ts = strtotime( (string) ( $item['published_at'] ?? '' ) );
			$url = (string) ( $item['source_url'] ?? '' );
			if ( $url === '' ) {
				continue;
			}

			$cards[] = [
				'title'     => (string) ( $item['title'] ?? '' ),
				'url'       => $url,
				'image'     => (string) ( $item['image_url'] ?? '' ),
				'published' => (string) ( $item['published_at'] ?? '' ),
				'pretty'    => $ts ? wp_date( 'j F Y', $ts ) : '',
				'region'    => (string) ( $item['region_label'] ?? '' ),
				'external'  => true,
			];
		}

		usort( $cards, static fn( $a, $b ) => strcmp( (string) $b['published'], (string) $a['published'] ) );
		$cards = array_slice( $cards, 0, 6 );

		ob_start();
		?>
		<section class="bg-bg-app py-16">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<?php echo self::section_head(
					__( 'Lokaal nieuws', 'radio-rucphen' ),
					__( 'De zes meest recente berichten van Zuidwest Update voor onze regio.', 'radio-rucphen' ),
					__( 'Alle nieuws', 'radio-rucphen' ),
					home_url( '/nieuws/' )
				); ?>
				<div class="grid grid-cols-3 gap-x-[1.4rem] gap-y-8 max-[767px]:grid-cols-1">
					<?php
					$idx = 0;
					foreach ( $cards as $card ) :
						$is_lead = $idx === 0;
						$ext     = $card['external'] ? ' target="_blank" rel="noopener nofollow"' : '';
						?>
						<article>
							<a class="grid h-full gap-[0.65rem] rounded-card border border-[#dce6f2] bg-white pb-[0.9rem] text-brand no-underline" href="<?php echo esc_url( $card['url'] ); ?>"<?php echo $ext; ?>>
								<?php if ( $card['image'] !== '' ) : ?>
									<img class="aspect-[16/9] w-full rounded-t-card bg-[#eef3f8] object-cover" src="<?php echo esc_url( $card['image'] ); ?>" loading="<?php echo $is_lead ? 'eager' : 'lazy'; ?>" alt="">
								<?php endif; ?>
								<div class="grid grid-rows-[auto_minmax(3.9rem,auto)_auto] gap-[0.42rem] px-[0.85rem]">
									<?php if ( $card['region'] !== '' ) : ?>
										<span class="inline-flex w-fit items-center rounded-full bg-accent px-[0.58rem] py-[0.22rem] text-[0.72rem] font-extrabold uppercase text-brand"><?php echo esc_html( $card['region'] ); ?></span>
									<?php endif; ?>
									<strong class="line-clamp-3 font-display text-[1.02rem] font-[850] leading-[1.22] text-ink"><?php echo esc_html( $card['title'] ); ?></strong>
									<span class="text-[0.8rem] font-extrabold text-[#64748b]">
										<?php echo esc_html( $card['pretty'] ); ?>
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

	public static function render_news_archive(): string {
		$cards = [];

		foreach ( ZuidwestImporter::get_news_cache() as $item ) {
			$ts = strtotime( (string) ( $item['published_at'] ?? '' ) );
			$url = (string) ( $item['source_url'] ?? '' );
			if ( $url === '' ) {
				continue;
			}

			$cards[] = [
				'title'     => (string) ( $item['title'] ?? '' ),
				'url'       => $url,
				'image'     => (string) ( $item['image_url'] ?? '' ),
				'excerpt'   => (string) ( $item['excerpt'] ?? '' ),
				'published' => (string) ( $item['published_at'] ?? '' ),
				'pretty'    => $ts ? wp_date( 'j F Y', $ts ) : '',
				'badge'     => (string) ( $item['region_label'] ?? '' ),
				'source'    => 'external',
				'external'  => true,
			];
		}

		$local = new \WP_Query(
			[
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 12,
				'meta_key'       => '_rucphen_news_source',
				'meta_value'     => 'redactie',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			]
		);
		foreach ( $local->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$image = has_post_thumbnail( $post )
				? (string) get_the_post_thumbnail_url( $post, 'rucphen-card' )
				: (string) get_post_meta( $post->ID, '_rucphen_news_cover', true );

			$cards[] = [
				'title'     => get_the_title( $post ),
				'url'       => get_permalink( $post ),
				'image'     => $image,
				'excerpt'   => get_the_excerpt( $post ),
				'published' => get_post_time( 'c', true, $post ),
				'pretty'    => get_the_date( 'j F Y', $post ),
				'badge'     => __( 'Redactie', 'radio-rucphen' ),
				'source'    => 'redactie',
				'external'  => false,
			];
		}

		usort( $cards, static fn( $a, $b ) => strcmp( (string) $b['published'], (string) $a['published'] ) );
		$cards = array_slice( $cards, 0, 12 );

		ob_start();
		?>
		<section class="relative isolate grid min-h-[320px] items-end overflow-hidden bg-brand-dark py-16 text-white">
			<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="<?php echo esc_url( self::theme_img( 'nieuws/2026-05-nieuwe-site.jpg' ) ); ?>" alt="">
			<span class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(15_23_42_/_0.88),rgb(0_53_118_/_0.64)_58%,rgb(15_23_42_/_0.42))]" aria-hidden="true"></span>
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="max-w-[760px]">
					<p class="mb-[0.8rem] inline-flex items-center gap-[0.45rem] text-[0.88rem] font-black uppercase text-[#bfdbfe]"><?php esc_html_e( 'Lokaal nieuws', 'radio-rucphen' ); ?></p>
					<h1 class="m-0 font-display text-[clamp(2.2rem,2rem_+_1.6vw,4rem)] font-extrabold leading-[1.08]"><?php esc_html_e( 'Nieuws', 'radio-rucphen' ); ?></h1>
					<p class="mt-4 max-w-[720px] text-[1.18rem] text-[#e2e8f0]"><?php esc_html_e( 'Nieuws van de redactie en gelabelde verwijzingen naar Zuidwest Update.', 'radio-rucphen' ); ?></p>
				</div>
			</div>
		</section>

		<section class="bg-bg-app py-16" data-component="news-archive">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<nav class="mb-6 flex flex-wrap gap-2" aria-label="<?php esc_attr_e( 'Filter nieuws', 'radio-rucphen' ); ?>">
					<button class="min-h-11 rounded-full border border-line bg-white px-[0.8rem] py-[0.45rem] font-black text-ink transition aria-pressed:border-brand aria-pressed:bg-brand aria-pressed:text-white hover:border-[#bfdbfe]" type="button" data-news-filter="all" aria-pressed="true"><?php esc_html_e( 'Alles', 'radio-rucphen' ); ?></button>
					<button class="min-h-11 rounded-full border border-line bg-white px-[0.8rem] py-[0.45rem] font-black text-ink transition aria-pressed:border-brand aria-pressed:bg-brand aria-pressed:text-white hover:border-[#bfdbfe]" type="button" data-news-filter="redactie" aria-pressed="false"><?php esc_html_e( 'Redactie', 'radio-rucphen' ); ?></button>
					<button class="min-h-11 rounded-full border border-line bg-white px-[0.8rem] py-[0.45rem] font-black text-ink transition aria-pressed:border-brand aria-pressed:bg-brand aria-pressed:text-white hover:border-[#bfdbfe]" type="button" data-news-filter="external" aria-pressed="false"><?php esc_html_e( 'Zuidwest Update', 'radio-rucphen' ); ?></button>
				</nav>
				<div class="grid grid-cols-3 gap-x-[1.4rem] gap-y-8 max-[767px]:grid-cols-1">
					<?php foreach ( $cards as $idx => $card ) :
						$ext = $card['external'] ? ' target="_blank" rel="noopener nofollow"' : '';
						?>
						<article data-news-card data-news-source="<?php echo esc_attr( (string) $card['source'] ); ?>">
							<a class="grid h-full gap-[0.65rem] rounded-card border border-[#dce6f2] bg-white pb-[0.9rem] text-brand no-underline" href="<?php echo esc_url( (string) $card['url'] ); ?>"<?php echo $ext; ?>>
								<?php if ( $card['image'] !== '' ) : ?>
									<img class="aspect-[16/9] w-full rounded-t-card bg-[#eef3f8] object-cover" src="<?php echo esc_url( (string) $card['image'] ); ?>" loading="<?php echo $idx < 3 ? 'eager' : 'lazy'; ?>" alt="">
								<?php endif; ?>
								<span class="grid grid-rows-[auto_auto_minmax(4.1rem,auto)_auto] gap-[0.42rem] px-[0.85rem]">
									<?php if ( $card['badge'] !== '' ) : ?>
										<span class="inline-flex w-fit items-center rounded-full bg-accent px-[0.58rem] py-[0.22rem] text-[0.72rem] font-extrabold uppercase text-brand"><?php echo esc_html( (string) $card['badge'] ); ?></span>
									<?php endif; ?>
									<span class="text-[0.8rem] font-extrabold text-[#64748b]"><?php echo esc_html( (string) $card['pretty'] ); ?></span>
									<strong class="line-clamp-3 font-display text-[1.02rem] font-[850] leading-[1.22] text-ink"><?php echo esc_html( (string) $card['title'] ); ?></strong>
									<span class="line-clamp-3 text-[0.92rem] text-ink-soft"><?php echo esc_html( (string) $card['excerpt'] ); ?></span>
								</span>
							</a>
						</article>
					<?php endforeach; ?>
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
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
					<div>
						<h2 class="font-display text-3xl font-extrabold leading-tight md:text-4xl"><?php esc_html_e( 'Video\'s uit de regio', 'radio-rucphen' ); ?></h2>
						<p class="mt-1 text-white/75"><?php esc_html_e( 'Actuele beelden uit Etten-Leur, Halderberge, Roosendaal, Rucphen en Zundert.', 'radio-rucphen' ); ?></p>
					</div>
					<a class="inline-flex w-fit items-center gap-2 rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-bold no-underline hover:bg-white/15" href="<?php echo esc_url( home_url( '/video/' ) ); ?>"><?php esc_html_e( 'Alle video\'s', 'radio-rucphen' ); ?></a>
				</div>
				<div class="grid gap-4 md:grid-cols-3">
					<?php
					$idx = 0;
					foreach ( $videos as $video ) :
						$is_large = $idx === 0;
						$link     = (string) ( $video['video_embed_url'] ?? $video['source_url'] ?? '' );
						$ts       = strtotime( (string) ( $video['published_at'] ?? '' ) );
						$pretty   = $ts ? wp_date( 'j F Y', $ts ) : '';
						$meta     = trim( (string) ( $video['region_label'] ?? '' ) . ( $pretty !== '' ? ' &middot; ' . $pretty : '' ) );
						?>
						<article class="<?php echo $is_large ? 'md:col-span-2 md:row-span-2 ' : ''; ?>group relative overflow-hidden rounded-card bg-white/5 shadow-md transition hover:bg-white/10">
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

	public static function render_video_archive(): string {
		$videos = ZuidwestImporter::get_videos_cache();

		ob_start();
		?>
		<section class="relative isolate grid min-h-[320px] items-end overflow-hidden bg-brand-dark py-16 text-white">
			<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="<?php echo esc_url( self::theme_img( 'video/regionaal-evenement.jpg' ) ); ?>" alt="">
			<span class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(15_23_42_/_0.88),rgb(0_53_118_/_0.64)_58%,rgb(15_23_42_/_0.42))]" aria-hidden="true"></span>
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="max-w-[760px]">
					<p class="mb-[0.8rem] inline-flex items-center gap-[0.45rem] text-[0.88rem] font-black uppercase text-[#bfdbfe]"><?php esc_html_e( 'Regiovideo', 'radio-rucphen' ); ?></p>
					<h1 class="m-0 font-display text-[clamp(2.2rem,2rem_+_1.6vw,4rem)] font-extrabold leading-[1.08]"><?php esc_html_e( 'Video\'s uit de regio', 'radio-rucphen' ); ?></h1>
					<p class="mt-4 max-w-[720px] text-[1.18rem] text-[#e2e8f0]"><?php esc_html_e( 'Actuele video\'s uit Rucphen en omliggende gemeenten, met duidelijke regio-aanduiding per kaart.', 'radio-rucphen' ); ?></p>
				</div>
			</div>
		</section>

		<section class="bg-bg-app py-16">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="grid grid-cols-3 gap-x-[1.4rem] gap-y-8 max-[767px]:grid-cols-1">
					<?php foreach ( $videos as $idx => $video ) :
						$link = (string) ( $video['video_embed_url'] ?? $video['source_url'] ?? '' );
						$ts = strtotime( (string) ( $video['published_at'] ?? '' ) );
						$pretty = $ts ? wp_date( 'j F Y', $ts ) : '';
						$meta_parts = array_filter( [ (string) ( $video['region_label'] ?? '' ), $pretty ] );
						?>
						<article>
							<a class="grid h-full overflow-hidden rounded-card border border-[#dce6f2] bg-white text-ink no-underline shadow-sm" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener nofollow">
								<span class="relative block aspect-[16/9] overflow-hidden bg-[#eef3f8]">
									<?php if ( ! empty( $video['image_url'] ) ) : ?>
										<img class="h-full w-full object-cover transition hover:scale-[1.035]" src="<?php echo esc_url( (string) $video['image_url'] ); ?>" loading="<?php echo $idx < 3 ? 'eager' : 'lazy'; ?>" alt="">
									<?php endif; ?>
									<span class="absolute inset-0 grid place-items-center bg-black/15">
										<span class="grid size-12 place-items-center rounded-full bg-accent text-brand shadow-md">
											<svg class="size-5 translate-x-px" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
										</span>
									</span>
								</span>
								<span class="grid gap-2 p-4">
									<?php if ( $meta_parts !== [] ) : ?>
										<span class="text-[0.78rem] font-extrabold uppercase text-[#64748b]"><?php echo esc_html( implode( ' · ', $meta_parts ) ); ?></span>
									<?php endif; ?>
									<strong class="font-display text-[1.08rem] font-extrabold leading-[1.16] text-ink"><?php echo esc_html( (string) ( $video['title'] ?? '' ) ); ?></strong>
									<?php if ( ! empty( $video['excerpt'] ) ) : ?>
										<span class="line-clamp-3 text-[0.92rem] text-ink-soft"><?php echo esc_html( (string) $video['excerpt'] ); ?></span>
									<?php endif; ?>
								</span>
							</a>
						</article>
					<?php endforeach; ?>
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
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
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

	public static function render_events_archive(): string {
		$now = current_datetime();
		$query = new \WP_Query(
			[
				'post_type'      => PostTypes::EVENT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => [ [ 'key' => '_rucphen_event_start', 'value' => $now->format( 'c' ), 'compare' => '>=', 'type' => 'DATETIME' ] ],
				'orderby'        => 'meta_value',
				'meta_key'       => '_rucphen_event_start',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			]
		);

		$months = [];
		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$ts = strtotime( (string) get_post_meta( $post->ID, '_rucphen_event_start', true ) );
			if ( $ts !== false ) {
				$months[ wp_date( 'Y-m', $ts ) ] = wp_date( 'F Y', $ts );
			}
		}

		ob_start();
		?>
		<section class="relative isolate grid min-h-[320px] items-end overflow-hidden bg-brand-dark py-16 text-white">
			<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="<?php echo esc_url( self::theme_img( 'events/rucphen-open-dag.jpg' ) ); ?>" alt="">
			<span class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(15_23_42_/_0.88),rgb(0_53_118_/_0.64)_58%,rgb(15_23_42_/_0.42))]" aria-hidden="true"></span>
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="max-w-[760px]">
					<p class="mb-[0.8rem] inline-flex items-center gap-[0.45rem] text-[0.88rem] font-black uppercase text-[#bfdbfe]"><?php esc_html_e( 'Acties', 'radio-rucphen' ); ?></p>
					<h1 class="m-0 font-display text-[clamp(2.2rem,2rem_+_1.6vw,4rem)] font-extrabold leading-[1.08]"><?php esc_html_e( 'Lokale agenda', 'radio-rucphen' ); ?></h1>
					<p class="mt-4 max-w-[720px] text-[1.18rem] text-[#e2e8f0]"><?php esc_html_e( 'Activiteiten in Rucphen en omgeving, met kalenderlinks per evenement.', 'radio-rucphen' ); ?></p>
				</div>
			</div>
		</section>

		<section class="bg-bg-app py-16" data-component="events-archive">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<nav class="mb-6 flex flex-wrap gap-2" aria-label="<?php esc_attr_e( 'Filter agenda op maand', 'radio-rucphen' ); ?>">
					<button class="min-h-11 rounded-full border border-line bg-white px-[0.8rem] py-[0.45rem] font-black text-ink transition aria-pressed:border-brand aria-pressed:bg-brand aria-pressed:text-white hover:border-[#bfdbfe]" type="button" data-event-filter="all" aria-pressed="true"><?php esc_html_e( 'Alle', 'radio-rucphen' ); ?></button>
					<?php foreach ( $months as $month => $label ) : ?>
						<button class="min-h-11 rounded-full border border-line bg-white px-[0.8rem] py-[0.45rem] font-black text-ink transition aria-pressed:border-brand aria-pressed:bg-brand aria-pressed:text-white hover:border-[#bfdbfe]" type="button" data-event-filter="<?php echo esc_attr( $month ); ?>" aria-pressed="false"><?php echo esc_html( strtolower( $label ) ); ?></button>
					<?php endforeach; ?>
				</nav>
				<div class="grid gap-4">
					<?php foreach ( $query->posts as $post ) :
						if ( ! $post instanceof \WP_Post ) {
							continue;
						}
						$start_iso = (string) get_post_meta( $post->ID, '_rucphen_event_start', true );
						$location  = (string) get_post_meta( $post->ID, '_rucphen_event_location', true );
						$url       = (string) get_post_meta( $post->ID, '_rucphen_event_url', true );
						$ts        = $start_iso !== '' ? strtotime( $start_iso ) : false;
						$month     = $ts !== false ? wp_date( 'Y-m', $ts ) : '';
						$more_attr = self::is_external_url( $url ) ? ' target="_blank" rel="noopener"' : '';
						?>
						<article class="rounded-card border border-line bg-white shadow-sm" data-event-card data-event-month="<?php echo esc_attr( $month ); ?>">
							<div class="grid grid-cols-[78px_minmax(0,1fr)] items-start gap-4 p-5 max-[767px]:grid-cols-1">
								<?php if ( $ts !== false ) : ?>
									<div class="grid w-[70px] place-items-center rounded-sm bg-brand p-3 text-center text-white">
										<span class="text-xs font-black uppercase"><?php echo esc_html( strtolower( wp_date( 'M', $ts ) ) ); ?></span>
										<strong class="font-display text-[2rem] font-extrabold leading-none"><?php echo esc_html( wp_date( 'j', $ts ) ); ?></strong>
									</div>
								<?php endif; ?>
								<div>
									<h2 class="font-display text-[1.28rem] font-extrabold leading-[1.12] text-ink"><?php echo esc_html( get_the_title( $post ) ); ?></h2>
									<p class="mt-1 text-sm font-bold text-ink-soft">
										<?php if ( $ts !== false ) : ?><?php echo esc_html( wp_date( 'j F Y', $ts ) ); ?><?php endif; ?>
										<?php if ( $location !== '' ) : ?> &middot; <?php echo esc_html( $location ); ?><?php endif; ?>
									</p>
									<p class="mt-2 text-ink-soft"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $post ) ?: $post->post_content ) ); ?></p>
									<div class="mt-4 flex flex-wrap gap-2">
										<a class="inline-flex min-h-10 items-center rounded-sm border border-[#c9d7ec] bg-white px-3 py-2 text-sm font-extrabold text-brand no-underline hover:bg-[#e9eef7]" href="<?php echo esc_url( self::event_ics_url( $post ) ); ?>"><?php esc_html_e( 'Voeg toe aan kalender', 'radio-rucphen' ); ?></a>
										<?php if ( $url !== '' ) : ?>
											<a class="inline-flex min-h-10 items-center rounded-sm border border-[#c9d7ec] bg-white px-3 py-2 text-sm font-extrabold text-brand no-underline hover:bg-[#e9eef7]" href="<?php echo esc_url( $url ); ?>"<?php echo $more_attr; ?>><?php esc_html_e( 'Meer informatie', 'radio-rucphen' ); ?></a>
										<?php endif; ?>
									</div>
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

	private static function event_ics_url( \WP_Post $event ): string {
		$slug = preg_replace( '/-\d{4}-\d{2}-\d{2}$/', '', $event->post_name );
		$slug = is_string( $slug ) && $slug !== '' ? $slug : $event->post_name;
		return home_url( '/static-source/agenda/' . $slug . '.ics' );
	}

	public static function render_frequency_grid(): string {
		$f = Settings::get( Settings::OPTION_FREQUENCIES );

		ob_start();
		?>
		<section class="bg-surface py-16">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
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

	public static function render_frequency_page(): string {
		$f = Settings::get( Settings::OPTION_FREQUENCIES );
		$fm = trim( (string) ( $f['fm_mhz'] ?? '' ) );
		$dab = str_replace( ',', ' /', (string) ( $f['dab_blocks'] ?? '' ) );
		$cable_provider = (string) ( $f['cable_provider'] ?? '' );
		$cable_channel = (string) ( $f['cable_channel'] ?? '' );
		$coverage = (string) ( $f['coverage'] ?? '' );

		ob_start();
		?>
		<section class="relative isolate grid min-h-[320px] items-end overflow-hidden bg-brand-dark py-16 text-white">
			<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="<?php echo esc_url( self::theme_img( 'programs/non-stop-muziek.jpg' ) ); ?>" alt="">
			<span class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(15_23_42_/_0.88),rgb(0_53_118_/_0.64)_58%,rgb(15_23_42_/_0.42))]" aria-hidden="true"></span>
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="max-w-[760px]">
					<p class="mb-[0.8rem] inline-flex items-center gap-[0.45rem] text-[0.88rem] font-black uppercase text-[#bfdbfe]"><?php esc_html_e( 'Frequenties', 'radio-rucphen' ); ?></p>
					<h1 class="m-0 font-display text-[clamp(2.2rem,2rem_+_1.6vw,4rem)] font-extrabold leading-[1.08]"><?php esc_html_e( 'Zo ontvang je Radio Rucphen', 'radio-rucphen' ); ?></h1>
					<p class="mt-4 max-w-[720px] text-[1.18rem] text-[#e2e8f0]"><?php esc_html_e( 'Luister via FM, DAB+, kabel, deze website en slimme speakers.', 'radio-rucphen' ); ?></p>
				</div>
			</div>
		</section>

		<section class="bg-bg-app py-16">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="grid grid-cols-4 gap-5 max-[1023px]:grid-cols-2 max-[767px]:grid-cols-1">
					<article class="rounded-card border border-[#dce6f2] bg-white p-5 shadow-sm">
						<h2 class="font-display text-[1.2rem] font-extrabold text-ink">FM</h2>
						<p class="mt-2 font-display text-[2rem] font-extrabold leading-none text-brand"><?php echo esc_html( $fm ); ?></p>
						<p class="mt-2 text-ink-soft"><?php echo esc_html( sprintf( __( 'MHz in %s.', 'radio-rucphen' ), $coverage ) ); ?></p>
					</article>
					<article class="rounded-card border border-[#dce6f2] bg-white p-5 shadow-sm">
						<h2 class="font-display text-[1.2rem] font-extrabold text-ink">DAB+</h2>
						<p class="mt-2 font-display text-[2rem] font-extrabold leading-none text-brand"><?php echo esc_html( $dab ); ?></p>
						<p class="mt-2 text-ink-soft"><?php echo esc_html( sprintf( __( 'Kanalen voor %s.', 'radio-rucphen' ), $coverage ) ); ?></p>
					</article>
					<article class="rounded-card border border-[#dce6f2] bg-white p-5 shadow-sm">
						<h2 class="font-display text-[1.2rem] font-extrabold text-ink"><?php esc_html_e( 'Kabel', 'radio-rucphen' ); ?></h2>
						<p class="mt-2 font-display text-[2rem] font-extrabold leading-none text-brand"><?php echo esc_html( $cable_channel ); ?></p>
						<p class="mt-2 text-ink-soft"><?php echo esc_html( trim( $cable_provider . ' digitaal lokaal. Kanaalnummer volgt.' ) ); ?></p>
					</article>
					<article class="rounded-card border border-[#dce6f2] bg-white p-5 shadow-sm">
						<h2 class="font-display text-[1.2rem] font-extrabold text-ink"><?php esc_html_e( 'Online', 'radio-rucphen' ); ?></h2>
						<p class="mt-2 font-display text-[2rem] font-extrabold leading-none text-brand"><?php esc_html_e( 'Live', 'radio-rucphen' ); ?></p>
						<p class="mt-2 text-ink-soft"><?php esc_html_e( 'Via deze website, Radioplayer NL en slimme speakers.', 'radio-rucphen' ); ?></p>
					</article>
				</div>
			</div>
		</section>

		<section class="bg-surface py-16">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?> grid grid-cols-2 gap-8 max-[767px]:grid-cols-1">
				<div>
					<h2 class="font-display text-[2rem] font-extrabold leading-[1.08] text-ink"><?php esc_html_e( 'Online ook via', 'radio-rucphen' ); ?></h2>
					<ul class="mt-4 grid gap-2 text-ink-soft">
						<li><?php esc_html_e( 'Radioplayer NL, zodra de koppeling definitief is.', 'radio-rucphen' ); ?></li>
						<li><?php esc_html_e( 'TuneIn, zodra de stationpagina actief is.', 'radio-rucphen' ); ?></li>
						<li><?php esc_html_e( 'Slimme speaker: "Alexa, speel Radio Rucphen op TuneIn".', 'radio-rucphen' ); ?></li>
						<li><?php esc_html_e( 'Google Nest: "Hey Google, speel Radio Rucphen".', 'radio-rucphen' ); ?></li>
					</ul>
				</div>
				<div>
					<h2 class="font-display text-[2rem] font-extrabold leading-[1.08] text-ink"><?php esc_html_e( 'In de auto', 'radio-rucphen' ); ?></h2>
					<p class="mt-4 text-ink-soft"><?php esc_html_e( 'Kies bij moderne autoradio\'s voor DAB+ en scan opnieuw als Radio Rucphen niet direct zichtbaar is. In oudere auto\'s kun je afstemmen op 106.4 MHz.', 'radio-rucphen' ); ?></p>
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
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
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

	public static function render_contact_page(): string {
		$contact = Settings::get( Settings::OPTION_CONTACT );
		$studio_email = (string) ( $contact['email_studio'] ?? '' );
		$redactie_email = (string) ( $contact['email_redactie'] ?? '' );
		$number = preg_replace( '/\D+/', '', (string) ( $contact['whatsapp_number'] ?? '' ) );
		$text = rawurlencode( (string) ( $contact['whatsapp_default_text'] ?? '' ) );
		$wa_url = 'https://wa.me/' . $number . '?text=' . $text;

		ob_start();
		?>
		<section class="relative isolate grid min-h-[320px] items-end overflow-hidden bg-brand-dark py-16 text-white">
			<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="<?php echo esc_url( self::theme_img( 'programs/wakker-met-rucphen.jpg' ) ); ?>" alt="">
			<span class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(15_23_42_/_0.88),rgb(0_53_118_/_0.64)_58%,rgb(15_23_42_/_0.42))]" aria-hidden="true"></span>
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="max-w-[760px]">
					<p class="mb-[0.8rem] inline-flex items-center gap-[0.45rem] text-[0.88rem] font-black uppercase text-[#bfdbfe]"><?php esc_html_e( 'Studio en redactie', 'radio-rucphen' ); ?></p>
					<h1 class="m-0 font-display text-[clamp(2.2rem,2rem_+_1.6vw,4rem)] font-extrabold leading-[1.08]"><?php esc_html_e( 'Contact', 'radio-rucphen' ); ?></h1>
					<p class="mt-4 max-w-[720px] text-[1.18rem] text-[#e2e8f0]"><?php esc_html_e( 'Voor verzoekjes gebruik je WhatsApp. Voor redactie en algemene vragen kun je mailen.', 'radio-rucphen' ); ?></p>
				</div>
			</div>
		</section>

		<section class="bg-bg-app py-16">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?> grid grid-cols-2 gap-5 max-[767px]:grid-cols-1">
				<article class="rounded-card border border-[#dce6f2] bg-white p-6 shadow-sm">
					<h2 class="font-display text-[1.55rem] font-extrabold leading-[1.08] text-ink"><?php esc_html_e( 'Studio', 'radio-rucphen' ); ?></h2>
					<?php if ( $studio_email !== '' ) : ?>
						<p class="mt-4 text-ink-soft"><?php esc_html_e( 'E-mail:', 'radio-rucphen' ); ?> <a class="font-bold text-brand no-underline hover:underline" href="mailto:<?php echo esc_attr( $studio_email ); ?>"><?php echo esc_html( $studio_email ); ?></a></p>
					<?php endif; ?>
					<p class="mt-3 text-ink-soft"><?php esc_html_e( 'Postadres: Postadres volgt.', 'radio-rucphen' ); ?></p>
					<p class="mt-3 text-ink-soft"><?php esc_html_e( 'Er staat bewust geen studio-telefoonnummer op de site. Verzoekjes lopen via WhatsApp en e-mail.', 'radio-rucphen' ); ?></p>
					<h2 class="mt-6 font-display text-[1.55rem] font-extrabold leading-[1.08] text-ink"><?php esc_html_e( 'Redactie', 'radio-rucphen' ); ?></h2>
					<?php if ( $redactie_email !== '' ) : ?>
						<p class="mt-4 text-ink-soft"><?php esc_html_e( 'E-mail:', 'radio-rucphen' ); ?> <a class="font-bold text-brand no-underline hover:underline" href="mailto:<?php echo esc_attr( $redactie_email ); ?>"><?php echo esc_html( $redactie_email ); ?></a></p>
					<?php endif; ?>
				</article>
				<article class="rounded-card border border-[#dce6f2] bg-white p-6 shadow-sm">
					<h2 class="font-display text-[1.55rem] font-extrabold leading-[1.08] text-ink"><?php esc_html_e( 'Verzoekje via WhatsApp', 'radio-rucphen' ); ?></h2>
					<p class="mt-4 text-ink-soft"><?php esc_html_e( 'Je opent hiermee WhatsApp. Wij delen je nummer alleen met de presentator van dat moment.', 'radio-rucphen' ); ?></p>
					<a class="mt-5 inline-flex min-h-11 items-center rounded-sm bg-brand px-4 py-2 font-extrabold text-white no-underline hover:bg-brand-dark" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open WhatsApp', 'radio-rucphen' ); ?></a>
				</article>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_about_page(): string {
		$org = Settings::get( Settings::OPTION_ORGANIZATION );
		$kvk = (string) ( $org['kvk'] ?? 'TBD' );
		$rsin = (string) ( $org['rsin'] ?? 'TBD' );
		$iban = (string) ( $org['iban'] ?? 'TBD' );

		ob_start();
		?>
		<section class="relative isolate grid min-h-[320px] items-end overflow-hidden bg-brand-dark py-16 text-white">
			<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="<?php echo esc_url( self::theme_img( 'programs/avond-hits.jpg' ) ); ?>" alt="">
			<span class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(15_23_42_/_0.88),rgb(0_53_118_/_0.64)_58%,rgb(15_23_42_/_0.42))]" aria-hidden="true"></span>
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="max-w-[760px]">
					<p class="mb-[0.8rem] inline-flex items-center gap-[0.45rem] text-[0.88rem] font-black uppercase text-[#bfdbfe]"><?php esc_html_e( 'Stichting Rucphen RTV', 'radio-rucphen' ); ?></p>
					<h1 class="m-0 font-display text-[clamp(2.2rem,2rem_+_1.6vw,4rem)] font-extrabold leading-[1.08]"><?php esc_html_e( 'Over Radio Rucphen', 'radio-rucphen' ); ?></h1>
					<p class="mt-4 max-w-[720px] text-[1.18rem] text-[#e2e8f0]"><?php esc_html_e( 'Lokale radio, gemaakt door vrijwilligers en gericht op de gemeente Rucphen.', 'radio-rucphen' ); ?></p>
				</div>
			</div>
		</section>

		<section class="bg-bg-app py-16">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?> grid grid-cols-2 gap-8 max-[767px]:grid-cols-1">
				<div class="text-ink">
					<h2 class="font-display text-[2rem] font-extrabold leading-[1.08]"><?php esc_html_e( 'Missie', 'radio-rucphen' ); ?></h2>
					<p class="mt-4 text-ink-soft"><?php esc_html_e( 'Radio Rucphen wil dichtbij, herkenbaar en betrouwbaar zijn. We brengen lokale informatie, muziek en verhalen voor luisteraars in de gemeente Rucphen en de directe regio.', 'radio-rucphen' ); ?></p>
					<h2 class="mt-8 font-display text-[2rem] font-extrabold leading-[1.08]"><?php esc_html_e( 'Geschiedenis', 'radio-rucphen' ); ?></h2>
					<p class="mt-4 text-ink-soft"><?php esc_html_e( 'De radio-tak valt onder Stichting Rucphen RTV. Deze nieuwe site richt zich volledig op radio en vervangt de gemengde radio- en televisiepresentatie.', 'radio-rucphen' ); ?></p>
				</div>
				<div class="text-ink">
					<h2 class="font-display text-[2rem] font-extrabold leading-[1.08]"><?php esc_html_e( 'Vrijwilliger worden', 'radio-rucphen' ); ?></h2>
					<p class="mt-4 text-ink-soft"><?php esc_html_e( 'We zoeken mensen voor techniek, presentatie, productie, redactie en ondersteuning. Ervaring is welkom, maar enthousiasme en betrokkenheid bij de regio zijn belangrijker.', 'radio-rucphen' ); ?></p>
					<a class="mt-5 inline-flex min-h-11 items-center rounded-sm bg-brand px-4 py-2 font-extrabold text-white no-underline hover:bg-brand-dark" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Neem contact op', 'radio-rucphen' ); ?></a>
				</div>
			</div>
		</section>

		<section class="bg-surface py-16">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<h2 class="font-display text-[2rem] font-extrabold leading-[1.08] text-ink"><?php esc_html_e( 'Bestuur', 'radio-rucphen' ); ?></h2>
				<div class="mt-5 grid grid-cols-3 gap-5 max-[767px]:grid-cols-1">
					<?php
					$roles = [ __( 'Voorzitter', 'radio-rucphen' ), __( 'Secretaris', 'radio-rucphen' ), __( 'Penningmeester', 'radio-rucphen' ) ];
					foreach ( $roles as $idx => $role ) :
						?>
						<article class="overflow-hidden rounded-card border border-[#dce6f2] bg-white shadow-sm">
							<img class="aspect-square w-full object-cover" src="<?php echo esc_url( self::theme_img( 'over-ons/bestuur-' . ( $idx + 1 ) . '.jpg' ) ); ?>" loading="lazy" alt="">
							<div class="p-4">
								<h3 class="font-display text-[1.18rem] font-extrabold text-ink"><?php esc_html_e( 'Naam Bestuurder', 'radio-rucphen' ); ?></h3>
								<p class="mt-1 text-ink-soft"><?php echo esc_html( $role ); ?></p>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="bg-bg-app py-16">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<h2 class="font-display text-[2rem] font-extrabold leading-[1.08] text-ink"><?php esc_html_e( 'ANBI-gegevens', 'radio-rucphen' ); ?></h2>
				<div class="mt-5 grid grid-cols-3 gap-5 max-[767px]:grid-cols-1">
					<?php foreach ( [ 'KvK' => $kvk, 'RSIN' => $rsin, 'IBAN' => $iban ] as $label => $value ) : ?>
						<article class="rounded-card border border-[#dce6f2] bg-white p-5 shadow-sm">
							<h3 class="font-display text-[1.18rem] font-extrabold text-ink"><?php echo esc_html( $label ); ?></h3>
							<p class="mt-2 text-ink-soft"><?php echo esc_html( $value ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
				<p class="mt-5 text-sm font-bold text-ink-soft"><?php esc_html_e( 'Definitieve ANBI-documenten en het jaarverslag volgen zodra de opdrachtgever de gegevens aanlevert.', 'radio-rucphen' ); ?></p>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_legal_page(): string {
		$page = get_queried_object();
		$slug = $page instanceof \WP_Post ? $page->post_name : '';
		$org = Settings::get( Settings::OPTION_ORGANIZATION );
		$contact = Settings::get( Settings::OPTION_CONTACT );

		$pages = [
			'privacy' => [
				'title' => __( 'Privacyverklaring', 'radio-rucphen' ),
				'intro' => __( 'Privacyverklaring van Radio Rucphen. Concepttekst. Definitieve juridische tekst wordt door de opdrachtgever aangeleverd en kan in deze pagina worden geplaatst.', 'radio-rucphen' ),
				'sections' => [
					[
						'title' => __( 'Welke gegevens verwerken wij?', 'radio-rucphen' ),
						'body'  => __( 'Deze site gebruikt geen analytics en geen trackingcookies. De live-player bewaart alleen het gekozen volume lokaal in je browser.', 'radio-rucphen' ),
					],
					[
						'title' => __( 'Contact', 'radio-rucphen' ),
						'body'  => sprintf( __( 'Mail privacyvragen naar de redactie via de contactpagina of via %s.', 'radio-rucphen' ), (string) ( $contact['email_redactie'] ?? 'redactie@radiorucphen.nl' ) ),
					],
				],
			],
			'cookies' => [
				'title' => __( 'Cookiebeleid', 'radio-rucphen' ),
				'intro' => __( 'Cookiebeleid van Radio Rucphen. Concepttekst. Definitieve juridische tekst wordt door de opdrachtgever aangeleverd en kan in deze pagina worden geplaatst.', 'radio-rucphen' ),
				'sections' => [
					[
						'title' => __( 'Geen trackingcookies', 'radio-rucphen' ),
						'body'  => __( 'Radio Rucphen plaatst geen advertentie- of trackingcookies. De volume-instelling wordt functioneel bewaard in localStorage.', 'radio-rucphen' ),
					],
				],
			],
			'disclaimer' => [
				'title' => __( 'Disclaimer', 'radio-rucphen' ),
				'intro' => __( 'Disclaimer en voorwaarden van Radio Rucphen. Concepttekst. Definitieve juridische tekst wordt door de opdrachtgever aangeleverd en kan in deze pagina worden geplaatst.', 'radio-rucphen' ),
				'sections' => [
					[
						'title' => __( 'Externe links', 'radio-rucphen' ),
						'body'  => __( 'Nieuws- en videokaarten van Zuidwest Update linken naar de bron. Radio Rucphen is niet verantwoordelijk voor inhoud op externe websites.', 'radio-rucphen' ),
					],
					[
						'title' => __( 'Auteursrecht', 'radio-rucphen' ),
						'body'  => __( 'Eigen teksten en beelden blijven eigendom van Radio Rucphen of de genoemde rechthebbenden.', 'radio-rucphen' ),
					],
				],
			],
			'colofon' => [
				'title' => __( 'Colofon', 'radio-rucphen' ),
				'intro' => __( 'Colofon en ANBI-informatie van Radio Rucphen. Concepttekst. Definitieve juridische tekst wordt door de opdrachtgever aangeleverd en kan in deze pagina worden geplaatst.', 'radio-rucphen' ),
				'sections' => [
					[
						'title' => __( 'Organisatie', 'radio-rucphen' ),
						'body'  => sprintf(
							"%s\nKvK: %s\nRSIN: %s\nIBAN: %s",
							(string) ( $org['legal_name'] ?? 'Stichting Rucphen RTV' ),
							(string) ( $org['kvk'] ?? 'TBD' ) ?: 'TBD',
							(string) ( $org['rsin'] ?? 'TBD' ) ?: 'TBD',
							(string) ( $org['iban'] ?? 'TBD' ) ?: 'TBD'
						),
					],
					[
						'title' => __( 'Contact', 'radio-rucphen' ),
						'body'  => sprintf(
							"Studio: %s\nRedactie: %s",
							(string) ( $contact['email_studio'] ?? 'studio@radiorucphen.nl' ),
							(string) ( $contact['email_redactie'] ?? 'redactie@radiorucphen.nl' )
						),
					],
				],
			],
		];

		$data = $pages[ $slug ] ?? $pages['privacy'];
		$intro_parts = explode( ' Concepttekst.', (string) $data['intro'], 2 );
		$lead = trim( $intro_parts[0] );
		if ( $lead !== '' && ! str_ends_with( $lead, '.' ) ) {
			$lead .= '.';
		}

		ob_start();
		?>
		<section class="relative isolate grid min-h-[320px] items-end overflow-hidden bg-brand-dark py-16 text-white">
			<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="<?php echo esc_url( self::theme_img( 'nieuws/2026-05-nieuwe-site.jpg' ) ); ?>" alt="">
			<span class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(15_23_42_/_0.88),rgb(0_53_118_/_0.64)_58%,rgb(15_23_42_/_0.42))]" aria-hidden="true"></span>
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
				<div class="max-w-[760px]">
					<p class="mb-[0.8rem] inline-flex items-center gap-[0.45rem] text-[0.88rem] font-black uppercase text-[#bfdbfe]"><?php esc_html_e( 'Juridisch', 'radio-rucphen' ); ?></p>
					<h1 class="m-0 font-display text-[clamp(2.2rem,2rem_+_1.6vw,4rem)] font-extrabold leading-[1.08]"><?php echo esc_html( $data['title'] ); ?></h1>
					<?php if ( $lead !== '' ) : ?>
						<p class="mt-4 max-w-[720px] text-[1.18rem] text-[#e2e8f0]"><?php echo esc_html( $lead ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<section class="bg-bg-app py-12">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?> max-w-[820px]">
				<div class="border-l-4 border-accent bg-[#fff8df] p-4 text-ink">
					<strong><?php esc_html_e( 'Concepttekst.', 'radio-rucphen' ); ?></strong>
					<?php esc_html_e( 'Definitieve juridische tekst wordt door de opdrachtgever aangeleverd en kan in deze pagina worden geplaatst.', 'radio-rucphen' ); ?>
				</div>
				<div class="mt-8 grid gap-8">
					<?php foreach ( $data['sections'] as $section ) : ?>
						<section>
							<h2 class="font-display text-[2rem] font-extrabold leading-tight text-ink"><?php echo esc_html( $section['title'] ); ?></h2>
							<div class="mt-3 grid gap-1 text-ink">
								<?php foreach ( explode( "\n", (string) $section['body'] ) as $line ) : ?>
									<p><?php echo esc_html( $line ); ?></p>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_newsletter_page(): string {
		$news = Settings::get( Settings::OPTION_NEWSLETTER );
		$href = self::whatsapp_url( (string) ( $news['fallback_whatsapp_text'] ?? 'Zet mij op de nieuwsbrief-lijst' ) );

		ob_start();
		?>
		<section class="bg-bg-app py-12">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?> max-w-[900px]">
				<p class="mb-[0.8rem] inline-flex items-center rounded-full bg-accent px-[0.72rem] py-[0.28rem] text-[0.78rem] font-black uppercase text-brand"><?php esc_html_e( 'Blijf op de hoogte', 'radio-rucphen' ); ?></p>
				<h1 class="m-0 font-display text-[clamp(2.2rem,2rem_+_1.6vw,4rem)] font-extrabold leading-[1.08] text-ink"><?php esc_html_e( 'Nieuwsbrief', 'radio-rucphen' ); ?></h1>
				<p class="mt-4 max-w-[760px] text-[1.12rem] leading-7 text-ink-soft"><?php esc_html_e( 'De nieuwsbrief komt binnenkort. Voor MVP loopt aanmelden via WhatsApp.', 'radio-rucphen' ); ?></p>
				<div class="mt-8 rounded-card border border-line bg-white p-6 shadow-sm">
					<h2 class="font-display text-2xl font-extrabold leading-tight text-ink"><?php esc_html_e( 'Aanmelden', 'radio-rucphen' ); ?></h2>
					<p class="mt-3 text-ink-soft"><?php esc_html_e( 'Onze nieuwsbrief komt binnenkort. Stuur ons een appje als je alvast op de lijst wilt.', 'radio-rucphen' ); ?></p>
					<a class="mt-5 inline-flex min-h-11 items-center rounded-sm bg-brand px-4 py-2 font-extrabold text-white no-underline hover:bg-brand-dark" href="<?php echo esc_url( $href ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Schrijf je in voor de nieuwsbrief', 'radio-rucphen' ); ?></a>
					<p class="mt-4 text-sm font-bold text-ink-soft"><?php esc_html_e( 'Er worden voor deze placeholder geen tracking-pixels of externe nieuwsbriefscripts geladen.', 'radio-rucphen' ); ?></p>
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
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
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
				'meta_key'       => '_rucphen_program_default_start',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			]
		);

		ob_start();
		?>
		<section class="bg-surface py-16">
			<div class="<?php echo esc_attr( self::CONTAINER ); ?>">
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
