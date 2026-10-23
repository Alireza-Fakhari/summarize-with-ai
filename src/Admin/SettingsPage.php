<?php
/**
 * Settings screen: sectioned form with a live preview.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI\Admin;

use SummarizeWithAI\Icons;
use SummarizeWithAI\Llms;
use SummarizeWithAI\Renderer;
use SummarizeWithAI\Services;
use SummarizeWithAI\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Settings screen with live preview.
 */
final class SettingsPage {

	/**
	 * Current settings, cached for the field helpers.
	 *
	 * @var array
	 */
	private static $s = array();

	/**
	 * Settings sections: id => [ title, icon ].
	 */
	private static function sections() {
		return array(
			'general'   => array( __( 'General', 'summarize-with-ai' ), 'setting-2' ),
			'services'  => array( __( 'AI assistants', 'summarize-with-ai' ), 'category' ),
			'prompts'   => array( __( 'Prompts & modes', 'summarize-with-ai' ), 'magic' ),
			'design'    => array( __( 'Design', 'summarize-with-ai' ), 'brush' ),
			'placement' => array( __( 'Placement', 'summarize-with-ai' ), 'layers' ),
			'floating'  => array( __( 'Floating button', 'summarize-with-ai' ), 'flash' ),
			'analytics' => array( __( 'Analytics & SEO', 'summarize-with-ai' ), 'chart' ),
			'tools'     => array( __( 'Tools', 'summarize-with-ai' ), 'setting-3' ),
		);
	}

	/**
	 * Output the settings screen.
	 */
	public static function render() {
		if ( ! current_user_can( Admin::CAP ) ) {
			return;
		}
		self::$s = Settings::get();
		?>
		<div class="wrap swai-app">
			<h1 class="screen-reader-text"><?php esc_html_e( 'Summarize with AI — Settings', 'summarize-with-ai' ); ?></h1>
			<?php Admin::header( Admin::SLUG_SETTING ); ?>
			<?php self::notices(); ?>

			<div class="swai-settings">
				<nav class="swai-side" aria-label="<?php esc_attr_e( 'Settings sections', 'summarize-with-ai' ); ?>">
					<?php foreach ( self::sections() as $id => $sec ) : ?>
						<a class="swai-side__item" href="#<?php echo esc_attr( $id ); ?>" data-section="<?php echo esc_attr( $id ); ?>">
							<?php echo UiIcons::get( $sec[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
							<span><?php echo esc_html( $sec[0] ); ?></span>
						</a>
					<?php endforeach; ?>
				</nav>

				<div class="swai-main">
					<form method="post" action="options.php" id="swai-form" novalidate>
						<?php settings_fields( 'swai' ); ?>
						<?php
						self::section_general();
						self::section_services();
						self::section_prompts();
						self::section_design();
						self::section_placement();
						self::section_floating();
						self::section_analytics();
						?>
						<div class="swai-savebar">
							<span class="swai-savebar__dirty"><?php esc_html_e( 'You have unsaved changes', 'summarize-with-ai' ); ?></span>
							<button type="submit" class="swai-btn swai-btn--primary"><?php echo UiIcons::get( 'tick-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?><span><?php esc_html_e( 'Save changes', 'summarize-with-ai' ); ?></span></button>
						</div>
					</form>
					<?php self::section_tools(); ?>
				</div>

				<aside class="swai-preview" aria-label="<?php esc_attr_e( 'Live preview', 'summarize-with-ai' ); ?>">
					<div class="swai-preview__card">
						<div class="swai-preview__head">
							<span><?php esc_html_e( 'Live preview', 'summarize-with-ai' ); ?></span>
							<span class="swai-preview__dots" aria-hidden="true"><i></i><i></i><i></i></span>
						</div>
						<div class="swai-preview__page">
							<span class="swai-skeleton" style="width:72%"></span>
							<span class="swai-skeleton" style="width:46%"></span>
							<div id="swai-preview-box">
								<?php
								$preview = Renderer::render(
									array(
										'post_id'     => 0,
										'context'     => 'preview',
										'services'    => array_keys( Services::all() ),
										'new_tab'     => true,
										'copy_button' => true,
									)
								);
								echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer.
								?>
							</div>
							<span class="swai-skeleton"></span>
							<span class="swai-skeleton" style="width:88%"></span>
							<span class="swai-skeleton" style="width:64%"></span>
						</div>
						<p class="swai-preview__note"><?php esc_html_e( 'Updates as you edit. Save to apply on your site.', 'summarize-with-ai' ); ?></p>
					</div>
				</aside>
			</div>
		</div>
		<?php
	}

	/**
	 * Output status banners after redirects.
	 */
	private static function notices() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only flags set by our own redirects.
		$map = array(
			'settings-updated' => __( 'Settings saved.', 'summarize-with-ai' ),
			'swai-imported'    => __( 'Settings imported.', 'summarize-with-ai' ),
			'swai-reset'       => __( 'Settings restored to defaults.', 'summarize-with-ai' ),
			'swai-stats-reset' => __( 'Analytics data deleted.', 'summarize-with-ai' ),
			'swai-error'       => __( 'That file could not be imported. Please choose a valid export.', 'summarize-with-ai' ),
		);
		foreach ( $map as $key => $message ) {
			if ( ! empty( $_GET[ $key ] ) ) {
				$error = 'swai-error' === $key;
				printf(
					'<div class="swai-banner %1$s" role="status">%2$s<span>%3$s</span></div>',
					$error ? 'swai-banner--warn' : 'swai-banner--ok',
					UiIcons::get( $error ? 'danger' : 'tick-circle' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG.
					esc_html( $message )
				);
			}
		}
		// phpcs:enable
	}

	// Field helpers. All names are swai_settings[...].

	/**
	 * Form field name for a settings key.
	 *
	 * @param string $key Setting or icon key.
	 */
	private static function name( $key ) {
		return Settings::OPTION . $key;
	}

	/**
	 * Open a settings section.
	 *
	 * @param string $id Section ID.
	 * @param string $desc Section description.
	 */
	private static function open( $id, $desc ) {
		$sec = self::sections()[ $id ];
		?>
		<section class="swai-section" id="swai-section-<?php echo esc_attr( $id ); ?>" data-section="<?php echo esc_attr( $id ); ?>">
			<header class="swai-section__head">
				<span class="swai-section__icon"><?php echo UiIcons::get( $sec[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
				<div>
					<h2><?php echo esc_html( $sec[0] ); ?></h2>
					<p><?php echo esc_html( $desc ); ?></p>
				</div>
			</header>
		<?php
	}

	/**
	 * Close a settings section.
	 */
	private static function close() {
		echo '</section>';
	}

	/**
	 * Output one labeled field row.
	 *
	 * @param string $label Field label.
	 * @param string $desc Help text (may contain <code>).
	 * @param string $control Control markup.
	 */
	private static function row( $label, $desc, $control ) {
		?>
		<div class="swai-field">
			<div class="swai-field__label">
				<span class="swai-field__title"><?php echo esc_html( $label ); ?></span>
				<?php if ( $desc ) : ?>
					<span class="swai-field__desc"><?php echo wp_kses( $desc, array( 'code' => array() ) ); ?></span>
				<?php endif; ?>
			</div>
			<div class="swai-field__control"><?php echo $control; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Controls are escaped by their builders. ?></div>
		</div>
		<?php
	}

	/**
	 * Output a switch field.
	 *
	 * @param string $key Setting or icon key.
	 * @param string $label Field label.
	 * @param string $desc Help text (may contain <code>).
	 * @param string $extra Extra HTML attributes.
	 */
	private static function toggle( $key, $label, $desc = '', $extra = '' ) {
		$control = sprintf(
			'<label class="swai-switch"><input type="checkbox" name="%1$s" value="1" %2$s data-preview="%3$s"%5$s><span class="swai-switch__track" aria-hidden="true"></span><span class="screen-reader-text">%4$s</span></label>',
			esc_attr( self::name( '[' . $key . ']' ) ),
			checked( ! empty( self::$s[ $key ] ), true, false ),
			esc_attr( $key ),
			esc_html( $label ),
			$extra
		);
		self::row( $label, $desc, $control );
	}

	/**
	 * Output a text-like input field.
	 *
	 * @param string $key Setting or icon key.
	 * @param string $label Field label.
	 * @param string $desc Help text (may contain <code>).
	 * @param string $type Input type.
	 * @param string $attrs Extra HTML attributes.
	 */
	private static function text( $key, $label, $desc = '', $type = 'text', $attrs = '' ) {
		$control = sprintf(
			'<input class="swai-input" type="%1$s" name="%2$s" value="%3$s" data-preview="%4$s" %5$s>',
			esc_attr( $type ),
			esc_attr( self::name( '[' . $key . ']' ) ),
			esc_attr( self::$s[ $key ] ),
			esc_attr( $key ),
			$attrs
		);
		self::row( $label, $desc, $control );
	}

	/**
	 * Output a select field.
	 *
	 * @param string $key Setting or icon key.
	 * @param string $label Field label.
	 * @param string $desc Help text (may contain <code>).
	 */
	private static function select( $key, $label, $desc = '' ) {
		$opts = '';
		foreach ( Settings::choices( $key ) as $value => $text ) {
			$opts .= sprintf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $value ), selected( self::$s[ $key ], $value, false ), esc_html( $text ) );
		}
		self::row( $label, $desc, sprintf( '<select class="swai-input" name="%1$s" data-preview="%2$s">%3$s</select>', esc_attr( self::name( '[' . $key . ']' ) ), esc_attr( $key ), $opts ) );
	}

	/**
	 * Output a segmented radio field.
	 *
	 * @param string $key Setting or icon key.
	 * @param string $label Field label.
	 * @param string $desc Help text (may contain <code>).
	 */
	private static function segmented( $key, $label, $desc = '' ) {
		$html = '<div class="swai-segmented swai-segmented--input">';
		foreach ( Settings::choices( $key ) as $value => $text ) {
			$html .= sprintf(
				'<label class="swai-segmented__item"><input type="radio" name="%1$s" value="%2$s" %3$s data-preview="%4$s"><span>%5$s</span></label>',
				esc_attr( self::name( '[' . $key . ']' ) ),
				esc_attr( $value ),
				checked( self::$s[ $key ], $value, false ),
				esc_attr( $key ),
				esc_html( $text )
			);
		}
		self::row( $label, $desc, $html . '</div>' );
	}

	/**
	 * Color input markup.
	 *
	 * @param string $key Setting or icon key.
	 * @param string $label Field label.
	 */
	private static function color( $key, $label ) {
		return sprintf(
			'<label class="swai-color"><input type="color" name="%1$s" value="%2$s" data-preview="%3$s"><span>%4$s</span><code>%2$s</code></label>',
			esc_attr( self::name( '[' . $key . ']' ) ),
			esc_attr( self::$s[ $key ] ),
			esc_attr( $key ),
			esc_html( $label )
		);
	}

	/**
	 * Checkbox pill list markup.
	 *
	 * @param string $key Setting or icon key.
	 * @param array  $options value => label.
	 */
	private static function checklist( $key, $options ) {
		$html = '<div class="swai-checks">';
		foreach ( $options as $value => $text ) {
			$html .= sprintf(
				'<label class="swai-check"><input type="checkbox" name="%1$s[]" value="%2$s" %3$s><span>%4$s</span></label>',
				esc_attr( self::name( '[' . $key . ']' ) ),
				esc_attr( $value ),
				checked( in_array( $value, (array) self::$s[ $key ], false ), true, false ), // phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse -- Mixed int/string ids.
				esc_html( $text )
			);
		}
		return $html . '</div>';
	}

	/**
	 * Public post types: name => label.
	 */
	private static function public_post_types() {
		$out = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $pt ) {
			if ( 'attachment' !== $pt->name ) {
				$out[ $pt->name ] = $pt->labels->name;
			}
		}
		return $out;
	}

	// Sections.

	/**
	 * General section.
	 */
	private static function section_general() {
		$s = self::$s;
		self::open( 'general', __( 'The label and icon shown next to the buttons.', 'summarize-with-ai' ) );
		self::text( 'label', __( 'Label', 'summarize-with-ai' ), __( 'Leave empty to hide the label.', 'summarize-with-ai' ) );

		$icons = '<div class="swai-icon-picker">';
		foreach ( Icons::label_icons() as $key => $icon ) {
			$icons .= sprintf(
				'<label class="swai-icon-picker__item" title="%4$s"><input type="radio" name="%1$s" value="%2$s" %3$s data-preview="label_icon"><span class="swai-icon-picker__glyph" style="color:%6$s">%5$s</span><span class="swai-icon-picker__name">%4$s</span></label>',
				esc_attr( self::name( '[label_icon]' ) ),
				esc_attr( $key ),
				checked( $s['label_icon'], $key, false ),
				esc_html( $icon['name'] ),
				$icon['svg'],
				esc_attr( $s['label_icon_color'] )
			);
		}
		$icons .= sprintf(
			'<label class="swai-icon-picker__item" title="%4$s"><input type="radio" name="%1$s" value="custom" %2$s data-preview="label_icon"><span class="swai-icon-picker__glyph" id="swai-custom-icon">%3$s</span><span class="swai-icon-picker__name">%4$s</span></label>',
			esc_attr( self::name( '[label_icon]' ) ),
			checked( $s['label_icon'], 'custom', false ),
			$s['label_icon_url'] ? '<img src="' . esc_url( $s['label_icon_url'] ) . '" alt="">' : UiIcons::get( 'upload' ),
			esc_html__( 'Upload', 'summarize-with-ai' )
		);
		$icons .= sprintf(
			'<label class="swai-icon-picker__item" title="%3$s"><input type="radio" name="%1$s" value="none" %2$s data-preview="label_icon"><span class="swai-icon-picker__glyph">∅</span><span class="swai-icon-picker__name">%3$s</span></label>',
			esc_attr( self::name( '[label_icon]' ) ),
			checked( $s['label_icon'], 'none', false ),
			esc_html__( 'None', 'summarize-with-ai' )
		);
		$icons .= '</div>';
		$icons .= sprintf(
			'<div class="swai-inline"><input class="swai-input" type="url" dir="ltr" id="swai-icon-url" name="%1$s" value="%2$s" placeholder="https://…/icon.svg"><button type="button" class="swai-btn" id="swai-icon-upload">%3$s</button></div>',
			esc_attr( self::name( '[label_icon_url]' ) ),
			esc_attr( $s['label_icon_url'] ),
			esc_html__( 'Media library', 'summarize-with-ai' )
		);
		self::row( __( 'Label icon', 'summarize-with-ai' ), __( 'Color applies to the built-in icons; uploaded icons keep their own colors.', 'summarize-with-ai' ), $icons );

		self::row(
			__( 'Icon color & size', 'summarize-with-ai' ),
			'',
			'<div class="swai-inline">' . self::color( 'label_icon_color', __( 'Color', 'summarize-with-ai' ) ) .
			sprintf( '<label class="swai-range"><input type="range" min="10" max="48" name="%1$s" value="%2$d" data-preview="label_icon_size"><output>%2$dpx</output></label>', esc_attr( self::name( '[label_icon_size]' ) ), (int) $s['label_icon_size'] ) . '</div>'
		);
		self::toggle( 'copy_button', __( 'Copy prompt button', 'summarize-with-ai' ), __( 'Lets readers copy the prompt to use in any AI app.', 'summarize-with-ai' ) );
		self::toggle( 'new_tab', __( 'Open in a new tab', 'summarize-with-ai' ) );
		self::close();
	}

	/**
	 * AI assistants section.
	 */
	private static function section_services() {
		$s = self::$s;
		self::open( 'services', __( 'Choose, rename and drag to reorder the assistants.', 'summarize-with-ai' ) );
		?>
		<ul class="swai-sortable" id="swai-services">
			<?php
			foreach ( Services::all() as $id => $svc ) :
				$builtin = Services::builtin();
				$default = isset( $builtin[ $id ] ) ? $builtin[ $id ]['name'] : $svc['name'];
				?>
				<li class="swai-sortable__item" draggable="true" data-service="<?php echo esc_attr( $id ); ?>">
					<span class="swai-sortable__handle" aria-hidden="true">⋮⋮</span>
					<input type="hidden" name="<?php echo esc_attr( self::name( '[order][]' ) ); ?>" value="<?php echo esc_attr( $id ); ?>">
					<span class="swai-sortable__icon" style="color:<?php echo esc_attr( $svc['color'] ); ?>"><?php echo Renderer::service_icon( $svc ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer. ?></span>
					<input class="swai-input swai-sortable__name" type="text" name="<?php echo esc_attr( self::name( '[names][' . $id . ']' ) ); ?>" value="<?php echo esc_attr( isset( $s['names'][ $id ] ) ? $s['names'][ $id ] : '' ); ?>" placeholder="<?php echo esc_attr( $default ); ?>" aria-label="<?php esc_attr_e( 'Display name', 'summarize-with-ai' ); ?>">
					<?php if ( $svc['copy'] ) : ?>
						<span class="swai-chip swai-chip--soft" title="<?php esc_attr_e( 'This assistant has no prompt link, so the prompt is copied and the visitor pastes it.', 'summarize-with-ai' ); ?>"><?php esc_html_e( 'copy & paste', 'summarize-with-ai' ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $svc['custom'] ) ) : ?>
						<span class="swai-chip swai-chip--soft"><?php esc_html_e( 'custom', 'summarize-with-ai' ); ?></span>
					<?php endif; ?>
					<label class="swai-switch">
						<input type="checkbox" name="<?php echo esc_attr( self::name( '[enabled][]' ) ); ?>" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( $id, (array) $s['enabled'], true ) ); ?> data-preview="service">
						<span class="swai-switch__track" aria-hidden="true"></span>
						<span class="screen-reader-text">
							<?php
							/* translators: %s: service name. */
							echo esc_html( sprintf( __( 'Show %s', 'summarize-with-ai' ), $svc['name'] ) );
							?>
						</span>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		$custom = implode(
			"\n",
			array_map(
				static function ( $c ) {
					return implode( '|', array_filter( array( $c['id'], $c['name'], $c['url'], $c['icon_url'] ), 'strlen' ) );
				},
				(array) $s['custom']
			)
		);
		self::row(
			__( 'Custom assistants', 'summarize-with-ai' ),
			__( 'One per line: <code>id|Name|https://example.com/?q={q}|icon-url</code>. Without <code>{q}</code> the prompt is copied instead.', 'summarize-with-ai' ),
			sprintf( '<textarea class="swai-input swai-code" dir="ltr" rows="3" name="%1$s" placeholder="perplexica|Perplexica|https://search.example.com/?q={q}">%2$s</textarea>', esc_attr( self::name( '[custom_text]' ) ), esc_textarea( $custom ) )
		);
		self::close();
	}

	/**
	 * Prompts and modes section.
	 */
	private static function section_prompts() {
		$s = self::$s;
		self::open( 'prompts', __( 'Modes let readers pick what the AI should do with the page.', 'summarize-with-ai' ) );
		self::toggle( 'show_modes', __( 'Show mode switcher', 'summarize-with-ai' ), __( 'When off, only the default mode is used.', 'summarize-with-ai' ) );

		$opts = '';
		foreach ( $s['modes'] as $id => $m ) {
			$opts .= sprintf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $id ), selected( $s['default_mode'], $id, false ), esc_html( $m['label'] ) );
		}
		self::row( __( 'Default mode', 'summarize-with-ai' ), '', sprintf( '<select class="swai-input" name="%1$s">%2$s</select>', esc_attr( self::name( '[default_mode]' ) ), $opts ) );
		?>
		<div class="swai-vars">
			<span><?php esc_html_e( 'Variables:', 'summarize-with-ai' ); ?></span>
			<?php foreach ( array( '{url}', '{title}', '{site}', '{excerpt}' ) as $var ) : ?>
				<button type="button" class="swai-chip swai-chip--var" data-insert="<?php echo esc_attr( $var ); ?>"><?php echo esc_html( $var ); ?></button>
			<?php endforeach; ?>
		</div>
		<div class="swai-modes-grid">
			<?php foreach ( $s['modes'] as $id => $m ) : ?>
				<div class="swai-mode-card">
					<div class="swai-mode-card__head">
						<input class="swai-input swai-mode-card__label" type="text" name="<?php echo esc_attr( self::name( '[modes][' . $id . '][label]' ) ); ?>" value="<?php echo esc_attr( $m['label'] ); ?>" aria-label="<?php esc_attr_e( 'Mode name', 'summarize-with-ai' ); ?>" data-preview="mode-label" data-mode="<?php echo esc_attr( $id ); ?>">
						<label class="swai-switch">
							<input type="checkbox" name="<?php echo esc_attr( self::name( '[modes][' . $id . '][enabled]' ) ); ?>" value="1" <?php checked( ! empty( $m['enabled'] ) ); ?> data-preview="mode" data-mode="<?php echo esc_attr( $id ); ?>">
							<span class="swai-switch__track" aria-hidden="true"></span>
							<span class="screen-reader-text"><?php esc_html_e( 'Enabled', 'summarize-with-ai' ); ?></span>
						</label>
					</div>
					<textarea class="swai-input" rows="3" name="<?php echo esc_attr( self::name( '[modes][' . $id . '][prompt]' ) ); ?>" aria-label="<?php esc_attr_e( 'Prompt', 'summarize-with-ai' ); ?>"><?php echo esc_textarea( $m['prompt'] ); ?></textarea>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		self::close();
	}

	/**
	 * Design section.
	 */
	private static function section_design() {
		$s = self::$s;
		self::open( 'design', __( 'Make the buttons match your theme.', 'summarize-with-ai' ) );

		$cards = '<div class="swai-templates">';
		foreach ( Settings::choices( 'template' ) as $value => $text ) {
			$cards .= sprintf(
				'<label class="swai-template swai-template--%2$s"><input type="radio" name="%1$s" value="%2$s" %3$s data-preview="template"><span class="swai-template__art" aria-hidden="true"><i></i><i></i><i></i></span><span>%4$s</span></label>',
				esc_attr( self::name( '[template]' ) ),
				esc_attr( $value ),
				checked( $s['template'], $value, false ),
				esc_html( $text )
			);
		}
		self::row( __( 'Style', 'summarize-with-ai' ), '', $cards . '</div>' );
		self::segmented( 'theme', __( 'Color scheme', 'summarize-with-ai' ) );
		self::segmented( 'align', __( 'Alignment', 'summarize-with-ai' ) );
		self::toggle( 'show_icons', __( 'Show assistant icons', 'summarize-with-ai' ) );
		self::toggle( 'brand_colors', __( 'Brand-colored icons', 'summarize-with-ai' ), __( 'Tint each icon with the assistant’s brand color.', 'summarize-with-ai' ) );
		self::row(
			__( 'Colors', 'summarize-with-ai' ),
			__( 'Used by the light scheme; dark uses its own palette.', 'summarize-with-ai' ),
			'<div class="swai-colors">' .
				self::color( 'accent', __( 'Accent', 'summarize-with-ai' ) ) .
				self::color( 'bg', __( 'Background', 'summarize-with-ai' ) ) .
				self::color( 'text', __( 'Text', 'summarize-with-ai' ) ) .
				self::color( 'border', __( 'Border', 'summarize-with-ai' ) ) .
				self::color( 'hover_border', __( 'Hover border', 'summarize-with-ai' ) ) .
			'</div>'
		);
		self::row(
			__( 'Corner radius', 'summarize-with-ai' ),
			'',
			sprintf( '<label class="swai-range"><input type="range" min="0" max="30" name="%1$s" value="%2$d" data-preview="radius"><output>%2$dpx</output></label>', esc_attr( self::name( '[radius]' ) ), (int) $s['radius'] )
		);
		self::close();
	}

	/**
	 * Placement section.
	 */
	private static function section_placement() {
		self::open( 'placement', __( 'Where the buttons appear automatically.', 'summarize-with-ai' ) );
		self::select( 'position', __( 'Position', 'summarize-with-ai' ), __( 'Use the shortcode <code>[summarize_with_ai]</code>, the block or the Elementor widget for manual placement.', 'summarize-with-ai' ) );
		self::row( __( 'Post types', 'summarize-with-ai' ), '', self::checklist( 'post_types', self::public_post_types() ) );
		self::text( 'min_words', __( 'Minimum words', 'summarize-with-ai' ), __( 'Skip short posts. 0 shows on every post.', 'summarize-with-ai' ), 'number', 'min="0" step="50"' );

		$cats = array();
		foreach ( get_categories( array( 'hide_empty' => false ) ) as $cat ) {
			$cats[ $cat->term_id ] = $cat->name;
		}
		if ( $cats ) {
			self::row( __( 'Exclude categories', 'summarize-with-ai' ), '', self::checklist( 'exclude_cats', $cats ) );
		}
		self::toggle( 'skip_elementor', __( 'Skip Elementor pages', 'summarize-with-ai' ), __( 'Don’t auto-insert on pages built with Elementor; place the widget there instead.', 'summarize-with-ai' ) );
		self::close();
	}

	/**
	 * Floating button section.
	 */
	private static function section_floating() {
		self::open( 'floating', __( 'A floating button that follows readers as they scroll.', 'summarize-with-ai' ) );
		self::toggle( 'floating', __( 'Enable floating button', 'summarize-with-ai' ) );
		self::text( 'floating_text', __( 'Button text', 'summarize-with-ai' ), __( 'Hidden on small screens, where only the icon shows.', 'summarize-with-ai' ) );
		self::segmented( 'floating_side', __( 'Side', 'summarize-with-ai' ) );
		self::segmented( 'floating_devices', __( 'Show on', 'summarize-with-ai' ) );
		self::close();
	}

	/**
	 * Analytics and SEO section.
	 */
	private static function section_analytics() {
		self::open( 'analytics', __( 'Measure usage and help AI assistants understand your site.', 'summarize-with-ai' ) );
		self::toggle( 'analytics', __( 'Click analytics', 'summarize-with-ai' ), __( 'Anonymous daily totals. No cookies, no IPs, no personal data.', 'summarize-with-ai' ) );
		self::toggle( 'track_views', __( 'Track impressions', 'summarize-with-ai' ), __( 'Needed for CTR. Adds one tiny background request per page view.', 'summarize-with-ai' ) );
		self::text( 'retention_days', __( 'Keep data for (days)', 'summarize-with-ai' ), '', 'number', 'min="30" max="3650"' );
		self::toggle( 'utm', __( 'UTM tags', 'summarize-with-ai' ), __( 'Adds <code>utm_source</code> and <code>utm_medium</code> to the shared link so AI-referred visits show up in Google Analytics.', 'summarize-with-ai' ) );
		self::text( 'utm_source', __( 'UTM source', 'summarize-with-ai' ), '', 'text', 'dir="ltr"' );

		$llms_url = home_url( '/llms.txt' );
		self::toggle(
			'llms',
			__( 'Serve /llms.txt', 'summarize-with-ai' ),
			sprintf(
				/* translators: %s: llms.txt URL. */
				__( 'A Markdown index of your best content for AI crawlers, at <code>%s</code>.', 'summarize-with-ai' ),
				esc_html( $llms_url )
			)
		);
		self::text( 'llms_count', __( 'Posts listed in llms.txt', 'summarize-with-ai' ), '', 'number', 'min="1" max="500"' );
		self::row( __( 'llms.txt content', 'summarize-with-ai' ), '', self::checklist( 'llms_post_types', self::public_post_types() ) );
		if ( Llms::physical_file_exists() ) {
			echo '<div class="swai-banner swai-banner--warn">' . UiIcons::get( 'danger' ) . '<span>' . esc_html__( 'A physical llms.txt file exists in your site root and will be served instead of the generated one.', 'summarize-with-ai' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG.
		}
		self::close();
	}

	/**
	 * Tools section (separate forms).
	 */
	private static function section_tools() {
		$export = wp_json_encode( Settings::get(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		self::open( 'tools', __( 'Back up, move or reset your configuration.', 'summarize-with-ai' ) );
		?>
		<div class="swai-tools">
			<div class="swai-tool">
				<span class="swai-tool__icon"><?php echo UiIcons::get( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
				<h3><?php esc_html_e( 'Export settings', 'summarize-with-ai' ); ?></h3>
				<p><?php esc_html_e( 'Download a JSON file to copy this setup to another site.', 'summarize-with-ai' ); ?></p>
				<button type="button" class="swai-btn" id="swai-export" data-json="<?php echo esc_attr( $export ); ?>"><?php esc_html_e( 'Download JSON', 'summarize-with-ai' ); ?></button>
			</div>
			<form class="swai-tool" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<span class="swai-tool__icon"><?php echo UiIcons::get( 'upload' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
				<h3><?php esc_html_e( 'Import settings', 'summarize-with-ai' ); ?></h3>
				<p><?php esc_html_e( 'Replace the current settings with an exported file.', 'summarize-with-ai' ); ?></p>
				<input type="hidden" name="action" value="swai_import">
				<?php wp_nonce_field( 'swai_import' ); ?>
				<input type="file" name="swai_file" accept="application/json,.json" required>
				<button type="submit" class="swai-btn"><?php esc_html_e( 'Import', 'summarize-with-ai' ); ?></button>
			</form>
			<form class="swai-tool" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-confirm="<?php esc_attr_e( 'Restore all settings to their defaults?', 'summarize-with-ai' ); ?>">
				<span class="swai-tool__icon"><?php echo UiIcons::get( 'refresh-2' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
				<h3><?php esc_html_e( 'Reset settings', 'summarize-with-ai' ); ?></h3>
				<p><?php esc_html_e( 'Go back to the default configuration. Analytics are kept.', 'summarize-with-ai' ); ?></p>
				<input type="hidden" name="action" value="swai_reset">
				<?php wp_nonce_field( 'swai_reset' ); ?>
				<button type="submit" class="swai-btn"><?php esc_html_e( 'Reset settings', 'summarize-with-ai' ); ?></button>
			</form>
			<form class="swai-tool swai-tool--danger" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-confirm="<?php esc_attr_e( 'Permanently delete all analytics data?', 'summarize-with-ai' ); ?>">
				<span class="swai-tool__icon"><?php echo UiIcons::get( 'trash' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
				<h3><?php esc_html_e( 'Delete analytics', 'summarize-with-ai' ); ?></h3>
				<p><?php esc_html_e( 'Remove all recorded clicks and impressions.', 'summarize-with-ai' ); ?></p>
				<input type="hidden" name="action" value="swai_reset_stats">
				<?php wp_nonce_field( 'swai_reset_stats' ); ?>
				<button type="submit" class="swai-btn swai-btn--danger"><?php esc_html_e( 'Delete data', 'summarize-with-ai' ); ?></button>
			</form>
		</div>

		<div class="swai-usage">
			<h3><?php esc_html_e( 'Developer usage', 'summarize-with-ai' ); ?></h3>
			<pre dir="ltr"><code>[summarize_with_ai services="chatgpt,claude" modes="summary,keypoints" template="pill" theme="auto"]

&lt;?php echo swai_render( array( 'template' =&gt; 'card' ) ); ?&gt;

add_filter( 'swai_builtin_services', function ( $services ) { … } );
add_filter( 'swai_modes', function ( $modes ) { … } );
add_filter( 'swai_is_eligible', function ( $show, $post ) { … }, 10, 2 );</code></pre>
		</div>
		<?php
		self::close();
	}
}
