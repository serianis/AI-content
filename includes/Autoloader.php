<?php

namespace AutoblogAI;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Autoloader {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        spl_autoload_register( array( $this, 'autoload' ) );
    }

    public function autoload( $class ) {
        // Only autoload classes from this namespace
        if ( strpos( $class, 'AutoblogAI\\' ) !== 0 ) {
            return;
        }

        // Remove namespace prefix
        $relative_class = str_replace( 'AutoblogAI\\', '', $class );

        // Map namespace to directory structure
        // Example: AutoblogAI\Core\Bootstrap -> includes/Core/Bootstrap.php
        $file = plugin_dir_path( __FILE__ ) . str_replace( '\\', '/', $relative_class ) . '.php';

        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}
