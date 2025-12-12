<?php
/**
 * Bootstrap file for PHPUnit tests
 */

// Define WordPress constants if not already defined
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/../' );
}

if ( ! defined( 'AUTOBLOGAI_PATH' ) ) {
	define( 'AUTOBLOGAI_PATH', ABSPATH . 'includes/' );
}

if ( ! defined( 'AUTOBLOGAI_VERSION' ) ) {
	define( 'AUTOBLOGAI_VERSION', '1.0.0' );
}

if ( ! defined( 'AUTOBLOGAI_TABLE_LOGS' ) ) {
	define( 'AUTOBLOGAI_TABLE_LOGS', 'autoblogai_logs' );
}

// Load the autoloader
require_once ABSPATH . 'includes/Autoloader.php';

// Initialize autoloader
AutoblogAI\Autoloader::get_instance();

// Mock WordPress functions for unit testing
if ( ! function_exists( 'get_option' ) ) {
	$GLOBALS['mock_options'] = array();

	function get_option( $option, $default = false ) {
		if ( isset( $GLOBALS['mock_options'][ $option ] ) ) {
			return $GLOBALS['mock_options'][ $option ];
		}
		return $default;
	}

	function update_option( $option, $value ) {
		$GLOBALS['mock_options'][ $option ] = $value;
		return true;
	}

	function delete_option( $option ) {
		unset( $GLOBALS['mock_options'][ $option ] );
		return true;
	}
}

if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		if ( $special_chars ) {
			$chars .= '!@#$%^&*()';
		}
		$result = '';
		for ( $i = 0; $i < $length; $i++ ) {
			$result .= $chars[ rand( 0, strlen( $chars ) - 1 ) ];
		}
		return $result;
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type = 'mysql', $gmt = 0 ) {
		if ( 'mysql' === $type ) {
			return gmdate( 'Y-m-d H:i:s' );
		}
		return gmdate( 'U' );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $text ) {
		return trim( strip_tags( $text ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return filter_var( $url, FILTER_VALIDATE_URL );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( $email, FILTER_VALIDATE_EMAIL );
	}
}

if ( ! function_exists( 'check_ajax_referer' ) ) {
	function check_ajax_referer( $action = -1, $query_arg = '_wpnonce', $die = true ) {
		return true;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		return false;
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		return wp_hash( $action . gmdate( 'Y-m-d H:i' ) . wp_salt(), 'nonce' );
	}
}

if ( ! function_exists( 'wp_hash' ) ) {
	function wp_hash( $data, $scheme = 'auth' ) {
		return hash_hmac( 'sha256', $data, wp_salt() );
	}
}

if ( ! function_exists( 'wp_salt' ) ) {
	function wp_salt( $scheme = 'auth' ) {
		return 'put your unique phrase here';
	}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook, $args = array() ) {
		return false;
	}
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( $timestamp, $recurrence, $hook, $args = array() ) {
		return true;
	}
}

if ( ! function_exists( 'wp_unschedule_event' ) ) {
	function wp_unschedule_event( $timestamp, $hook, $args = array() ) {
		return true;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {
		return null;
	}
}

// Define WP_Error if not available
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $errors = array();
		public $error_data = array();

		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( empty( $code ) ) {
				return;
			}

			$this->errors[ $code ][] = $message;

			if ( ! empty( $data ) ) {
				$this->error_data[ $code ] = $data;
			}
		}

		public function get_error_message( $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}

			if ( isset( $this->errors[ $code ][0] ) ) {
				return $this->errors[ $code ][0];
			}

			return '';
		}

		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return isset( $codes[0] ) ? $codes[0] : '';
		}
	}
}
