<?php
/**
 * Plugin Name:       Summarize with AI
 * Plugin URI:        https://github.com/Alireza-Fakhari/summarize-with-ai
 * Description:       Let readers summarize any post with ChatGPT, Perplexity, Claude, Gemini and more in one click. Prompt modes, privacy-first analytics, llms.txt, floating button, Gutenberg block and Elementor widget.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Alireza Fakhari
 * Author URI:        https://github.com/Alireza-Fakhari
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       summarize-with-ai
 * Domain Path:       /languages
 *
 * @package SummarizeWithAI
 */

defined( 'ABSPATH' ) || exit;

define( 'SWAI_VERSION', '1.0.0' );
define( 'SWAI_FILE', __FILE__ );
define( 'SWAI_DIR', plugin_dir_path( __FILE__ ) );
define( 'SWAI_URL', plugin_dir_url( __FILE__ ) );

/*
 * PSR-4 autoloader for the SummarizeWithAI\ namespace (src/).
 * Kept dependency-free so the plugin works without running Composer.
 */
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SummarizeWithAI\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SWAI_DIR . 'src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'SummarizeWithAI\\Analytics\\Store', 'install' ) );

add_action( 'plugins_loaded', array( 'SummarizeWithAI\\Plugin', 'boot' ) );

/**
 * Render the buttons anywhere in a theme: <?php echo swai_render(); ?>
 *
 * @param array $args See SummarizeWithAI\Renderer::render().
 * @return string
 */
function swai_render( $args = array() ) {
	return \SummarizeWithAI\Renderer::render( $args );
}
