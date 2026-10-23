<?php
/**
 * Front-end output: auto-insertion, shortcode, floating button and assets.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI;

defined( 'ABSPATH' ) || exit;

final class Frontend {

	public static function register() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_filter( 'the_content', array( __CLASS__, 'filter_content' ), 20 );
		add_action( 'wp_footer', array( __CLASS__, 'floating' ) );
		add_shortcode( 'summarize_with_ai', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Register (not enqueue) assets. They are enqueued only on pages that
	 * actually render the box, so the rest of the site pays nothing.
	 */
	public static function register_assets() {
		wp_register_style( 'swai', Plugin::asset( 'css/frontend.css' ), array(), SWAI_VERSION );
		wp_style_add_data( 'swai', 'path', SWAI_DIR . 'assets/css/frontend.css' );

		wp_register_script(
			'swai',
			Plugin::asset( 'js/frontend.js' ),
			array(),
			SWAI_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		$s = Settings::get();
		wp_add_inline_script(
			'swai',
			'window.swaiConfig=' . wp_json_encode(
				array(
					'endpoint'   => $s['analytics'] ? esc_url_raw( rest_url( 'swai/v1/event' ) ) : '',
					'trackViews' => (bool) ( $s['analytics'] && $s['track_views'] ),
					'utm'        => $s['utm'] ? $s['utm_source'] : '',
					'i18n'       => array(
						/* translators: %s: AI service name. */
						'copiedFor' => __( 'Prompt copied. Paste it in %s.', 'summarize-with-ai' ),
						'copied'    => __( 'Prompt copied to clipboard.', 'summarize-with-ai' ),
						'copyFail'  => __( 'Could not copy. Please copy the prompt manually.', 'summarize-with-ai' ),
					),
				)
			) . ';',
			'before'
		);

		// Pre-enqueue on singular views that will show the box, so CSS lands in <head>.
		if ( is_singular() && self::should_auto_insert( get_queried_object_id() ) ) {
			Renderer::enqueue();
		}
	}

	/**
	 * Whether the box should be inserted automatically for a post.
	 */
	public static function should_auto_insert( $post_id ) {
		$s = Settings::get();
		if ( ! $post_id || 'none' === $s['position'] ) {
			return false;
		}
		return self::eligible( $post_id ) && ! ( $s['skip_elementor'] && self::built_with_elementor( $post_id ) );
	}

	/**
	 * Post-level rules shared by auto-insertion and the floating button.
	 */
	public static function eligible( $post_id ) {
		$s    = Settings::get();
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status || post_password_required( $post ) ) {
			return false;
		}
		if ( ! in_array( $post->post_type, (array) $s['post_types'], true ) || get_post_meta( $post_id, '_swai_disable', true ) ) {
			return false;
		}
		if ( $s['exclude_cats'] && has_category( $s['exclude_cats'], $post ) ) {
			return false;
		}
		if ( $s['min_words'] > 0 && self::word_count( $post->post_content ) < $s['min_words'] ) {
			return false;
		}

		/**
		 * Final say on whether a post shows the buttons.
		 *
		 * @param bool     $eligible Default decision.
		 * @param \WP_Post $post     Post object.
		 */
		return (bool) apply_filters( 'swai_is_eligible', true, $post );
	}

	/**
	 * Unicode-aware word count (str_word_count() ignores non-Latin scripts).
	 */
	private static function word_count( $content ) {
		$text = trim( wp_strip_all_tags( strip_shortcodes( $content ) ) );
		return '' === $text ? 0 : count( preg_split( '/\s+/u', $text ) );
	}

	public static function built_with_elementor( $post_id ) {
		return 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true );
	}

	public static function filter_content( $content ) {
		if ( is_admin() || is_feed() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$post_id = get_the_ID();
		if ( get_queried_object_id() !== $post_id || ! self::should_auto_insert( $post_id ) ) {
			return $content;
		}

		$box = Renderer::render( array( 'post_id' => $post_id ) );
		switch ( Settings::get( 'position' ) ) {
			case 'after':
				return $content . $box;
			case 'both':
				return $box . $content . $box;
			default:
				return $box . $content;
		}
	}

	/**
	 * [summarize_with_ai services="chatgpt,claude" label="…" modes="summary,keypoints" mode="summary" template="pill" theme="dark"]
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'services' => '',
				'label'    => null,
				'modes'    => '',
				'mode'     => '',
				'prompt'   => '',
				'template' => '',
				'theme'    => '',
			),
			$atts,
			'summarize_with_ai'
		);

		$args = array( 'context' => 'shortcode' );
		foreach ( array( 'services', 'modes' ) as $list ) {
			if ( '' !== $atts[ $list ] ) {
				$args[ $list ] = array_map( 'sanitize_key', explode( ',', $atts[ $list ] ) );
			}
		}
		if ( null !== $atts['label'] ) {
			$args['label'] = sanitize_text_field( $atts['label'] );
		}
		foreach ( array( 'mode', 'template', 'theme' ) as $key ) {
			if ( '' !== $atts[ $key ] ) {
				$args[ $key ] = sanitize_key( $atts[ $key ] );
			}
		}
		if ( '' !== $atts['prompt'] ) {
			$args['prompt'] = sanitize_textarea_field( $atts['prompt'] );
		}
		return Renderer::render( $args );
	}

	/**
	 * Floating action button + popover, printed in the footer of eligible posts.
	 */
	public static function floating() {
		$s = Settings::get();
		if ( ! $s['floating'] || ! is_singular() ) {
			return;
		}
		$post_id = get_queried_object_id();
		if ( ! self::eligible( $post_id ) ) {
			return;
		}
		$box = Renderer::render(
			array(
				'post_id' => $post_id,
				'context' => 'floating',
			)
		);
		if ( '' === $box ) {
			return;
		}
		$classes = sprintf( 'swai-fab swai-fab--%s swai-fab--%s', sanitize_html_class( $s['floating_side'] ), sanitize_html_class( $s['floating_devices'] ) );
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" style="--swai-accent:<?php echo esc_attr( $s['accent'] ); ?>">
			<button type="button" class="swai-fab__toggle" aria-expanded="false" aria-controls="swai-fab-panel">
				<span class="swai__icon" aria-hidden="true"><?php echo Icons::label_icons()['stars-bold']['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
				<span class="swai-fab__text"><?php echo esc_html( $s['floating_text'] ); ?></span>
			</button>
			<div class="swai-fab__panel" id="swai-fab-panel" hidden>
				<button type="button" class="swai-fab__close" aria-label="<?php esc_attr_e( 'Close', 'summarize-with-ai' ); ?>"><?php echo Icons::svg( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></button>
				<?php echo $box; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer. ?>
			</div>
		</div>
		<?php
	}
}
