<?php

namespace AutoblogAI\Generator;

use AutoblogAI\API\Client;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Image {

	private $api_client;

	public function __construct( Client $api_client ) {
		$this->api_client = $api_client;
	}

	public function generate_and_upload( $prompt, $title ) {
		$base64_or_error = $this->api_client->generate_image( $prompt );

		if ( is_wp_error( $base64_or_error ) ) {
			return $base64_or_error;
		}

		$img = base64_decode( $base64_or_error );
		$upload_dir = wp_upload_dir();
		$filename   = 'gen_' . uniqid() . '.png';
		$file_path  = $upload_dir['path'] . '/' . $filename;
		$file_url   = $upload_dir['url'] . '/' . $filename;

		if ( ! file_put_contents( $file_path, $img ) ) {
			return new WP_Error( 'file_save_error', 'Could not save generated image.' );
		}

		return $this->upload_image_from_url( $file_url, $title );
	}

	private function upload_image_from_url( $url, $title ) {
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		// Download to temp
		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$file_array = array(
			'name'     => sanitize_title( $title ) . '.png', // Assuming PNG from Imagen
			'tmp_name' => $tmp,
		);

		// Upload to media library
		$id = media_handle_sideload( $file_array, 0 );

		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			return $id;
		}

		// Update Alt Text
		update_post_meta( $id, '_wp_attachment_image_alt', $title );

		return $id;
	}
}
