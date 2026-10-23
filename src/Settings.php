<?php
/**
 * Settings storage, defaults and sanitization.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI;

defined( 'ABSPATH' ) || exit;

/**
 * Reads, defaults and sanitizes the plugin settings.
 */
final class Settings {

	const OPTION = 'swai_settings';

	/**
	 * Request-level cache; reset whenever the option changes.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Get all settings, or one value by key.
	 *
	 * @param string|null $key Setting key, or null for all.
	 */
	public static function get( $key = null ) {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		}
		if ( null === $key ) {
			return self::$cache;
		}
		return isset( self::$cache[ $key ] ) ? self::$cache[ $key ] : null;
	}

	/**
	 * Clear the cached value.
	 */
	public static function flush() {
		self::$cache = null;
	}

	/**
	 * Default settings.
	 */
	public static function defaults() {
		return array(
			// Content.
			'label'            => __( 'Summarize with:', 'summarize-with-ai' ),
			'label_icon'       => 'stars-bold',
			'label_icon_url'   => '',
			'label_icon_color' => '#6d5dfc',
			'label_icon_size'  => 18,
			'modes'            => Services::default_modes(),
			'show_modes'       => 1,
			'default_mode'     => 'summary',
			'copy_button'      => 1,
			// Services.
			'enabled'          => array( 'chatgpt', 'perplexity', 'claude', 'gemini' ),
			'order'            => array_keys( Services::builtin() ),
			'names'            => array(),
			'custom'           => array(),
			// Placement.
			'position'         => 'before',
			'post_types'       => array( 'post' ),
			'skip_elementor'   => 1,
			'min_words'        => 0,
			'exclude_cats'     => array(),
			// Design.
			'template'         => 'outline',
			'theme'            => 'light',
			'align'            => 'start',
			'show_icons'       => 1,
			'brand_colors'     => 0,
			'new_tab'          => 1,
			'bg'               => '#ffffff',
			'text'             => '#1e2a4a',
			'border'           => '#e3e6ef',
			'hover_border'     => '#9aa6c4',
			'accent'           => '#6d5dfc',
			'radius'           => 10,
			// Floating button.
			'floating'         => 0,
			'floating_side'    => 'right',
			'floating_devices' => 'all',
			'floating_text'    => __( 'Summarize with AI', 'summarize-with-ai' ),
			// Analytics & SEO.
			'analytics'        => 1,
			'track_views'      => 0,
			'retention_days'   => 365,
			'utm'              => 1,
			'utm_source'       => 'ai-assistant',
			'llms'             => 0,
			'llms_count'       => 50,
			'llms_post_types'  => array( 'post', 'page' ),
		);
	}

	/**
	 * Choice lists shared by the settings UI and the sanitizer.
	 *
	 * @param string $key Setting key.
	 */
	public static function choices( $key ) {
		$choices = array(
			'position'         => array(
				'before' => __( 'Before content', 'summarize-with-ai' ),
				'after'  => __( 'After content', 'summarize-with-ai' ),
				'both'   => __( 'Before and after', 'summarize-with-ai' ),
				'none'   => __( 'Manual only (shortcode, block, Elementor)', 'summarize-with-ai' ),
			),
			'template'         => array(
				'outline' => __( 'Outline', 'summarize-with-ai' ),
				'filled'  => __( 'Filled', 'summarize-with-ai' ),
				'pill'    => __( 'Pill', 'summarize-with-ai' ),
				'minimal' => __( 'Minimal', 'summarize-with-ai' ),
				'icons'   => __( 'Icons only', 'summarize-with-ai' ),
				'card'    => __( 'Card', 'summarize-with-ai' ),
			),
			'theme'            => array(
				'light' => __( 'Light', 'summarize-with-ai' ),
				'dark'  => __( 'Dark', 'summarize-with-ai' ),
				'auto'  => __( 'Match visitor system', 'summarize-with-ai' ),
			),
			'align'            => array(
				'start'  => __( 'Start', 'summarize-with-ai' ),
				'center' => __( 'Center', 'summarize-with-ai' ),
				'end'    => __( 'End', 'summarize-with-ai' ),
			),
			'floating_side'    => array(
				'right' => __( 'Right', 'summarize-with-ai' ),
				'left'  => __( 'Left', 'summarize-with-ai' ),
			),
			'floating_devices' => array(
				'all'     => __( 'All devices', 'summarize-with-ai' ),
				'mobile'  => __( 'Mobile only', 'summarize-with-ai' ),
				'desktop' => __( 'Desktop only', 'summarize-with-ai' ),
			),
		);
		return isset( $choices[ $key ] ) ? $choices[ $key ] : array();
	}

	/**
	 * Sanitize the full settings array coming from the settings form or an import.
	 *
	 * @param mixed $in Raw input.
	 * @return array
	 */
	public static function sanitize( $in ) {
		$in  = is_array( $in ) ? wp_unslash( $in ) : array();
		$d   = self::defaults();
		$out = array();

		$text   = static function ( $key ) use ( $in, $d ) {
			return isset( $in[ $key ] ) ? sanitize_text_field( $in[ $key ] ) : $d[ $key ];
		};
		$bool   = static function ( $key ) use ( $in ) {
			return empty( $in[ $key ] ) ? 0 : 1;
		};
		$int    = static function ( $key, $min, $max ) use ( $in, $d ) {
			$v = isset( $in[ $key ] ) ? (int) $in[ $key ] : $d[ $key ];
			return max( $min, min( $max, $v ) );
		};
		$choice = static function ( $key ) use ( $in, $d ) {
			$v = isset( $in[ $key ] ) ? (string) $in[ $key ] : '';
			return array_key_exists( $v, self::choices( $key ) ) ? $v : $d[ $key ];
		};
		$color  = static function ( $key ) use ( $in, $d ) {
			$v = isset( $in[ $key ] ) ? sanitize_hex_color( $in[ $key ] ) : '';
			return $v ? $v : $d[ $key ];
		};
		$keys   = static function ( $key ) use ( $in ) {
			return array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) ( isset( $in[ $key ] ) ? $in[ $key ] : array() ) ) ) ) );
		};

		// Content.
		$out['label']            = $text( 'label' );
		$out['label_icon']       = isset( $in['label_icon'] ) && in_array( $in['label_icon'], array_merge( array_keys( Icons::label_icons() ), array( 'custom', 'none' ) ), true ) ? $in['label_icon'] : $d['label_icon'];
		$out['label_icon_url']   = isset( $in['label_icon_url'] ) ? esc_url_raw( $in['label_icon_url'] ) : '';
		$out['label_icon_color'] = $color( 'label_icon_color' );
		$out['label_icon_size']  = $int( 'label_icon_size', 10, 48 );
		$out['show_modes']       = $bool( 'show_modes' );
		$out['copy_button']      = $bool( 'copy_button' );

		$out['modes'] = array();
		$raw_modes    = isset( $in['modes'] ) && is_array( $in['modes'] ) ? $in['modes'] : array();
		foreach ( $d['modes'] as $id => $mode ) {
			$m                   = isset( $raw_modes[ $id ] ) && is_array( $raw_modes[ $id ] ) ? $raw_modes[ $id ] : array();
			$out['modes'][ $id ] = array(
				'label'   => isset( $m['label'] ) && '' !== trim( $m['label'] ) ? sanitize_text_field( $m['label'] ) : $mode['label'],
				'prompt'  => isset( $m['prompt'] ) && '' !== trim( $m['prompt'] ) ? sanitize_textarea_field( $m['prompt'] ) : $mode['prompt'],
				'enabled' => empty( $m['enabled'] ) ? 0 : 1,
			);
		}
		$out['default_mode'] = isset( $in['default_mode'] ) && isset( $out['modes'][ $in['default_mode'] ] ) ? $in['default_mode'] : 'summary';

		// Services.
		$out['custom']  = self::parse_custom_services( isset( $in['custom_text'] ) ? $in['custom_text'] : '', isset( $in['custom'] ) ? $in['custom'] : null );
		$valid          = array_merge( array_keys( Services::builtin() ), wp_list_pluck( $out['custom'], 'id' ) );
		$out['enabled'] = array_values( array_intersect( $keys( 'enabled' ), $valid ) );
		$order          = array_values( array_intersect( $keys( 'order' ), $valid ) );
		$out['order']   = array_values( array_unique( array_merge( $order, $valid ) ) );
		$out['names']   = array();
		foreach ( (array) ( isset( $in['names'] ) ? $in['names'] : array() ) as $id => $name ) {
			$id = sanitize_key( $id );
			if ( in_array( $id, $valid, true ) && '' !== trim( (string) $name ) ) {
				$out['names'][ $id ] = sanitize_text_field( $name );
			}
		}

		// Placement.
		$out['position']       = $choice( 'position' );
		$out['post_types']     = array_values( array_intersect( $keys( 'post_types' ), get_post_types( array( 'public' => true ) ) ) );
		$out['skip_elementor'] = $bool( 'skip_elementor' );
		$out['min_words']      = $int( 'min_words', 0, 100000 );
		$out['exclude_cats']   = array_values( array_filter( array_map( 'absint', (array) ( isset( $in['exclude_cats'] ) ? $in['exclude_cats'] : array() ) ) ) );

		// Design.
		$out['template']     = $choice( 'template' );
		$out['theme']        = $choice( 'theme' );
		$out['align']        = $choice( 'align' );
		$out['show_icons']   = $bool( 'show_icons' );
		$out['brand_colors'] = $bool( 'brand_colors' );
		$out['new_tab']      = $bool( 'new_tab' );
		foreach ( array( 'bg', 'text', 'border', 'hover_border', 'accent' ) as $k ) {
			$out[ $k ] = $color( $k );
		}
		$out['radius'] = $int( 'radius', 0, 50 );

		// Floating.
		$out['floating']         = $bool( 'floating' );
		$out['floating_side']    = $choice( 'floating_side' );
		$out['floating_devices'] = $choice( 'floating_devices' );
		$out['floating_text']    = $text( 'floating_text' );

		// Analytics & SEO.
		$out['analytics']       = $bool( 'analytics' );
		$out['track_views']     = $bool( 'track_views' );
		$out['retention_days']  = $int( 'retention_days', 30, 3650 );
		$out['utm']             = $bool( 'utm' );
		$out['utm_source']      = sanitize_title( $text( 'utm_source' ) );
		$out['utm_source']      = $out['utm_source'] ? $out['utm_source'] : $d['utm_source'];
		$out['llms']            = $bool( 'llms' );
		$out['llms_count']      = $int( 'llms_count', 1, 500 );
		$out['llms_post_types'] = array_values( array_intersect( $keys( 'llms_post_types' ), get_post_types( array( 'public' => true ) ) ) );

		self::flush();
		return $out;
	}

	/**
	 * Custom services come from a textarea ("id|Name|URL with {q}|icon URL" per line)
	 * or, on import, as an already structured array.
	 *
	 * @param string     $text Raw textarea input.
	 * @param array|null $structured Structured rows from an import.
	 */
	private static function parse_custom_services( $text, $structured ) {
		$rows = array();
		if ( is_array( $structured ) && '' === $text ) {
			foreach ( $structured as $c ) {
				if ( is_array( $c ) ) {
					$rows[] = array( isset( $c['id'] ) ? $c['id'] : '', isset( $c['name'] ) ? $c['name'] : '', isset( $c['url'] ) ? $c['url'] : '', isset( $c['icon_url'] ) ? $c['icon_url'] : '' );
				}
			}
		} else {
			foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
				$rows[] = array_map( 'trim', explode( '|', $line ) );
			}
		}

		$builtin = Services::builtin();
		$out     = array();
		foreach ( $rows as $p ) {
			if ( count( $p ) < 3 ) {
				continue;
			}
			$id  = sanitize_key( $p[0] );
			$url = esc_url_raw( $p[2], array( 'https', 'http' ) );
			if ( ! $id || ! $url || isset( $builtin[ $id ] ) ) {
				continue;
			}
			$out[ $id ] = array(
				'id'       => $id,
				'name'     => sanitize_text_field( $p[1] ),
				'url'      => $url,
				'icon_url' => isset( $p[3] ) ? esc_url_raw( $p[3] ) : '',
			);
		}
		return array_values( $out );
	}
}
