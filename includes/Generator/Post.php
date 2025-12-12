<?php

namespace AutoblogAI\Generator;

use AutoblogAI\API\Client;
use AutoblogAI\Utils\Logger;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AutoblogAI_Post_Generator' ) ) {
    require_once dirname( __DIR__ ) . '/class-post-generator.php';
}

class Post extends \AutoblogAI_Post_Generator {

    public function __construct( Client $api_client, Image $image_generator, Logger $logger ) {
        parent::__construct( $api_client, $image_generator, $logger );
    }

    /**
     * Legacy method for backward compatibility.
     * Delegates to the new generate_and_publish method with default settings.
     *
     * @param string $topic The topic for the article.
     * @param string $keyword Optional SEO keyword.
     * @return int|WP_Error Post ID on success, WP_Error on failure.
     */
    public function create_post( $topic, $keyword = '' ) {
        return $this->generate_and_publish( $topic, $keyword, array() );
    }
}
