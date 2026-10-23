<?php
/**
 * Registers the "Summarize with AI" block from blocks/summarize/block.json.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Summarize with AI block.
 */
final class Block {

	/**
	 * Hook the component into WordPress.
	 */
	public static function register() {
		add_action( 'init', array( __CLASS__, 'register_block' ) );
	}

	/**
	 * Register the block type and pass editor data.
	 */
	public static function register_block() {
		register_block_type( SWAI_DIR . 'blocks/summarize' );

		$options = array();
		foreach ( Services::all() as $id => $svc ) {
			$options[] = array(
				'value' => $id,
				'label' => $svc['name'],
			);
		}
		$modes = array();
		foreach ( Services::active_modes() as $id => $m ) {
			$modes[] = array(
				'value' => $id,
				'label' => $m['label'],
			);
		}
		wp_add_inline_script(
			'swai-summarize-editor-script',
			'window.swaiBlock=' . wp_json_encode(
				array(
					'services' => $options,
					'modes'    => $modes,
					'enabled'  => array_values( (array) Settings::get( 'enabled' ) ),
				)
			) . ';',
			'before'
		);
		wp_set_script_translations( 'swai-summarize-editor-script', 'summarize-with-ai', SWAI_DIR . 'languages' );
	}

	/**
	 * Server-side render callback (blocks/summarize/render.php).
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes ) {
		$args = array( 'context' => 'block' );
		if ( ! empty( $attributes['services'] ) ) {
			$args['services'] = array_map( 'sanitize_key', (array) $attributes['services'] );
		}
		if ( isset( $attributes['label'] ) && null !== $attributes['label'] ) {
			$args['label'] = sanitize_text_field( $attributes['label'] );
		}
		foreach ( array( 'template', 'theme', 'mode' ) as $key ) {
			if ( ! empty( $attributes[ $key ] ) ) {
				$args[ $key ] = sanitize_key( $attributes[ $key ] );
			}
		}
		if ( isset( $attributes['showModes'] ) ) {
			$args['show_modes'] = (bool) $attributes['showModes'];
		}
		$post_id = get_the_ID();
		if ( ! $post_id && isset( $_GET['post_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Editor preview only.
			$post_id = absint( $_GET['post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$args['post_id'] = (int) $post_id;

		$html = Renderer::render( $args );
		if ( '' === $html ) {
			return '';
		}
		return sprintf( '<div %s>%s</div>', get_block_wrapper_attributes(), $html );
	}
}
