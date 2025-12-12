<?php
/**
 * Plugin Name: AI content
 * Description: Automatic creation of articles and images using Google Gemini API, SEO optimization and Schema markup.
 * Version: 1.0.0
 * Author: Texnologia
 * Text Domain: autoblogai
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Global constants
define( 'AUTOBLOGAI_VERSION', '1.0.0' );
define( 'AUTOBLOGAI_DB_VERSION', '1.1' ); // Bumped DB version
define( 'AUTOBLOGAI_TABLE_LOGS', 'autoblogai_logs' );
define( 'AUTOBLOGAI_PATH', plugin_dir_path( __FILE__ ) );
define( 'AUTOBLOGAI_URL', plugin_dir_url( __FILE__ ) );

// Load Autoloader
require_once AUTOBLOGAI_PATH . 'includes/Autoloader.php';

AutoblogAI\Autoloader::get_instance();

// Activation Hook
register_activation_hook( __FILE__, 'autoblogai_activate' );

function autoblogai_activate() {
    global $wpdb;
    $table_name      = $wpdb->prefix . AUTOBLOGAI_TABLE_LOGS;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        request_payload text NOT NULL,
        request_hash varchar(64) DEFAULT NULL,
        status varchar(50) NOT NULL,
        response_excerpt text NOT NULL,
        post_id mediumint(9) DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY request_hash (request_hash),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    add_option( 'autoblogai_db_version', AUTOBLOGAI_DB_VERSION );
}

// Deactivation Hook
register_deactivation_hook( __FILE__, 'autoblogai_deactivate' );

function autoblogai_deactivate() {
    $scheduler = new AutoblogAI\Core\Scheduler();
    $scheduler->unschedule_events();
}

// Initialize Plugin
function autoblogai_init() {
    // Text Domain
    load_plugin_textdomain( 'autoblogai', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // Instantiate Core Classes
    $logger          = new AutoblogAI\Utils\Logger();
    $security        = new AutoblogAI\Core\Security();
    $scheduler       = new AutoblogAI\Core\Scheduler();
    $api_client      = new AutoblogAI\API\Client();
    $image_generator = new AutoblogAI\Generator\Image( $api_client );
    $post_generator  = new AutoblogAI\Generator\Post( $api_client, $image_generator, $logger );
    
    // Admin
    if ( is_admin() ) {
        new AutoblogAI\Admin\Admin( $post_generator, $logger, $security );
    }

    // Scheduler
    // Hook scheduler to init or schedule events if needed. 
    // Currently Scheduler only has methods, but if we want to run scheduled tasks, we'd hook them here.
    // For now, we just instantiated it as per requirements.

    // Frontend Schema
    $schema = new AutoblogAI\Frontend\Schema();
    add_action( 'wp_head', array( $schema, 'insert_schema_json_ld' ) );
}

add_action( 'plugins_loaded', 'autoblogai_init' );
