<?php
/**
 * Plugin bootstrap: wires every component to WordPress.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the plugin and wires its components to WordPress.
 */
final class Plugin {

	/**
	 * Called on plugins_loaded.
	 */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ), 0 );

		Frontend::register();
		Analytics\Store::register();
		Analytics\Rest::register();
		Llms::register();
		Block::register();
		Integrations\Elementor::register();

		if ( is_admin() ) {
			Admin\Admin::register();
			Admin\MetaBox::register();
			Admin\Tools::register();
		}
	}

	/**
	 * Load translations.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'summarize-with-ai', false, dirname( plugin_basename( SWAI_FILE ) ) . '/languages' );
	}

	/**
	 * Absolute URL of a file inside assets/.
	 *
	 * @param string $path Path relative to assets/.
	 */
	public static function asset( $path ) {
		return SWAI_URL . 'assets/' . ltrim( $path, '/' );
	}
}
