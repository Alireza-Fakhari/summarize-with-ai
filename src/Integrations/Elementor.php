<?php
/**
 * Elementor integration (loaded only when Elementor is active).
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor integration loader.
 */
final class Elementor {

	/**
	 * Hook the component into WordPress.
	 */
	public static function register() {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'widgets' ) );
	}

	/**
	 * Register the Elementor widget.
	 *
	 * @param \Elementor\Widgets_Manager $manager Widgets manager.
	 */
	public static function widgets( $manager ) {
		$manager->register( new ElementorWidget() );
	}
}
