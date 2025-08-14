<?php
/**
 * Plugin Name: RAS WP AI
 * Description: ChatGPT integration via shortcode and REST API with scope control, logging, and admin settings.
 * Version: 1.0.0
 * Author: RAS
 * Text Domain: ras-wp-ai
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants.
if ( ! defined( 'RASWPAI_VERSION' ) ) {
	define( 'RASWPAI_VERSION', '1.0.0' );
}
if ( ! defined( 'RASWPAI_PLUGIN_FILE' ) ) {
	define( 'RASWPAI_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'RASWPAI_PLUGIN_DIR' ) ) {
	define( 'RASWPAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'RASWPAI_PLUGIN_URL' ) ) {
	define( 'RASWPAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Load text domain.
function raswpai_load_textdomain() {
	load_plugin_textdomain( 'ras-wp-ai', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'raswpai_load_textdomain' );

// Includes.
require_once RASWPAI_PLUGIN_DIR . 'includes/class-raswpai-plugin.php';
require_once RASWPAI_PLUGIN_DIR . 'includes/class-raswpai-admin.php';
require_once RASWPAI_PLUGIN_DIR . 'includes/class-raswpai-frontend.php';
require_once RASWPAI_PLUGIN_DIR . 'includes/class-raswpai-rest.php';
require_once RASWPAI_PLUGIN_DIR . 'includes/class-raswpai-logger.php';

// Bootstrap instances.
function raswpai_bootstrap() {
	// Initialize components.
	$plugin   = new raswpai_Plugin();
	$admin    = new raswpai_Admin();
	$frontend = new raswpai_Frontend();
	$rest     = new raswpai_Rest();
	$logger   = new raswpai_Logger();

	$plugin->raswpai_init( $admin, $frontend, $rest, $logger );
}
add_action( 'init', 'raswpai_bootstrap', 5 );

// Activation / Deactivation hooks.
function raswpai_activate() {
	// Create DB table for logs and schedule cleanup.
	raswpai_Logger::raswpai_create_table();
	// Schedule daily cleanup if not scheduled.
	if ( ! wp_next_scheduled( 'raswpai_cleanup_logs_event' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'raswpai_cleanup_logs_event' );
	}
}
register_activation_hook( __FILE__, 'raswpai_activate' );

function raswpai_deactivate() {
	// Clear scheduled event.
	$timestamp = wp_next_scheduled( 'raswpai_cleanup_logs_event' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'raswpai_cleanup_logs_event' );
	}
}
register_deactivation_hook( __FILE__, 'raswpai_deactivate' );

// Cron event callback.
add_action( 'raswpai_cleanup_logs_event', array( 'raswpai_Logger', 'raswpai_purge_old_logs' ) );