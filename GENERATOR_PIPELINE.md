# AutoblogAI Post Generator Pipeline

## Overview

The `AutoblogAI_Post_Generator` class implements a comprehensive content generation pipeline that orchestrates prompt assembly, API calls to Google Gemini, response parsing and validation, duplicate content detection, banned word filtering, and multi-locale support for WordPress post creation.

## Features

### 1. Smart Prompt Assembly
- **Multi-locale support**: EL (Greek) and EN (English) or any custom locale
- **Structured output format**: Enforces JSON with specific fields and constraints
- **Configurable tone**: Professional, Casual, Journalistic
- **Customizable templates**: Via `autoblogai_prompt_before_assembly` and `autoblogai_prompt_template` filters

### 2. Content Structure Enforcement
The generator requires and validates:

- **Title**: ≤60 characters, SEO-optimized
- **Lede (Introduction)**: 40–80 words, engaging hook
- **Content Body**: 1,400–2,000 words with:
  - Multiple H2 subheadings
  - H3 sub-sections
  - Bullet points and lists
  - Clear transitions
- **Call-to-Action (CTA)**: Reader engagement prompt
- **FAQ Section**: Exactly 5 question-answer pairs
- **Meta Information**:
  - Meta title: ≤60 characters
  - Meta description: ≤160 characters
  - Keywords: 5–10 comma-separated
  - Image prompts: Multiple suggestions for hero and inline images

### 3. Response Parsing & Validation
- Validates all required fields are present and correct
- Enforces character/word count limits
- Validates FAQ structure (exactly 5 pairs)
- Checks image prompt availability
- Combines lede, content, and FAQ into final HTML

### 4. Duplicate Content Detection
- **Jaccard similarity algorithm**: Compares word sets between new and recent posts
- **Configurable threshold**: Default 75% (via `autoblogai_similarity_threshold` option)
- **Time-window based**: Checks last 30 days of posts (configurable via `autoblogai_similarity_check_days` filter)
- **Rejects similar posts**: Returns error without logging as publishable

### 5. Banned Words Filtering
- **Configurable word list**: Via `autoblogai_banned_words` option (comma-separated)
- **Case-insensitive matching**: Detects banned words regardless of case
- **Flags content**: Logs as "flagged" status when banned words detected
- **Prevents publication**: Rejects posts before final publishing

### 6. Multi-Publish Modes
- **Draft** (default): Creates posts as draft status
- **Publish** (immediate): Publishes posts immediately
- **Scheduled**: Schedules posts for future publication with `post_date`

### 7. Daily Publishing Cap
- **Limit enforcement**: Default 10 posts/day (via `autoblogai_max_posts_per_day` option)
- **Transient-based tracking**: Uses `autoblogai_daily_post_count` transient
- **Increments automatically**: When posts are published or scheduled
- **Resets daily**: Transient expires after 24 hours

### 8. Hero Image Generation & Injection
- Integrates with `AutoblogAI_Image_Generator`
- Uses first image prompt for hero image
- Automatically sets as featured image
- Gracefully handles image generation failures

### 9. JSON-LD Article Schema
- Auto-generates Article schema.org markup
- Includes:
  - Headline, description, author information
  - Publication and modification dates
  - Image reference
  - Main entity of page
- Embeds in post content before saving

### 10. SEO Metadata Updates
- **AutoblogAI custom fields**:
  - `_autoblogai_meta_title`
  - `_autoblogai_meta_description`
  - `_autoblogai_keywords`
  - `_autoblogai_generated` (flag)
  - `_autoblogai_generated_at` (timestamp)
- **Yoast SEO compatibility**:
  - `_yoast_wpseo_title`
  - `_yoast_wpseo_metadesc`

### 11. Actions & Filters
Extensibility points for content modification:

- **`autoblogai_prompt_before_assembly`**: Override prompt completely
- **`autoblogai_prompt_template`**: Modify default prompt template
- **`autoblogai_similarity_check_days`**: Adjust lookback window (default: 30)
- **`autoblogai_before_publish_post`**: Filter parsed content before image generation
- **`autoblogai_hero_image_prompt`**: Modify hero image prompt
- **`autoblogai_after_post_created`**: Hook after post creation
- **`autoblogai_image_prompt`** (from Image Generator): Modify image prompts

### 12. Comprehensive Logging
- Logs all operations via `AutoblogAI\Utils\Logger`
- Tracks status: error, success, rejected, flagged
- Includes request payload (with API keys redacted)
- Records post IDs for successful publications
- No raw API keys stored in logs

## Usage

### Basic Generation (Draft)
```php
$api_client = new AutoblogAI\API\Client();
$image_generator = new AutoblogAI\Generator\Image( $api_client );
$logger = new AutoblogAI\Utils\Logger();

$post_generator = new AutoblogAI_Post_Generator( 
    $api_client, 
    $image_generator, 
    $logger 
);

$post_id = $post_generator->generate_and_publish( 
    'Machine Learning Basics',
    'machine learning',
    array(
        'locale' => 'English',
        'tone' => 'Professional',
        'publish_mode' => 'draft'
    )
);

if ( is_wp_error( $post_id ) ) {
    echo 'Error: ' . $post_id->get_error_message();
} else {
    echo 'Post created: ' . $post_id;
}
```

### Immediate Publication
```php
$post_id = $post_generator->generate_and_publish( 
    'AI Trends 2024',
    'artificial intelligence',
    array(
        'publish_mode' => 'publish'
    )
);
```

### Scheduled Publication
```php
$scheduled_date = date( 'Y-m-d H:i:s', strtotime( '+1 week' ) );

$post_id = $post_generator->generate_and_publish( 
    'AI Ethics',
    'ethics artificial intelligence',
    array(
        'publish_mode' => 'scheduled',
        'scheduled_at' => $scheduled_date
    )
);
```

### Via Legacy Method (Backward Compatible)
```php
// Uses default settings
$post_id = $post_generator->create_post( 
    'My Topic',
    'my keyword'
);
```

## Configuration Options

### WordPress Options (Set via `update_option`)

```php
// Daily publishing limit (default: 10)
update_option( 'autoblogai_max_posts_per_day', 5 );

// Similarity threshold for duplicate detection (default: 0.75)
update_option( 'autoblogai_similarity_threshold', 0.80 );

// Banned words (comma-separated)
update_option( 'autoblogai_banned_words', 'spam,malware,scam' );

// Language/locale (default: 'Greek')
update_option( 'autoblogai_language', 'English' );

// Tone of voice (default: 'Professional')
update_option( 'autoblogai_tone', 'Casual' );

// Default publish mode (default: 'draft')
update_option( 'autoblogai_post_status', 'publish' );
```

## Error Handling

The class returns `WP_Error` objects for all failures:

```php
// Constants for error codes
AutoblogAI_Post_Generator::ERROR_FAILED_PROMPT_ASSEMBLY    // Prompt assembly failed
AutoblogAI_Post_Generator::ERROR_INVALID_RESPONSE          // API response invalid
AutoblogAI_Post_Generator::ERROR_BANNED_WORDS_DETECTED     // Banned words found
AutoblogAI_Post_Generator::ERROR_DUPLICATE_CONTENT         // Too similar to existing
AutoblogAI_Post_Generator::ERROR_DAILY_CAP_EXCEEDED        // Publishing limit reached
AutoblogAI_Post_Generator::ERROR_POST_CREATION_FAILED      // WordPress post creation failed
```

### Example Error Handling
```php
$post_id = $post_generator->generate_and_publish( $topic, $keyword );

if ( is_wp_error( $post_id ) ) {
    $code = $post_id->get_error_code();
    $message = $post_id->get_error_message();
    
    error_log( "Generation failed ($code): $message" );
} else {
    error_log( "Post created successfully: $post_id" );
}
```

## Internal Methods

### Public Methods
- `generate_and_publish( $topic, $keyword = '', $args = array() )`: Main orchestration method
- `reset_daily_post_count()`: Reset daily post counter (call at midnight)

### Private Methods (for extension/testing)
- `assemble_prompt()`: Build the Gemini prompt
- `parse_and_validate_response()`: Validate API response structure
- `combine_content_with_faq()`: Combine main content with FAQ section
- `check_banned_words()`: Filter banned words
- `check_duplicate_content()`: Detect duplicate content
- `calculate_similarity()`: Calculate Jaccard similarity
- `attach_json_ld_schema()`: Embed JSON-LD markup
- `update_seo_metadata()`: Save SEO fields
- `check_daily_publishing_cap()`: Enforce daily limit
- `get_daily_post_count()`: Get today's post count
- `increment_daily_post_count()`: Increment post counter

## Testing

Run the test suite:
```bash
vendor/bin/phpunit tests/PostGeneratorPipelineTest.php
```

Tests cover:
- Initialization
- Banned word detection
- Similarity calculation
- Daily cap enforcement
- Response structure validation
- Title length validation
- FAQ structure validation
- Valid content acceptance

## Integration with Scheduler

The class works seamlessly with the `AutoblogAI\Core\Scheduler` for automated publishing:

```php
$scheduler = new AutoblogAI\Core\Scheduler();

// Enqueue topic for scheduled processing
$scheduler->enqueue_topic( 'My Topic', 'my keyword' );

// In wp_cron hook:
add_action( 'autoblogai_daily_publish_event', function() {
    $topic = $scheduler->dequeue_topic();
    if ( $topic ) {
        $post_generator->generate_and_publish( 
            $topic['topic'], 
            $topic['keyword'] 
        );
    }
} );
```

## Security Considerations

1. **API Key Redaction**: All logging strips API keys (uses regex patterns)
2. **User Capability**: Admin checks should be performed by calling code
3. **Nonce Verification**: Should be enforced at AJAX/form submission level
4. **SQL Injection Prevention**: Uses `get_posts()` and `wp_insert_post()` with proper escaping
5. **XSS Prevention**: Uses `wp_strip_all_tags()` and `esc_html()` for user-facing content

## Performance Notes

1. **Similarity Checks**: Limited to 10 recent posts from last 30 days
2. **Word Counting**: Uses native PHP `str_word_count()` function
3. **Transient-based Caps**: Uses WordPress transients for daily counters (no database writes)
4. **Parallel Image Generation**: Image generation is asynchronous (non-blocking)

## Future Enhancements

Possible extensions:
- Category/tag assignment
- Custom post type support
- Inline image generation and injection
- Excerpt auto-generation
- Reading time estimation
- SEO keyword density analysis
- Multiple content variations
- Translation support via integrations
