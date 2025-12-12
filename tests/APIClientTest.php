<?php

namespace AutoblogAI\Tests;

use AutoblogAI\API\Client;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/../' );
}

class APIClientTest extends TestCase {

	private $client;

	protected function setUp(): void {
		$this->client = new Client();
	}

	public function test_client_initialization() {
		$this->assertInstanceOf( Client::class, $this->client );
	}

	public function test_missing_api_key_for_text_generation() {
		$prompt = 'Test prompt';
		$result = $this->client->generate_text( $prompt );

		$this->assertWPError( $result );
		$this->assertEquals( 'no_key', $result->get_error_code() );
	}

	public function test_missing_api_key_for_image_generation() {
		$prompt = 'A test image';
		$result = $this->client->generate_image( $prompt );

		$this->assertWPError( $result );
		$this->assertEquals( 'no_key', $result->get_error_code() );
	}

	protected function assertWPError( $thing ) {
		$this->assertInstanceOf( 'WP_Error', $thing );
	}
}
