<?php
/**
 * Serves /llms.txt — a Markdown index of the site for AI assistants (llmstxt.org).
 * Output is cached and rebuilt only when content or settings change.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI;

defined( 'ABSPATH' ) || exit;

final class Llms {

	const CACHE_KEY = 'swai_llms_txt';

	public static function register() {
		add_action( 'parse_request', array( __CLASS__, 'maybe_serve' ), 0 );
		add_action( 'save_post', array( __CLASS__, 'flush' ) );
		add_action( 'deleted_post', array( __CLASS__, 'flush' ) );
		add_action( 'update_option_' . Settings::OPTION, array( __CLASS__, 'flush' ) );
	}

	public static function flush() {
		delete_transient( self::CACHE_KEY );
	}

	public static function physical_file_exists() {
		return file_exists( ABSPATH . 'llms.txt' );
	}

	public static function maybe_serve() {
		if ( ! Settings::get( 'llms' ) || empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$path   = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		$target = wp_parse_url( home_url( '/llms.txt' ), PHP_URL_PATH );
		if ( $path !== $target ) {
			return;
		}

		$body = get_transient( self::CACHE_KEY );
		if ( false === $body ) {
			$body = self::build();
			set_transient( self::CACHE_KEY, $body, DAY_IN_SECONDS );
		}

		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		header( 'Cache-Control: public, max-age=3600' );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain-text Markdown, built from sanitized text.
		exit;
	}

	private static function line( $text ) {
		return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) ) ) );
	}

	public static function build() {
		$s     = Settings::get();
		$name  = self::line( get_bloginfo( 'name' ) );
		$desc  = self::line( get_bloginfo( 'description' ) );
		$types = $s['llms_post_types'] ? $s['llms_post_types'] : array( 'post' );

		$out = '# ' . $name . "\n\n";
		if ( $desc ) {
			$out .= '> ' . $desc . "\n\n";
		}

		foreach ( $types as $type ) {
			$obj = get_post_type_object( $type );
			if ( ! $obj ) {
				continue;
			}
			$posts = get_posts(
				array(
					'post_type'        => $type,
					'post_status'      => 'publish',
					'numberposts'      => (int) $s['llms_count'],
					'orderby'          => 'modified',
					'has_password'     => false,
					'suppress_filters' => false,
					'no_found_rows'    => true,
				)
			);
			if ( ! $posts ) {
				continue;
			}
			$out .= '## ' . self::line( $obj->labels->name ) . "\n\n";
			foreach ( $posts as $post ) {
				if ( get_post_meta( $post->ID, '_swai_disable', true ) ) {
					continue;
				}
				$excerpt = self::line( has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( strip_shortcodes( $post->post_content ), 30, '…' ) );
				$out    .= sprintf( '- [%s](%s)%s', self::line( get_the_title( $post ) ), get_permalink( $post ), $excerpt ? ': ' . $excerpt : '' ) . "\n";
			}
			$out .= "\n";
		}

		/**
		 * Filter the generated llms.txt body.
		 *
		 * @param string $out Markdown text.
		 */
		return apply_filters( 'swai_llms_txt', $out );
	}
}
