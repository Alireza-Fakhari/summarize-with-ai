<?php
/**
 * Elementor integration (loaded only when Elementor is active).
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI\Integrations;

defined( 'ABSPATH' ) || exit;

final class Elementor {

	public static function register() {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'widgets' ) );
	}

	public static function widgets( $manager ) {
		$manager->register( new ElementorWidget() );
	}
}
