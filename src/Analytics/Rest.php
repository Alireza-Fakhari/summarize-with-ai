<?php
/**
 * Public REST endpoint that receives click / view beacons.
 *
 * The endpoint is intentionally unauthenticated (it must work behind page
 * caches, where nonces go stale), so every field is validated against an
 * allow-list and each client is rate-limited by a salted, non-reversible hash.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI\Analytics;

use SummarizeWithAI\Services;
use SummarizeWithAI\Settings;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * REST endpoint that records click and view beacons.
 */
final class Rest {

	const RATE_LIMIT  = 60;  // Max events per client per window.
	const RATE_WINDOW = 600; // Window length in seconds.

	/**
	 * Hook the component into WordPress.
	 */
	public static function register() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public static function routes() {
		register_rest_route(
			'swai/v1',
			'/event',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'event'   => array(
						'type'     => 'string',
						'enum'     => array( 'click', 'view' ),
						'required' => true,
					),
					'post'    => array(
						'type'    => 'integer',
						'minimum' => 0,
						'default' => 0,
					),
					'service' => array(
						'type'      => 'string',
						'default'   => '',
						'maxLength' => 40,
					),
					'mode'    => array(
						'type'      => 'string',
						'default'   => '',
						'maxLength' => 40,
					),
					'device'  => array(
						'type'    => 'string',
						'enum'    => array( 'mobile', 'desktop' ),
						'default' => 'desktop',
					),
				),
			)
		);
	}

	/**
	 * Validate and record one event.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 */
	public static function handle( WP_REST_Request $request ) {
		if ( ! Settings::get( 'analytics' ) ) {
			return new WP_REST_Response( null, 204 );
		}

		$event   = $request['event'];
		$post_id = (int) $request['post'];
		$service = sanitize_key( $request['service'] );
		$mode    = sanitize_key( $request['mode'] );

		if ( 'view' === $event && ! Settings::get( 'track_views' ) ) {
			return new WP_REST_Response( null, 204 );
		}
		if ( 'click' === $event && 'copy' !== $service && ! array_key_exists( $service, Services::all() ) ) {
			return new WP_REST_Response( array( 'error' => 'invalid_service' ), 400 );
		}
		if ( 'view' === $event ) {
			$service = '';
		}
		if ( '' !== $mode && ! array_key_exists( $mode, (array) Settings::get( 'modes' ) ) ) {
			$mode = '';
		}
		if ( $post_id && 'publish' !== get_post_status( $post_id ) ) {
			return new WP_REST_Response( array( 'error' => 'invalid_post' ), 400 );
		}
		if ( self::rate_limited() ) {
			return new WP_REST_Response( array( 'error' => 'rate_limited' ), 429 );
		}

		Store::record( $event, $post_id, $service, $mode, $request['device'] );
		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Fixed-window limiter keyed by a salted hash of the client address.
	 * The address itself is never stored.
	 */
	private static function rate_limited() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'swai_rl_' . substr( wp_hash( $ip . '|' . floor( time() / self::RATE_WINDOW ), 'nonce' ), 0, 20 );
		$n   = (int) get_transient( $key );
		if ( $n >= self::RATE_LIMIT ) {
			return true;
		}
		set_transient( $key, $n + 1, self::RATE_WINDOW );
		return false;
	}
}
