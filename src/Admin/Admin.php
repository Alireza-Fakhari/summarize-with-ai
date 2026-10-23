<?php
/**
 * Admin menu, assets, settings registration and the WP dashboard widget.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI\Admin;

use SummarizeWithAI\Analytics\Store;
use SummarizeWithAI\Plugin;
use SummarizeWithAI\Settings;

defined( 'ABSPATH' ) || exit;

final class Admin {

	const CAP          = 'manage_options';
	const SLUG         = 'summarize-with-ai';
	const SLUG_SETTING = 'summarize-with-ai-settings';
	const REPO         = 'https://github.com/Alireza-Fakhari/summarize-with-ai';

	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'dashboard_widget' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SWAI_FILE ), array( __CLASS__, 'action_links' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
	}

	public static function menu() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black"><path d="M15.08 4.1c.35-.8 1.49-.8 1.84 0l.75 1.72c.1.23.29.41.52.51l1.71.75c.8.35.8 1.49 0 1.84l-1.71.75a1 1 0 0 0-.52.51l-.75 1.72c-.35.8-1.49.8-1.84 0l-.75-1.72a1 1 0 0 0-.51-.51l-1.72-.75c-.8-.35-.8-1.49 0-1.84l1.72-.75c.23-.1.41-.28.51-.51zM6.68 9.89c.31-.72 1.33-.72 1.64 0l.4.92c.1.2.26.37.47.46l.92.4c.72.32.72 1.34 0 1.65l-.92.4a.9.9 0 0 0-.47.47l-.4.92c-.31.72-1.33.72-1.64 0l-.4-.92a.9.9 0 0 0-.47-.47l-.92-.4c-.72-.31-.72-1.33 0-1.65l.92-.4c.2-.09.37-.26.47-.46zM13.58 15.66c.2-.3.65-.3.84 0l.69 1.08.15.15 1.08.69c.3.2.3.65 0 .84l-1.08.69-.15.15-.69 1.08c-.2.3-.65.3-.84 0l-.69-1.08-.15-.15-1.08-.69c-.3-.2-.3-.65 0-.84l1.08-.69.15-.15z"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Menu icon data URI.

		add_menu_page( __( 'Summarize with AI', 'summarize-with-ai' ), __( 'Summarize AI', 'summarize-with-ai' ), self::CAP, self::SLUG, array( Dashboard::class, 'render' ), $icon, 81 );
		add_submenu_page( self::SLUG, __( 'Analytics', 'summarize-with-ai' ), __( 'Analytics', 'summarize-with-ai' ), self::CAP, self::SLUG, array( Dashboard::class, 'render' ) );
		add_submenu_page( self::SLUG, __( 'Settings', 'summarize-with-ai' ), __( 'Settings', 'summarize-with-ai' ), self::CAP, self::SLUG_SETTING, array( SettingsPage::class, 'render' ) );
	}

	public static function register_setting() {
		register_setting(
			'swai',
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'show_in_rest'      => false,
			)
		);
		add_action( 'update_option_' . Settings::OPTION, array( Settings::class, 'flush' ) );
	}

	public static function is_plugin_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && false !== strpos( $screen->id, self::SLUG );
	}

	public static function body_class( $classes ) {
		return self::is_plugin_screen() ? $classes . ' swai-admin-screen' : $classes;
	}

	/**
	 * Admin assets load only on this plugin's screens.
	 */
	public static function assets() {
		if ( ! self::is_plugin_screen() ) {
			return;
		}
		wp_enqueue_style( 'swai-admin', Plugin::asset( 'css/admin.css' ), array(), SWAI_VERSION );
		wp_enqueue_script(
			'swai-admin',
			Plugin::asset( 'js/admin.js' ),
			array(),
			SWAI_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		if ( false !== strpos( get_current_screen()->id, self::SLUG_SETTING ) ) {
			wp_enqueue_media();
			wp_enqueue_style( 'swai', Plugin::asset( 'css/frontend.css' ), array(), SWAI_VERSION );
		}
	}

	public static function action_links( $links ) {
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=' . self::SLUG_SETTING ) ), esc_html__( 'Settings', 'summarize-with-ai' ) ),
			sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ), esc_html__( 'Analytics', 'summarize-with-ai' ) )
		);
		return $links;
	}

	public static function dashboard_widget() {
		if ( current_user_can( self::CAP ) && Settings::get( 'analytics' ) ) {
			wp_add_dashboard_widget( 'swai_widget', __( 'Summarize with AI — last 7 days', 'summarize-with-ai' ), array( __CLASS__, 'render_widget' ) );
		}
	}

	public static function render_widget() {
		$r     = Store::report( 7 );
		$max   = max( 1, max( $r['clicks'] ) );
		$delta = self::delta( $r['totals']['clicks'], $r['totals']['prev_clicks'] );
		$top   = $r['services'] ? self::service_name( key( $r['services'] ) ) : '';
		?>
		<div style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px">
			<div>
				<div style="font-size:32px;font-weight:700;line-height:1"><?php echo esc_html( number_format_i18n( $r['totals']['clicks'] ) ); ?></div>
				<div style="color:#64748b;margin-top:6px">
					<?php esc_html_e( 'AI clicks', 'summarize-with-ai' ); ?>
					<?php if ( null !== $delta ) : ?>
						<strong style="color:<?php echo $delta >= 0 ? '#16a34a' : '#e11d48'; ?>"><?php echo esc_html( ( $delta >= 0 ? '+' : '' ) . $delta . '%' ); ?></strong>
					<?php endif; ?>
				</div>
			</div>
			<div style="display:flex;align-items:flex-end;gap:4px;height:48px" aria-hidden="true">
				<?php foreach ( $r['clicks'] as $v ) : ?>
					<span style="width:10px;border-radius:3px;background:#6d5dfc;opacity:.85;height:<?php echo esc_attr( max( 4, round( 48 * $v / $max ) ) ); ?>px"></span>
				<?php endforeach; ?>
			</div>
		</div>
		<p style="margin:14px 0 0;display:flex;justify-content:space-between">
			<span>
				<?php if ( $top ) : ?>
					<?php
					/* translators: %s: AI service name. */
					printf( esc_html__( 'Top AI: %s', 'summarize-with-ai' ), '<strong>' . esc_html( $top ) . '</strong>' );
					?>
				<?php endif; ?>
			</span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>"><?php esc_html_e( 'Open analytics →', 'summarize-with-ai' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Display name for a service id stored in analytics ('copy' = copy button).
	 */
	public static function service_name( $id ) {
		if ( 'copy' === $id ) {
			return __( 'Copy prompt', 'summarize-with-ai' );
		}
		$all = \SummarizeWithAI\Services::all();
		return isset( $all[ $id ] ) ? $all[ $id ]['name'] : $id;
	}

	/**
	 * Percent change, or null when there is no previous value to compare with.
	 */
	public static function delta( $now, $before ) {
		if ( ! $before ) {
			return null;
		}
		return (int) round( ( $now - $before ) / $before * 100 );
	}

	/**
	 * Shared page header (logo, tabs, GitHub link).
	 */
	public static function header( $active ) {
		$tabs = array(
			self::SLUG         => array( __( 'Analytics', 'summarize-with-ai' ), 'chart' ),
			self::SLUG_SETTING => array( __( 'Settings', 'summarize-with-ai' ), 'setting-2' ),
		);
		?>
		<header class="swai-top">
			<div class="swai-brand">
				<span class="swai-brand__logo"><?php echo UiIcons::get( 'stars' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
				<span class="swai-brand__name"><?php esc_html_e( 'Summarize with AI', 'summarize-with-ai' ); ?></span>
				<span class="swai-chip">v<?php echo esc_html( SWAI_VERSION ); ?></span>
			</div>
			<nav class="swai-tabs" aria-label="<?php esc_attr_e( 'Plugin sections', 'summarize-with-ai' ); ?>">
				<?php foreach ( $tabs as $slug => $tab ) : ?>
					<a class="swai-tabs__item<?php echo $slug === $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"<?php echo $slug === $active ? ' aria-current="page"' : ''; ?>>
						<?php echo UiIcons::get( $tab[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
						<span><?php echo esc_html( $tab[0] ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
			<a class="swai-btn swai-btn--ghost" href="<?php echo esc_url( self::REPO ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo UiIcons::get( 'stars' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
				<span><?php esc_html_e( 'Star on GitHub', 'summarize-with-ai' ); ?></span>
			</a>
		</header>
		<?php
	}
}
