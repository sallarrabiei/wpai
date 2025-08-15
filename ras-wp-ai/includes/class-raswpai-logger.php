<?php
/**
 * Logging utilities for RAS WP AI.
 *
 * @package ras-wp-ai
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class raswpai_Logger
 */
class raswpai_Logger {
	/**
	 * Init hook placeholder (may be expanded later).
	 */
	public function raswpai_init() {}

	/**
	 * Create DB table for logs using dbDelta.
	 */
	public static function raswpai_create_table() {
		global $wpdb;
		$table_name = self::raswpai_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_id varchar(64) NOT NULL,
			user_id bigint(20) unsigned NULL,
			ip_hash varchar(64) NULL,
			role varchar(20) NOT NULL,
			content longtext NULL,
			created_at datetime NOT NULL,
			meta longtext NULL,
			PRIMARY KEY  (id),
			KEY session_idx (session_id),
			KEY created_idx (created_at)
		) {$charset_collate};";
		dbDelta( $sql );
	}

	/**
	 * Full table name.
	 *
	 * @return string
	 */
	public static function raswpai_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'raswpai_logs';
	}

	/**
	 * Write a log row.
	 *
	 * @param string $session_id Session.
	 * @param string $role       Role.
	 * @param string $content    Content.
	 * @param array  $meta       Meta.
	 */
	public static function raswpai_log( $session_id, $role, $content, $meta = array() ) {
		global $wpdb;
		$table = self::raswpai_table_name();
		$wpdb->insert(
			$table,
			array(
				'session_id' => substr( sanitize_text_field( $session_id ), 0, 64 ),
				'user_id'    => isset( $meta['user_id'] ) ? intval( $meta['user_id'] ) : null,
				'ip_hash'    => isset( $meta['ip_hash'] ) ? sanitize_text_field( $meta['ip_hash'] ) : null,
				'role'       => substr( sanitize_key( $role ), 0, 20 ),
				'content'    => $content,
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
				'meta'       => ! empty( $meta ) ? wp_json_encode( $meta ) : null,
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Purge logs older than retention days (from options).
	 */
	public static function raswpai_purge_old_logs() {
		$options = raswpai_Plugin::raswpai_get_options();
		$days    = max( 1, intval( $options['retention_days'] ) );
		$cutoff  = gmdate( 'Y-m-d H:i:s', time() - ( DAY_IN_SECONDS * $days ) );

		global $wpdb;
		$table = self::raswpai_table_name();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) );
	}
}