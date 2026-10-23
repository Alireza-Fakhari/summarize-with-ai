<?php
/**
 * Privacy-first analytics storage.
 *
 * Events are aggregated into one row per day / post / service / mode / device / event,
 * so the table stays small and every hit is a single indexed upsert.
 * No IP addresses, cookies or user identifiers are ever stored.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI\Analytics;

use SummarizeWithAI\Settings;

defined( 'ABSPATH' ) || exit;

final class Store {

	const DB_VERSION     = '1';
	const DB_VERSION_KEY = 'swai_db_version';
	const PRUNE_HOOK     = 'swai_prune_stats';

	public static function register() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );
		add_action( self::PRUNE_HOOK, array( __CLASS__, 'prune' ) );
		if ( ! wp_next_scheduled( self::PRUNE_HOOK ) && ! wp_installing() ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', self::PRUNE_HOOK );
		}
	}

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'swai_stats';
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$table} (
				day date NOT NULL,
				post_id bigint(20) unsigned NOT NULL DEFAULT 0,
				service varchar(40) NOT NULL DEFAULT '',
				mode varchar(40) NOT NULL DEFAULT '',
				device varchar(10) NOT NULL DEFAULT '',
				event varchar(10) NOT NULL DEFAULT 'click',
				hits int(10) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (day,post_id,service,mode,device,event),
				KEY post_id (post_id)
			) {$charset};"
		);
		update_option( self::DB_VERSION_KEY, self::DB_VERSION, false );
	}

	public static function maybe_upgrade() {
		if ( get_option( self::DB_VERSION_KEY ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Record one event (upsert).
	 */
	public static function record( $event, $post_id, $service, $mode, $device ) {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom table, write path.
		return false !== $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (day, post_id, service, mode, device, event, hits) VALUES (%s, %d, %s, %s, %s, %s, 1) ON DUPLICATE KEY UPDATE hits = hits + 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
				current_time( 'Y-m-d' ),
				$post_id,
				$service,
				$mode,
				$device,
				$event
			)
		);
	}

	/**
	 * Delete rows older than the retention window (daily cron).
	 */
	public static function prune() {
		global $wpdb;
		$table = self::table();
		$days  = (int) Settings::get( 'retention_days' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE day < %s", gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . " -{$days} days" ) ) ) );
	}

	public static function reset() {
		global $wpdb;
		$table = self::table();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Everything the dashboard needs for a date range, plus the previous
	 * period of the same length for comparison. Cached for 5 minutes.
	 *
	 * @param int $days Range length in days.
	 * @return array
	 */
	public static function report( $days ) {
		$days      = max( 1, (int) $days );
		$cache_key = 'swai_report_' . $days;
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		$table = self::table();
		$today = current_time( 'Y-m-d' );
		$from  = gmdate( 'Y-m-d', strtotime( $today . ' -' . ( $days - 1 ) . ' days' ) );
		$pfrom = gmdate( 'Y-m-d', strtotime( $from . ' -' . $days . ' days' ) );
		$pto   = gmdate( 'Y-m-d', strtotime( $from . ' -1 day' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Internal table, all values prepared.
		$daily = $wpdb->get_results( $wpdb->prepare( "SELECT day, event, SUM(hits) AS hits FROM {$table} WHERE day BETWEEN %s AND %s GROUP BY day, event", $pfrom, $today ), ARRAY_A );
		$by    = static function ( $column ) use ( $wpdb, $table, $from, $today ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT {$column} AS k, SUM(hits) AS hits FROM {$table} WHERE event = 'click' AND day BETWEEN %s AND %s GROUP BY {$column} ORDER BY hits DESC", $from, $today ), ARRAY_A );
		};
		$top   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, SUM(CASE WHEN event = 'click' THEN hits ELSE 0 END) AS clicks, SUM(CASE WHEN event = 'view' THEN hits ELSE 0 END) AS views
				FROM {$table} WHERE day BETWEEN %s AND %s AND post_id > 0 GROUP BY post_id ORDER BY clicks DESC LIMIT 10",
				$from,
				$today
			),
			ARRAY_A
		);
		// phpcs:enable

		// Fill every day so charts have no gaps.
		$series = array();
		for ( $t = strtotime( $pfrom ); $t <= strtotime( $today ); $t += DAY_IN_SECONDS ) {
			$series[ gmdate( 'Y-m-d', $t ) ] = array(
				'click' => 0,
				'view'  => 0,
			);
		}
		foreach ( (array) $daily as $row ) {
			if ( isset( $series[ $row['day'] ][ $row['event'] ] ) ) {
				$series[ $row['day'] ][ $row['event'] ] = (int) $row['hits'];
			}
		}
		$current  = array_slice( $series, $days, null, true );
		$previous = array_slice( $series, 0, $days, true );
		$sum      = static function ( $rows, $event ) {
			return array_sum( wp_list_pluck( $rows, $event ) );
		};

		$posts = array();
		foreach ( (array) $top as $row ) {
			$posts[] = array(
				'id'     => (int) $row['post_id'],
				'title'  => html_entity_decode( get_the_title( (int) $row['post_id'] ), ENT_QUOTES, 'UTF-8' ),
				'url'    => get_permalink( (int) $row['post_id'] ),
				'edit'   => get_edit_post_link( (int) $row['post_id'], 'raw' ),
				'clicks' => (int) $row['clicks'],
				'views'  => (int) $row['views'],
			);
		}

		$pairs = static function ( $rows ) {
			$out = array();
			foreach ( (array) $rows as $row ) {
				$out[ (string) $row['k'] ] = (int) $row['hits'];
			}
			return $out;
		};

		$report = array(
			'days'     => $days,
			'from'     => $from,
			'to'       => $today,
			'labels'   => array_keys( $current ),
			'clicks'   => array_values( wp_list_pluck( $current, 'click' ) ),
			'views'    => array_values( wp_list_pluck( $current, 'view' ) ),
			'prev'     => array_values( wp_list_pluck( $previous, 'click' ) ),
			'totals'   => array(
				'clicks'      => $sum( $current, 'click' ),
				'views'       => $sum( $current, 'view' ),
				'prev_clicks' => $sum( $previous, 'click' ),
				'prev_views'  => $sum( $previous, 'view' ),
			),
			'services' => $pairs( $by( 'service' ) ),
			'modes'    => $pairs( $by( 'mode' ) ),
			'devices'  => $pairs( $by( 'device' ) ),
			'posts'    => $posts,
		);

		set_transient( $cache_key, $report, 5 * MINUTE_IN_SECONDS );
		return $report;
	}

	/**
	 * Raw aggregated rows for CSV export.
	 */
	public static function export_rows( $days ) {
		global $wpdb;
		$table = self::table();
		$from  = gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' -' . ( max( 1, (int) $days ) - 1 ) . ' days' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $wpdb->prepare( "SELECT day, post_id, service, mode, device, event, hits FROM {$table} WHERE day >= %s ORDER BY day DESC, hits DESC", $from ), ARRAY_A );
	}
}
