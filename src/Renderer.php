<?php
/**
 * Builds the buttons box markup.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI;

defined( 'ABSPATH' ) || exit;

final class Renderer {

	/**
	 * Render the box.
	 *
	 * @param array $args {
	 *     Optional. Everything defaults to the saved settings.
	 *
	 *     @type string[] $services    Service ids to show.
	 *     @type string   $label       Label text ('' hides it).
	 *     @type bool     $show_modes  Show the mode switcher.
	 *     @type string[] $modes       Restrict to these mode ids.
	 *     @type string   $mode        Initially selected mode.
	 *     @type string   $prompt      Override the prompt of the selected mode.
	 *     @type string   $template    outline|filled|pill|minimal|icons|card.
	 *     @type string   $theme       light|dark|auto.
	 *     @type int      $post_id     Post to summarize; 0 uses the current URL.
	 *     @type string   $context     inline|floating|block|elementor (CSS hook).
	 * }
	 * @return string HTML, or '' when there is nothing to show.
	 */
	public static function render( $args = array() ) {
		$s    = Settings::get();
		$args = wp_parse_args(
			$args,
			array(
				'services'    => $s['enabled'],
				'label'       => $s['label'],
				'show_modes'  => $s['show_modes'],
				'modes'       => array(),
				'mode'        => $s['default_mode'],
				'prompt'      => '',
				'template'    => $s['template'],
				'theme'       => $s['theme'],
				'show_icons'  => $s['show_icons'],
				'new_tab'     => $s['new_tab'],
				'copy_button' => $s['copy_button'],
				'post_id'     => get_the_ID(),
				'class'       => '',
				'context'     => 'inline',
			)
		);

		$all      = Services::all();
		$services = array_values( array_intersect( array_keys( $all ), (array) $args['services'] ) );
		if ( ! $services ) {
			return '';
		}

		$modes = Services::active_modes();
		if ( $args['modes'] ) {
			$subset = array_intersect_key( $modes, array_flip( (array) $args['modes'] ) );
			$modes  = $subset ? $subset : $modes;
		}
		$mode = isset( $modes[ $args['mode'] ] ) ? $args['mode'] : key( $modes );

		$post_id = (int) $args['post_id'];
		$custom  = $post_id ? (string) get_post_meta( $post_id, '_swai_prompt', true ) : '';
		if ( '' !== $args['prompt'] ) {
			$custom = $args['prompt'];
		}
		if ( '' !== $custom ) {
			$modes[ $mode ]['prompt'] = $custom;
		}

		$ctx = self::context( $post_id, false !== strpos( implode( ' ', wp_list_pluck( $modes, 'prompt' ) ), '{excerpt}' ) );

		self::enqueue();

		$template = array_key_exists( $args['template'], Settings::choices( 'template' ) ) ? $args['template'] : 'outline';
		$theme    = array_key_exists( $args['theme'], Settings::choices( 'theme' ) ) ? $args['theme'] : 'light';
		$justify  = array(
			'start'  => 'flex-start',
			'center' => 'center',
			'end'    => 'flex-end',
		);
		$vars     = sprintf(
			'--swai-bg:%s;--swai-text:%s;--swai-border:%s;--swai-hover:%s;--swai-accent:%s;--swai-radius:%dpx;--swai-justify:%s',
			$s['bg'],
			$s['text'],
			$s['border'],
			$s['hover_border'],
			$s['accent'],
			$s['radius'],
			isset( $justify[ $s['align'] ] ) ? $justify[ $s['align'] ] : 'flex-start'
		);
		$classes  = array(
			'swai',
			'swai--' . $template,
			'swai--' . $theme,
			'swai--in-' . sanitize_html_class( $args['context'] ),
			$s['brand_colors'] ? 'swai--brand' : '',
			$args['class'],
		);
		$data     = array(
			'post'  => $post_id,
			'ctx'   => $ctx,
			'mode'  => $mode,
			'modes' => wp_list_pluck( $modes, 'prompt' ),
		);
		$target   = $args['new_tab'] ? ' target="_blank" rel="noopener noreferrer nofollow"' : ' rel="nofollow"';
		$template_prompt = $modes[ $mode ]['prompt'];
		$show_icons      = $args['show_icons'] || 'icons' === $template;

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>" style="<?php echo esc_attr( $vars ); ?>" data-swai="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">
			<?php if ( $args['show_modes'] && count( $modes ) > 1 ) : ?>
				<div class="swai__modes" role="group" aria-label="<?php esc_attr_e( 'What should the AI do?', 'summarize-with-ai' ); ?>">
					<?php foreach ( $modes as $id => $m ) : ?>
						<button type="button" class="swai__mode" data-mode="<?php echo esc_attr( $id ); ?>" aria-pressed="<?php echo $id === $mode ? 'true' : 'false'; ?>"><?php echo esc_html( $m['label'] ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="swai__row">
				<?php if ( '' !== (string) $args['label'] ) : ?>
					<span class="swai__label"><?php echo self::label_icon( $s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts. ?><span><?php echo esc_html( $args['label'] ); ?></span></span>
				<?php endif; ?>
				<?php
				foreach ( $services as $id ) :
					$svc   = $all[ $id ];
					$title = $svc['copy']
						/* translators: %s: AI service name, e.g. Gemini. */
						? sprintf( __( 'Copy the prompt and open %s', 'summarize-with-ai' ), $svc['name'] )
						/* translators: %s: AI service name, e.g. ChatGPT. */
						: sprintf( __( 'Open in %s', 'summarize-with-ai' ), $svc['name'] );
					?>
					<a class="swai__btn" href="<?php echo esc_url( self::service_href( $svc, self::build_prompt( $template_prompt, $ctx, $id ) ) ); ?>" style="--swai-brand:<?php echo esc_attr( $svc['color'] ); ?>" data-service="<?php echo esc_attr( $id ); ?>" data-tpl="<?php echo esc_attr( $svc['url'] ); ?>" data-name="<?php echo esc_attr( $svc['name'] ); ?>"<?php echo $svc['copy'] ? ' data-copy="1"' : ''; ?> title="<?php echo esc_attr( $title ); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static string. ?>>
						<?php echo $show_icons ? self::service_icon( $svc ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts. ?>
						<span class="swai__name"><?php echo esc_html( $svc['name'] ); ?></span>
					</a>
				<?php endforeach; ?>
				<?php if ( $args['copy_button'] ) : ?>
					<button type="button" class="swai__btn swai__copy" title="<?php esc_attr_e( 'Copy prompt', 'summarize-with-ai' ); ?>" aria-label="<?php esc_attr_e( 'Copy prompt', 'summarize-with-ai' ); ?>">
						<span class="swai__icon" aria-hidden="true"><?php echo Icons::svg( 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
					</button>
				<?php endif; ?>
			</div>
			<div class="swai__toast" role="status" aria-live="polite"></div>
		</div>
		<?php
		/**
		 * Filter the final markup.
		 *
		 * @param string $html Box HTML.
		 * @param array  $args Render arguments.
		 */
		return apply_filters( 'swai_render_html', trim( ob_get_clean() ), $args );
	}

	/**
	 * Values available to prompt templates.
	 *
	 * @param int  $post_id      Post id, 0 for the current URL.
	 * @param bool $with_excerpt Build {excerpt} (skipped unless a prompt uses it).
	 */
	public static function context( $post_id, $with_excerpt = false ) {
		if ( $post_id ) {
			$excerpt = '';
			if ( $with_excerpt ) {
				$excerpt = get_post_field( 'post_excerpt', $post_id );
				if ( '' === $excerpt ) {
					$excerpt = get_post_field( 'post_content', $post_id );
				}
				$excerpt = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $excerpt ) ), 40, '…' );
			}
			return array(
				'url'     => get_permalink( $post_id ),
				'title'   => wp_strip_all_tags( get_the_title( $post_id ) ),
				'site'    => get_bloginfo( 'name' ),
				'excerpt' => $excerpt,
			);
		}
		return array(
			'url'     => home_url( add_query_arg( array() ) ),
			'title'   => wp_strip_all_tags( wp_get_document_title() ),
			'site'    => get_bloginfo( 'name' ),
			'excerpt' => '',
		);
	}

	/**
	 * Fill a prompt template. Must stay in sync with buildPrompt() in assets/js/frontend.js.
	 */
	public static function build_prompt( $template, $ctx, $service ) {
		$url    = self::tracked_url( $ctx['url'], $service );
		$prompt = strtr(
			$template,
			array(
				'{url}'     => $url,
				'{title}'   => $ctx['title'],
				'{site}'    => $ctx['site'],
				'{excerpt}' => $ctx['excerpt'],
			)
		);
		if ( false === strpos( $template, '{url}' ) ) {
			$prompt .= ' ' . $url;
		}
		return trim( $prompt );
	}

	/**
	 * Add UTM tags so visits referred by AI assistants are attributable.
	 */
	public static function tracked_url( $url, $service ) {
		if ( ! Settings::get( 'utm' ) || '' === $url ) {
			return $url;
		}
		$sep = false === strpos( $url, '?' ) ? '?' : '&';
		return $url . $sep . 'utm_source=' . rawurlencode( Settings::get( 'utm_source' ) ) . '&utm_medium=' . rawurlencode( $service );
	}

	public static function service_href( $svc, $prompt ) {
		return $svc['copy'] ? $svc['url'] : str_replace( '{q}', rawurlencode( $prompt ), $svc['url'] );
	}

	public static function label_icon( $s ) {
		if ( 'none' === $s['label_icon'] ) {
			return '';
		}
		$style = sprintf( ' style="color:%1$s;width:%2$dpx;height:%2$dpx"', esc_attr( $s['label_icon_color'] ), (int) $s['label_icon_size'] );
		if ( 'custom' === $s['label_icon'] ) {
			return $s['label_icon_url'] ? '<img class="swai__icon swai__label-icon" src="' . esc_url( $s['label_icon_url'] ) . '" alt=""' . $style . '>' : '';
		}
		$icons = Icons::label_icons();
		$icon  = isset( $icons[ $s['label_icon'] ] ) ? $icons[ $s['label_icon'] ] : $icons['stars-bold'];
		return '<span class="swai__icon swai__label-icon" aria-hidden="true"' . $style . '>' . $icon['svg'] . '</span>';
	}

	public static function service_icon( $svc ) {
		if ( ! empty( $svc['icon_url'] ) ) {
			return '<img class="swai__icon" src="' . esc_url( $svc['icon_url'] ) . '" alt="" width="18" height="18" loading="lazy" decoding="async">';
		}
		$svg = $svc['icon'] ? Icons::svg( $svc['icon'] ) : '';
		if ( '' === $svg ) {
			$letter = function_exists( 'mb_substr' ) ? mb_substr( $svc['name'], 0, 1 ) : substr( $svc['name'], 0, 1 );
			return '<span class="swai__icon swai__letter" aria-hidden="true">' . esc_html( strtoupper( $letter ) ) . '</span>';
		}
		return '<span class="swai__icon" aria-hidden="true">' . $svg . '</span>';
	}

	/**
	 * Enqueue front-end assets (registered in Frontend::register_assets()).
	 */
	public static function enqueue() {
		wp_enqueue_style( 'swai' );
		wp_enqueue_script( 'swai' );
	}
}
