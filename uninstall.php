<?php
/**
 * Removes every trace of the plugin when it is deleted from the Plugins screen.
 *
 * @package SummarizeWithAI
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'swai_settings' );
delete_option( 'swai_db_version' );
delete_transient( 'swai_llms_txt' );
foreach ( array( 7, 30, 90 ) as $swai_days ) {
	delete_transient( 'swai_report_' . $swai_days );
}
wp_clear_scheduled_hook( 'swai_prune_stats' );

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}swai_stats" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_swai_disable' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_swai_prompt' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
