<?php

namespace AutoblogAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Security {

	public function verify_nonce( $action, $query_arg = '_wpnonce' ) {
		return check_ajax_referer( $action, $query_arg, false );
	}

	public function current_user_can( $capability ) {
		return current_user_can( $capability );
	}

	public function create_nonce( $action ) {
		return wp_create_nonce( $action );
	}
}
