<?php

namespace AutoblogAI\API;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Client {
	private $api_key;
	private $base_url = 'https://generativelanguage.googleapis.com/v1beta/models/';

	public function __construct() {
		$this->api_key = get_option( 'autoblogai_api_key' );
	}

	public function generate_text( $prompt ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'no_key', 'Missing Gemini API Key' );
		}

		// Using Gemini 2.0 Flash or Pro depending on availability, falling back to 1.5
		$model = 'gemini-2.0-flash-exp';
		$url   = $this->base_url . $model . ':generateContent?key=' . $this->api_key;

		$body = array(
			'contents'         => array(
				array( 'parts' => array( array( 'text' => $prompt ) ) ),
			),
			'generationConfig' => array(
				'temperature'      => 0.7,
				'responseMimeType' => 'application/json', // Force JSON
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'body'    => json_encode( $body ),
				'headers' => array( 'Content-Type' => 'application/json' ),
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $data['error'] ) ) {
			return new WP_Error( 'api_error', $data['error']['message'] );
		}

		if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			$raw_json = $data['candidates'][0]['content']['parts'][0]['text'];
			// Clean up any potential markdown wrapping
			$raw_json = str_replace( array( '```json', '```' ), '', $raw_json );
			return json_decode( $raw_json, true );
		}

		return new WP_Error( 'parse_error', 'Could not parse Gemini response' );
	}

	public function generate_image( $image_prompt ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'no_key', 'Missing API Key' );
		}

		// Note: Imagen 3 API integration via Gemini endpoint
		// If not available on the key, we might need a fallback.
		// For this demo, we assume the user has access to Imagen-3 or similar capability via API.

		$model = 'imagen-3.0-generate-001';
		$url   = $this->base_url . $model . ':predict?key=' . $this->api_key;

		$body = array(
			'instances'  => array(
				array( 'prompt' => $image_prompt ),
			),
			'parameters' => array(
				'sampleCount' => 1,
				'aspectRatio' => '16:9',
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'body'    => json_encode( $body ),
				'headers' => array( 'Content-Type' => 'application/json' ),
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $data['predictions'][0]['bytesBase64Encoded'] ) ) {
			return $data['predictions'][0]['bytesBase64Encoded'];
		}

		// Fallback: If image gen fails or model not found, return error so we skip image
		return new WP_Error( 'img_api_error', 'Image generation failed or model not available.' );
	}
}
