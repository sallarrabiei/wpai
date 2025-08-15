<?php
/**
 * Uninstall cleanup for RAS WP AI.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load WordPress to access options and db.
$opts = get_option( 'raswpai_options', array() );

// Always delete options.
delete_option( 'raswpai_options' );

// Conditionally delete logs.
if ( ! empty( $opts['delete_data_on_uninstall'] ) ) {
	global $wpdb;
	$table = $wpdb->prefix . 'raswpai_logs';
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}