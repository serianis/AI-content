<?php

namespace AutoblogAI\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logger {

	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'autoblogai_logs';
	}

	public function log( $request_payload, $response_excerpt, $status, $post_id = null ) {
		global $wpdb;

		$wpdb->insert(
			$this->table_name,
			array(
				'created_at'       => current_time( 'mysql' ),
				'request_payload'  => is_array( $request_payload ) || is_object( $request_payload ) ? json_encode( $request_payload ) : $request_payload,
				'response_excerpt' => $response_excerpt,
				'status'           => $status,
				'post_id'          => $post_id,
			)
		);
	}

	public function get_logs( $limit = 50 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d", $limit ) );
	}
}
