<?php
/**
 * REST endpoint voor Zuidwest Update-artikelen in de modal.
 *
 * Per klik wordt de originele artikelpagina opgehaald, zodat Zuidwest Update
 * een hit op de bron-URL krijgt. De response bevat daarna alleen een gestript
 * artikeldocument voor een same-origin iframe.
 *
 * @package RadioRucphen
 */

declare(strict_types=1);

namespace RadioRucphen;

defined( 'ABSPATH' ) || exit;

final class ZuidwestArticle {

	public const NAMESPACE = 'radio-rucphen/v1';
	public const ROUTE     = '/zuidwest-article';

	public static function register(): void {
		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
	}

	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
				'callback'            => [ self::class, 'handle' ],
			]
		);
	}

	public static function handle( \WP_REST_Request $request ) {
		$id = trim( (string) $request->get_param( 'id' ) );
		if ( $id === '' ) {
			return new \WP_Error( 'rucphen_zwu_article_missing_id', __( 'Geen artikel gekozen.', 'radio-rucphen' ), [ 'status' => 400 ] );
		}

		$item = self::find_news_item( $id );
		if ( $item === null ) {
			return new \WP_Error( 'rucphen_zwu_article_unknown', __( 'Artikel niet gevonden in de Zuidwest Update-cache.', 'radio-rucphen' ), [ 'status' => 404 ] );
		}

		$source_url = isset( $item['source_url'] ) ? esc_url_raw( (string) $item['source_url'] ) : '';
		if ( ! self::is_allowed_source_url( $source_url ) ) {
			return new \WP_Error( 'rucphen_zwu_article_invalid_url', __( 'Ongeldige bron-URL.', 'radio-rucphen' ), [ 'status' => 400 ] );
		}

		$response = wp_remote_get(
			$source_url,
			[
				'timeout'     => 12,
				'redirection' => 3,
				'headers'     => [
					'Accept' => 'text/html,application/xhtml+xml',
				],
				'user-agent'  => 'RadioRucphenTheme/' . RUCPHEN_THEME_VERSION . '; ' . home_url( '/' ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'rucphen_zwu_article_fetch_failed', $response->get_error_message(), [ 'status' => 502 ] );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error( 'rucphen_zwu_article_http_error', sprintf( 'Zuidwest Update gaf HTTP %d terug.', $code ), [ 'status' => 502 ] );
		}

		$html = wp_remote_retrieve_body( $response );
		if ( ! is_string( $html ) || trim( $html ) === '' ) {
			return new \WP_Error( 'rucphen_zwu_article_empty', __( 'Zuidwest Update gaf geen artikelinhoud terug.', 'radio-rucphen' ), [ 'status' => 502 ] );
		}

		$article = self::extract_article( $html, $item, $source_url );

		return rest_ensure_response(
			[
				'id'        => $id,
				'title'     => $article['title'],
				'sourceUrl' => $source_url,
				'html'      => self::build_srcdoc( $article, $source_url ),
			]
		);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function find_news_item( string $id ): ?array {
		foreach ( ZuidwestImporter::get_news_cache() as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( (string) ( $item['source_id'] ?? '' ) === $id ) {
				return $item;
			}
		}

		return null;
	}

	private static function is_allowed_source_url( string $url ): bool {
		if ( $url === '' ) {
			return false;
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		$host   = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! is_string( $scheme ) || ! in_array( strtolower( $scheme ), [ 'http', 'https' ], true ) ) {
			return false;
		}

		if ( ! is_string( $host ) ) {
			return false;
		}

		return in_array( strtolower( $host ), [ 'zuidwestupdate.nl', 'www.zuidwestupdate.nl' ], true );
	}

	/**
	 * @param array<string, mixed> $item
	 * @return array{title:string,meta:string,image:string,caption:string,content:string}
	 */
	private static function extract_article( string $html, array $item, string $source_url ): array {
		$fallback = [
			'title'   => (string) ( $item['title'] ?? __( 'Zuidwest Update', 'radio-rucphen' ) ),
			'meta'    => self::meta_label_from_item( $item ),
			'image'   => (string) ( $item['image_url'] ?? '' ),
			'caption' => '',
			'content' => wpautop( esc_html( (string) ( $item['excerpt'] ?? '' ) ) ),
		];

		if ( ! class_exists( '\DOMDocument' ) || ! class_exists( '\DOMXPath' ) ) {
			return $fallback;
		}

		$previous = libxml_use_internal_errors( true );
		$document = new \DOMDocument();
		$loaded   = $document->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			return $fallback;
		}

		$xpath   = new \DOMXPath( $document );
		$article = $xpath->query( '//article' )->item( 0 );
		if ( ! $article instanceof \DOMNode ) {
			return $fallback;
		}

		self::remove_nodes(
			$xpath,
			$article,
			'.//script | .//style | .//noscript | .//nav | .//aside | .//*[contains(@id, "zw-staart")] | .//*[contains(@class, "sharedaddy")]'
		);

		$title = self::text_from_first( $xpath, './/h1', $article );
		$title = $title !== '' ? $title : $fallback['title'];

		$author = self::text_from_first( $xpath, './/a[contains(@href, "/author/")]', $article );
		$date   = self::text_from_first( $xpath, './/time', $article );
		if ( $date === '' ) {
			$timestamp = strtotime( (string) ( $item['published_at'] ?? '' ) );
			$date = $timestamp ? wp_date( 'j F Y', $timestamp ) : '';
		}
		$meta = trim( implode( ' | ', array_filter( [ (string) ( $item['region_label'] ?? '' ), $date, $author ] ) ) );

		$image_data = self::hero_image( $xpath, $article, $fallback['image'] );
		$content    = $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " zw-prose ")]', $article )->item( 0 );
		if ( ! $content instanceof \DOMNode ) {
			$content = $article;
		}

		self::remove_nodes( $xpath, $content, './/script | .//style | .//noscript | .//nav | .//aside | .//*[contains(@id, "zw-staart")]' );
		$content_html = self::sanitize_content( self::inner_html( $document, $content ) );

		if ( trim( wp_strip_all_tags( $content_html ) ) === '' ) {
			$content_html = $fallback['content'];
		}

		return [
			'title'   => $title,
			'meta'    => $meta !== '' ? $meta : $fallback['meta'],
			'image'   => $image_data['src'],
			'caption' => $image_data['caption'],
			'content' => $content_html,
		];
	}

	/**
	 * @param array<string, mixed> $item
	 */
	private static function meta_label_from_item( array $item ): string {
		$parts = [];
		if ( ! empty( $item['region_label'] ) ) {
			$parts[] = (string) $item['region_label'];
		}

		$timestamp = strtotime( (string) ( $item['published_at'] ?? '' ) );
		if ( $timestamp ) {
			$parts[] = wp_date( 'j F Y', $timestamp );
		}

		return implode( ' | ', $parts );
	}

	private static function remove_nodes( \DOMXPath $xpath, \DOMNode $context, string $query ): void {
		$nodes = $xpath->query( $query, $context );
		if ( ! $nodes instanceof \DOMNodeList ) {
			return;
		}

		for ( $i = $nodes->length - 1; $i >= 0; $i-- ) {
			$node = $nodes->item( $i );
			if ( $node instanceof \DOMNode && $node->parentNode instanceof \DOMNode ) {
				$node->parentNode->removeChild( $node );
			}
		}
	}

	private static function text_from_first( \DOMXPath $xpath, string $query, \DOMNode $context ): string {
		$node = $xpath->query( $query, $context )->item( 0 );
		if ( ! $node instanceof \DOMNode ) {
			return '';
		}

		return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $node->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ?? '' );
	}

	/**
	 * @return array{src:string,caption:string}
	 */
	private static function hero_image( \DOMXPath $xpath, \DOMNode $article, string $fallback ): array {
		$images = $xpath->query( './/img', $article );
		if ( ! $images instanceof \DOMNodeList ) {
			return [ 'src' => $fallback, 'caption' => '' ];
		}

		foreach ( $images as $image ) {
			if ( ! $image instanceof \DOMElement ) {
				continue;
			}

			$class = (string) $image->getAttribute( 'class' );
			$width = (int) $image->getAttribute( 'width' );
			if ( str_contains( $class, 'h-8' ) || ( $width > 0 && $width < 200 ) ) {
				continue;
			}

			$src = esc_url_raw( (string) $image->getAttribute( 'src' ) );
			if ( $src === '' ) {
				continue;
			}

			$caption = '';
			$container = $image->parentNode instanceof \DOMNode ? $image->parentNode : null;
			if ( $container instanceof \DOMNode ) {
				$caption = self::text_from_first( $xpath, './p | ./figcaption', $container );
			}

			return [ 'src' => $src, 'caption' => $caption ];
		}

		return [ 'src' => $fallback, 'caption' => '' ];
	}

	private static function inner_html( \DOMDocument $document, \DOMNode $node ): string {
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $document->saveHTML( $child );
		}

		return $html;
	}

	private static function sanitize_content( string $html ): string {
		$allowed = wp_kses_allowed_html( 'post' );

		foreach ( [ 'div', 'p', 'span', 'strong', 'em', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'figure', 'figcaption' ] as $tag ) {
			if ( ! isset( $allowed[ $tag ] ) ) {
				$allowed[ $tag ] = [];
			}
			$allowed[ $tag ]['class'] = true;
		}

		if ( isset( $allowed['a'] ) ) {
			$allowed['a']['target'] = true;
			$allowed['a']['rel']    = true;
			$allowed['a']['class']  = true;
		}

		if ( isset( $allowed['img'] ) ) {
			$allowed['img']['srcset']  = true;
			$allowed['img']['sizes']   = true;
			$allowed['img']['loading'] = true;
			$allowed['img']['decoding'] = true;
		}

		return wp_kses( $html, $allowed );
	}

	/**
	 * @param array{title:string,meta:string,image:string,caption:string,content:string} $article
	 */
	private static function build_srcdoc( array $article, string $source_url ): string {
		$origin = 'https://www.zuidwestupdate.nl/';
		$scheme = wp_parse_url( $source_url, PHP_URL_SCHEME );
		$host   = wp_parse_url( $source_url, PHP_URL_HOST );
		if ( is_string( $scheme ) && is_string( $host ) ) {
			$origin = $scheme . '://' . $host . '/';
		}

		$image = $article['image'] !== ''
			? sprintf(
				'<figure class="hero"><img src="%s" alt="">%s</figure>',
				esc_url( $article['image'] ),
				$article['caption'] !== '' ? '<figcaption>' . esc_html( $article['caption'] ) . '</figcaption>' : ''
			)
			: '';

		return '<!doctype html><html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="' .
			esc_url( $origin ) .
			'" target="_blank"><style>' .
			self::srcdoc_css() .
			'</style><title>' . esc_html( $article['title'] ) . '</title></head><body><article><p class="source">Zuidwest Update</p><h1>' .
			esc_html( $article['title'] ) .
			'</h1><p class="meta">' .
			esc_html( $article['meta'] ) .
			'</p>' .
			$image .
			'<div class="content">' .
			$article['content'] .
			'</div><p class="origin"><a href="' .
			esc_url( $source_url ) .
			'" rel="noopener nofollow">Lees op Zuidwest Update</a></p></article></body></html>';
	}

	private static function srcdoc_css(): string {
		return '*,*::before,*::after{box-sizing:border-box}body{margin:0;background:#fff;color:#0f172a;font-family:Inter,"Helvetica Neue",Arial,sans-serif;line-height:1.65}article{max-width:780px;margin:0 auto;padding:28px clamp(18px,4vw,42px) 44px}.source{margin:0 0 10px;color:#003576;font-size:.78rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em}h1{margin:0;color:#0f172a;font-size:clamp(2rem,1.65rem + 1.4vw,3.2rem);line-height:1.07;font-weight:900}h2{margin:2rem 0 .75rem;color:#003576;font-size:1.55rem;line-height:1.16}h3{margin:1.5rem 0 .65rem;color:#003576;font-size:1.25rem;line-height:1.2}.meta{margin:.85rem 0 1.5rem;color:#64748b;font-size:.88rem;font-weight:800;text-transform:uppercase}.hero{margin:0 0 1.6rem}.hero img,.content img{display:block;width:100%;height:auto;border-radius:8px;background:#eef3f8}.hero figcaption,figcaption{margin-top:.5rem;color:#64748b;font-size:.86rem;text-align:center}.content p{margin:0 0 1.05rem}.content a{color:#003576;font-weight:800}.content blockquote,.content .wp-block-pullquote{position:relative;margin:2rem 0;padding:1.4rem 1.45rem 1.35rem 4.4rem;border:0;border-radius:8px;background:#003576;color:#fff;box-shadow:inset 0 0 0 1px rgb(255 255 255 / .08),0 14px 34px rgb(15 23 42 / .16)}.content blockquote::before,.content .wp-block-pullquote::before{content:"\201C";position:absolute;left:1.25rem;top:.65rem;color:#ffde00;font-size:4.25rem;font-weight:900;line-height:1}.content blockquote p,.content .wp-block-pullquote p{margin:0;color:inherit;font-size:clamp(1.12rem,1rem + .55vw,1.48rem);font-weight:800;line-height:1.35}.content blockquote cite,.content .wp-block-pullquote cite{display:block;margin-top:.8rem;color:#bfdbfe;font-size:.9rem;font-style:normal;font-weight:800}.content ul,.content ol{padding-left:1.35rem}.kader-content{margin:1.4rem 0;padding:1.05rem;border-left:5px solid #16a34a;border-radius:8px;background:#f0fdf4;color:#164e2a}.origin{margin:2rem 0 0;padding-top:1rem;border-top:1px solid #e2e8f0}.origin a{display:inline-flex;min-height:42px;align-items:center;border-radius:6px;background:#003576;color:#fff;padding:.65rem .9rem;font-weight:900;text-decoration:none}@media(max-width:640px){article{padding:22px 16px 34px}h1{font-size:2rem}.content blockquote,.content .wp-block-pullquote{padding:1.2rem 1.1rem 1.15rem 3.55rem}.content blockquote::before,.content .wp-block-pullquote::before{left:.95rem;font-size:3.5rem}}';
	}
}
