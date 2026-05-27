/**
 * Editor-side registratie van Radio Rucphen dynamic blocks.
 *
 * Elke block heeft een server-side render callback in PHP. Hier registreren we
 * een client-side edit/save zodat de Gutenberg editor weet hoe ze te tonen.
 * De edit-functie gebruikt ServerSideRender om de echte PHP-output als preview
 * te laten zien in de editor.
 */

( function ( wp ) {
	if ( ! wp || ! wp.blocks ) {
		return;
	}

	var el = wp.element.createElement;
	var ServerSideRender = wp.serverSideRender;
	var BlockControls = wp.blockEditor && wp.blockEditor.BlockControls;
	var InnerBlocks = wp.blockEditor && wp.blockEditor.InnerBlocks;
	var templateOnlyBlocks = {
		'rucphen/template-main': true,
		'rucphen/page-hero': true,
		'rucphen/breadcrumbs': true,
		'rucphen/hero-text': true
	};

	var blocks = [
		{ name: 'rucphen/template-main',       title: 'Template main',                     category: 'theme', inner: true, attributes: { contained: { type: 'boolean', default: false } } },
		{ name: 'rucphen/page-hero',           title: 'Pagina hero',                       category: 'theme', inner: true, hero: true, attributes: { fallbackImage: { type: 'string', default: '' } } },
		{ name: 'rucphen/breadcrumbs',         title: 'Broodkruimels',                     category: 'theme', attributes: { variant: { type: 'string', default: 'hero' } } },
		{ name: 'rucphen/hero-text',           title: 'Hero-tekst',                        category: 'theme', attributes: { fallbackText: { type: 'string', default: '' } } },
		{ name: 'rucphen/site-header',         title: 'Site header',                       category: 'theme' },
		{ name: 'rucphen/site-footer',         title: 'Site footer',                       category: 'theme' },
		{ name: 'rucphen/live-hero',           title: 'Live hero',                         category: 'radio-rucphen' },
		{ name: 'rucphen/sticky-player',       title: 'Sticky player',                     category: 'radio-rucphen' },
		{ name: 'rucphen/program-archive',     title: 'Programmagids',                     category: 'radio-rucphen' },
		{ name: 'rucphen/program-single',      title: 'Programma detail',                  category: 'radio-rucphen' },
		{ name: 'rucphen/presenter-archive',   title: "DJ's en presentatoren",             category: 'radio-rucphen' },
		{ name: 'rucphen/presenter-single',    title: 'Presentator detail',                category: 'radio-rucphen' },
		{ name: 'rucphen/featured-programs',   title: "Uitgelichte programma's",           category: 'radio-rucphen' },
		{ name: 'rucphen/recent-podcasts',     title: 'Gemiste uitzendingen',              category: 'radio-rucphen' },
		{ name: 'rucphen/podcast-archive',     title: 'Gemist archief',                    category: 'radio-rucphen' },
		{ name: 'rucphen/podcast-single',      title: 'Gemiste uitzending',                category: 'radio-rucphen' },
		{ name: 'rucphen/news-mixed-grid',     title: 'Lokaal nieuws',                     category: 'radio-rucphen' },
		{ name: 'rucphen/news-archive',        title: 'Nieuws archief',                    category: 'radio-rucphen' },
		{ name: 'rucphen/video-grid',          title: "Video's uit de regio",              category: 'radio-rucphen' },
		{ name: 'rucphen/video-archive',       title: 'Video archief',                     category: 'radio-rucphen' },
		{ name: 'rucphen/events-grid',         title: 'Agenda',                            category: 'radio-rucphen' },
		{ name: 'rucphen/events-archive',      title: 'Agenda archief',                    category: 'radio-rucphen' },
		{ name: 'rucphen/frequency-grid',      title: 'Frequenties',                       category: 'radio-rucphen' },
		{ name: 'rucphen/frequency-options',   title: 'Luisteropties',                     category: 'radio-rucphen' },
		{ name: 'rucphen/whatsapp-cta',        title: 'WhatsApp verzoekje CTA',            category: 'radio-rucphen' },
		{ name: 'rucphen/contact-details',     title: 'Contactgegevens',                   category: 'radio-rucphen' },
		{ name: 'rucphen/about-story',         title: 'Over ons verhaal',                  category: 'radio-rucphen' },
		{ name: 'rucphen/about-board',         title: 'Bestuur',                           category: 'radio-rucphen' },
		{ name: 'rucphen/about-anbi',          title: 'ANBI gegevens',                     category: 'radio-rucphen' },
		{ name: 'rucphen/legal-content',       title: 'Juridische inhoud',                 category: 'radio-rucphen' },
		{ name: 'rucphen/newsletter-signup',   title: 'Nieuwsbrief aanmelden',             category: 'radio-rucphen' },
		{ name: 'rucphen/newsletter-cta',      title: 'Nieuwsbrief CTA',                   category: 'radio-rucphen' },
		{ name: 'rucphen/program-quick-links', title: 'Snelle programma-links',            category: 'radio-rucphen' }
	];

	blocks.forEach( function ( b ) {
		if ( b.inner ) {
			wp.blocks.registerBlockType( b.name, {
				apiVersion: 3,
				title:      b.title,
				category:   b.category,
				icon:       'layout',
				description: 'Radio Rucphen: ' + b.title,
				attributes: b.attributes || {},
				supports: { html: false, align: false, customClassName: false, inserter: ! templateOnlyBlocks[ b.name ] },
				edit: function () {
					var blockTemplate = b.hero ? [
						[ 'rucphen/breadcrumbs' ],
						[ 'core/post-title', { level: 1 } ],
						[ 'rucphen/hero-text' ]
					] : undefined;
					return el(
						b.hero ? 'section' : 'main',
						{
							className: b.hero ? 'rucphen-page-hero-editor' : 'rucphen-template-main-editor',
							style: {
								padding: b.hero ? '2rem' : '1rem',
								border: '1px dashed #cbd5e1',
								borderRadius: '8px',
								background: b.hero ? '#003576' : undefined,
								color: b.hero ? '#fff' : undefined
							}
						},
						InnerBlocks ? el( InnerBlocks, { template: blockTemplate } ) : b.title
					);
				},
				save: function () {
					return InnerBlocks ? el( InnerBlocks.Content ) : null;
				}
			} );
			return;
		}

		wp.blocks.registerBlockType( b.name, {
			apiVersion: 3,
			title:      b.title,
			category:   b.category,
			icon:       'microphone',
			description: 'Radio Rucphen: ' + b.title,
			attributes: b.attributes || {},
			supports:   { html: false, align: false, customClassName: false, inserter: ! templateOnlyBlocks[ b.name ] },
			edit: function ( props ) {
				if ( ! ServerSideRender ) {
					return el(
						'div',
						{ className: 'rucphen-block-placeholder', style: { padding: '1rem', border: '1px dashed #cbd5e1', borderRadius: '8px' } },
						b.title
					);
				}
				return el(
					'div',
					{ className: 'rucphen-block-ssr' },
					el( ServerSideRender, {
						block: b.name,
						attributes: props.attributes || {},
						EmptyResponsePlaceholder: function () {
							return el(
								'div',
								{ style: { padding: '1rem', color: '#64748b' } },
								b.title + ': geen inhoud beschikbaar in preview.'
							);
						}
					} )
				);
			},
			save: function () { return null; }
		} );
	} );
} )( window.wp );
