<?php
/**
 * Handlers for admin-post.php actions: CSV export, settings import/reset, analytics reset.
 * Every handler checks capability and a nonce before doing anything.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI\Admin;

use SummarizeWithAI\Analytics\Store;
use SummarizeWithAI\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Export, import and reset handlers.
 */
final class Tools {

	const MAX_IMPORT_BYTES = 262144;

	/**
	 * Hook the component into WordPress.
	 */
	public static function register() {
		add_action( 'admin_post_swai_export_csv', array( __CLASS__, 'export_csv' ) );
		add_action( 'admin_post_swai_import', array( __CLASS__, 'import' ) );
		add_action( 'admin_post_swai_reset', array( __CLASS__, 'reset' ) );
		add_action( 'admin_post_swai_reset_stats', array( __CLASS__, 'reset_stats' ) );
	}

	/**
	 * Stop unless the user may manage options and the nonce is valid.
	 *
	 * @param string $action Nonce action.
	 */
	private static function guard( $action ) {
		if ( ! current_user_can( Admin::CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'summarize-with-ai' ), 403 );
		}
		check_admin_referer( $action );
	}

	/**
	 * Redirect back to the Tools section with a status flag.
	 *
	 * @param string $flag Query flag to add.
	 */
	private static function back( $flag ) {
		wp_safe_redirect( add_query_arg( $flag, 1, admin_url( 'admin.php?page=' . Admin::SLUG_SETTING . '#tools' ) ) );
		exit;
	}

	/**
	 * Stream analytics as CSV.
	 */
	public static function export_csv() {
		self::guard( 'swai_export_csv' );
		$days = isset( $_GET['range'] ) ? absint( $_GET['range'] ) : 30; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce checked in guard().
		$rows = Store::export_rows( $days );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=summarize-with-ai-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming to output.
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- UTF-8 BOM so Excel reads non-Latin titles.
		fputcsv( $out, array( 'date', 'post_id', 'post_title', 'service', 'mode', 'device', 'event', 'count' ) );
		foreach ( $rows as $row ) {
			fputcsv(
				$out,
				array(
					$row['day'],
					$row['post_id'],
					self::csv_safe( $row['post_id'] ? get_the_title( (int) $row['post_id'] ) : '' ),
					$row['service'],
					$row['mode'],
					$row['device'],
					$row['event'],
					$row['hits'],
				)
			);
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Neutralize spreadsheet formula injection in user-controlled cells.
	 *
	 * @param string $value Cell value.
	 */
	private static function csv_safe( $value ) {
		$value = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
		return preg_match( '/^[=+\-@\t\r]/', $value ) ? "'" . $value : $value;
	}

	/**
	 * Import settings from an uploaded JSON file.
	 */
	public static function import() {
		self::guard( 'swai_import' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked in guard(); file array validated below.
		$file = isset( $_FILES['swai_file'] ) ? $_FILES['swai_file'] : null;
		if ( ! $file || UPLOAD_ERR_OK !== $file['error'] || $file['size'] > self::MAX_IMPORT_BYTES || ! is_uploaded_file( $file['tmp_name'] ) ) {
			self::back( 'swai-error' );
		}
		$data = json_decode( (string) file_get_contents( $file['tmp_name'] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local uploaded temp file.
		if ( ! is_array( $data ) || ! isset( $data['enabled'] ) ) {
			self::back( 'swai-error' );
		}

		// Runs through Settings::sanitize() via the registered setting.
		update_option( Settings::OPTION, $data );
		self::back( 'swai-imported' );
	}

	/**
	 * Restore default settings.
	 */
	public static function reset() {
		self::guard( 'swai_reset' );
		delete_option( Settings::OPTION );
		Settings::flush();
		self::back( 'swai-reset' );
	}

	/**
	 * Delete all analytics data.
	 */
	public static function reset_stats() {
		self::guard( 'swai_reset_stats' );
		Store::reset();
		foreach ( Dashboard::RANGES as $days ) {
			delete_transient( 'swai_report_' . $days );
		}
		self::back( 'swai-stats-reset' );
	}
}
