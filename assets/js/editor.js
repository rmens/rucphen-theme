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

	var blocks = [
		{ name: 'rucphen/template-main',       title: 'Template main',                     category: 'theme', inner: true },
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
		{ name: 'rucphen/frequency-page',      title: 'Frequenties pagina',                category: 'radio-rucphen' },
		{ name: 'rucphen/whatsapp-cta',        title: 'WhatsApp verzoekje CTA',            category: 'radio-rucphen' },
		{ name: 'rucphen/contact-page',        title: 'Contact pagina',                    category: 'radio-rucphen' },
		{ name: 'rucphen/about-page',          title: 'Over ons pagina',                   category: 'radio-rucphen' },
		{ name: 'rucphen/legal-page',          title: 'Juridische pagina',                 category: 'radio-rucphen' },
		{ name: 'rucphen/newsletter-page',     title: 'Nieuwsbrief pagina',                category: 'radio-rucphen' },
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
				attributes: {
					contained: {
						type: 'boolean',
						default: false
					}
				},
				supports: { html: false, align: false, customClassName: false },
				edit: function () {
					return el(
						'main',
						{ className: 'rucphen-template-main-editor', style: { padding: '1rem', border: '1px dashed #cbd5e1', borderRadius: '8px' } },
						InnerBlocks ? el( InnerBlocks ) : b.title
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
			supports:   { html: false, align: false, customClassName: false },
			edit: function () {
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
