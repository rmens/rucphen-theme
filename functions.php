<?php
/**
 * Radio Rucphen theme bootstrap.
 *
 * Het theme is bewust modulair opgezet zonder plugins. Alle logica leeft in
 * losse classes onder inc/ en wordt hier alleen ingeladen en gestart.
 *
 * @package RadioRucphen
 */

defined( 'ABSPATH' ) || exit;

define( 'RUCPHEN_THEME_VERSION', '0.1.0' );
define( 'RUCPHEN_THEME_DIR', trailingslashit( get_template_directory() ) );
define( 'RUCPHEN_THEME_URI', trailingslashit( get_template_directory_uri() ) );

require_once RUCPHEN_THEME_DIR . 'inc/class-autoload.php';

\RadioRucphen\Autoload::register();

add_action(
	'after_setup_theme',
	static function (): void {
		\RadioRucphen\Setup::register();
		\RadioRucphen\Assets::register();
		\RadioRucphen\PostTypes::register();
		\RadioRucphen\Taxonomies::register();
		\RadioRucphen\Meta::register();
		\RadioRucphen\Settings::register();
		\RadioRucphen\ZuidwestImporter::register();
		\RadioRucphen\ZuidwestArticle::register();
		\RadioRucphen\NowPlaying::register();
		\RadioRucphen\SeoCompat::register();
		\RadioRucphen\IconRegistry::register();
		\RadioRucphen\RestSearch::register();
		\RadioRucphen\Blocks::register();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\RadioRucphen\CliImport::register();
		}
	},
	5
);
