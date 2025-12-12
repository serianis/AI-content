# Generator Pipeline Multi-Locale Parse Validate Publish Queue - Implementation Summary

## Overview
This implementation creates a comprehensive post generator pipeline that orchestrates the complete process of AI-assisted content creation, validation, quality checks, and multi-mode publishing.

## Files Created/Modified

### 1. New Files Created

#### `/includes/class-post-generator.php` (572 lines)
**Main comprehensive post generator class: `AutoblogAI_Post_Generator`**

Public API:
- `generate_and_publish($topic, $keyword='', $args=[])` - Main orchestration method
- `reset_daily_post_count()` - Reset daily post counter

Key Constants:
- Option keys: `OPTION_BANNED_WORDS`, `OPTION_SIMILARITY_THRESHOLD`, `OPTION_MAX_POSTS_PER_DAY`
- Transient key: `TRANSIENT_DAILY_POST_COUNT`
- Error codes: `ERROR_INVALID_RESPONSE`, `ERROR_BANNED_WORDS_DETECTED`, `ERROR_DUPLICATE_CONTENT`, `ERROR_DAILY_CAP_EXCEEDED`, etc.

Features Implemented:
1. **Smart Prompt Assembly**
   - Multi-locale support (EL, EN, custom)
   - Customizable via `autoblogai_prompt_before_assembly` filter
   - Default template enforces structure requirements
   
2. **Structure Enforcement**
   - Title ≤60 characters validation
   - Lede 40–80 word count validation
   - Content body 1400–2000 word range
   - Exactly 5 FAQ pairs validation
   - Meta title/description character limits
   - Required image prompts validation

3. **Response Parsing & Validation**
   - Validates all required JSON fields
   - Enforces character/word count limits
   - Combines lede + content + FAQ into final HTML
   - Returns WP_Error for any validation failures

4. **Duplicate Content Detection**
   - Jaccard similarity algorithm implementation
   - Configurable threshold (default 75%)
   - Checks against 10 recent posts from last 30 days
   - Rejects if similarity exceeds threshold

5. **Banned Words Filtering**
   - Comma-separated word list configuration
   - Case-insensitive matching
   - Flags content before publication
   - Can be disabled (empty word list)

6. **Multi-Publish Modes**
   - Draft (default)
   - Publish (immediate)
   - Scheduled (future publication with date)

7. **Daily Publishing Cap**
   - Default 10 posts/day limit
   - Transient-based counter (no DB writes)
   - Enforces cap on publish/scheduled modes
   - Prevents queue from draining too fast

8. **Image Integration**
   - Hero image generation via `AutoblogAI_Image_Generator`
   - Uses first image prompt from response
   - Sets as featured image
   - Graceful failure handling

9. **JSON-LD Schema Generation**
   - Auto-generates schema.org Article markup
   - Embeds in post content
   - Includes author, dates, description, image ref

10. **SEO Metadata Updates**
    - AutoblogAI custom fields
    - Yoast SEO compatibility
    - Generation timestamp tracking

11. **Action & Filter Hooks**
    - `autoblogai_prompt_before_assembly` - Override entire prompt
    - `autoblogai_prompt_template` - Modify default template
    - `autoblogai_similarity_check_days` - Adjust lookback window
    - `autoblogai_before_publish_post` - Filter parsed content
    - `autoblogai_hero_image_prompt` - Modify image prompt
    - `autoblogai_after_post_created` - Post-creation hook

12. **Comprehensive Logging**
    - Logs all operations via Logger class
    - Status tracking: error, success, rejected, flagged
    - No raw API keys in logs (redacted)
    - Request hash tracking for deduplication

### 2. Files Modified

#### `/includes/Generator/Post.php` (35 lines)
**Refactored to extend AutoblogAI_Post_Generator**

Changes:
- Changed from standalone implementation to inheritance model
- Extends `AutoblogAI_Post_Generator` base class
- Loads base class via require_once
- Maintains backward compatibility via `create_post()` method
- Delegates to `generate_and_publish()` with default arguments
- Kept namespaced wrapper pattern for PSR-4 autoloader

### 3. New Test File

#### `/tests/PostGeneratorPipelineTest.php` (271 lines)
Comprehensive test coverage for new features:

Test Cases:
- Initialization test
- Banned words detection (positive and negative)
- Similarity calculation verification
- Daily post count tracking
- Daily cap enforcement
- Valid response structure validation
- Invalid response handling (missing fields)
- Title length validation
- FAQ structure validation

Helpers:
- Custom `assertWPError()` method
- Uses reflection for private method testing
- Mock dependencies via PHPUnit

### 4. Documentation

#### `/GENERATOR_PIPELINE.md` (350+ lines)
Comprehensive documentation covering:
- Feature overview
- Structure enforcement details
- Configuration options
- Usage examples
- Error handling patterns
- Integration with Scheduler
- Performance notes
- Future enhancement suggestions

## Architecture

### Orchestration Flow
```
Input: topic, keyword, args
  ↓
Check daily cap (if publish/scheduled)
  ↓
Assemble prompt with locale/tone
  ↓
Call Gemini API for JSON response
  ↓
Parse & validate response structure
  ↓
Check for banned words
  ↓
Check for duplicate content (similarity)
  ↓
Fire pre-publish filters
  ↓
Generate hero image (optional)
  ↓
Attach JSON-LD schema
  ↓
Create WordPress post
  ↓
Update SEO metadata
  ↓
Fire post-creation hook
  ↓
Increment daily counter
  ↓
Log success
  ↓
Return post_id
```

## Key Design Decisions

1. **Class Structure**: Base class `AutoblogAI_Post_Generator` with namespaced wrapper `AutoblogAI\Generator\Post` maintains PSR-4 pattern consistency with API Client and Image Generator

2. **Validation Order**: Validates structure before any database operations to fail fast

3. **Similarity Algorithm**: Jaccard (set intersection over union) is lightweight and effective for word-level similarity without requiring ML models

4. **Transient-based Counters**: Uses transients instead of database for daily limits to avoid extra DB writes and enable easy reset

5. **Error Handling**: All failures return WP_Error with specific error codes for caller-side handling

6. **Logging Strategy**: Logs at multiple points (error, rejection, success) with payload redaction

7. **Filter/Action Hooks**: Multiple extension points allow custom prompt templates, content modification, and post-processing

## Configuration

### Required Options
None (all have sensible defaults)

### Optional Options
```php
update_option( 'autoblogai_max_posts_per_day', 10 );
update_option( 'autoblogai_similarity_threshold', 0.75 );
update_option( 'autoblogai_banned_words', 'spam,malware' );
update_option( 'autoblogai_language', 'Greek' );
update_option( 'autoblogai_tone', 'Professional' );
update_option( 'autoblogai_post_status', 'draft' );
```

## Usage Example

```php
$api_client = new AutoblogAI\API\Client();
$image_generator = new AutoblogAI\Generator\Image( $api_client );
$logger = new AutoblogAI\Utils\Logger();

$post_generator = new AutoblogAI\Generator\Post(
    $api_client,
    $image_generator,
    $logger
);

$post_id = $post_generator->generate_and_publish(
    'My Article Topic',
    'focus keyword',
    array(
        'locale' => 'Greek',
        'tone' => 'Professional',
        'publish_mode' => 'publish'
    )
);

if ( is_wp_error( $post_id ) ) {
    error_log( 'Failed: ' . $post_id->get_error_message() );
} else {
    error_log( 'Created post: ' . $post_id );
}
```

## Testing

Run comprehensive tests:
```bash
cd /home/engine/project
vendor/bin/phpunit tests/PostGeneratorPipelineTest.php -v
```

Tests validate:
- Class initialization
- Banned word detection
- Similarity calculations
- Daily cap enforcement
- Response structure validation
- Title/content/FAQ validation

## Backward Compatibility

The implementation maintains full backward compatibility:
- Existing `create_post()` calls continue to work
- Legacy implementation behavior preserved via delegation
- All existing filters/actions remain functional
- Admin class integration unchanged

## Security Considerations

1. **API Key Redaction**: Logger automatically redacts API keys from payloads
2. **SQL Injection**: Uses WordPress prepared statements via `get_posts()` and `wp_insert_post()`
3. **XSS Prevention**: Uses `wp_strip_all_tags()`, `esc_html()`, and `esc_attr()` appropriately
4. **Word Filtering**: Case-insensitive substring matching for banned words
5. **Error Messages**: Never exposes sensitive system information

## Performance

- **Daily Counter**: Transient-based (no database writes)
- **Similarity Checks**: Limited to 10 recent posts, O(n) complexity
- **Word Counting**: Native PHP `str_word_count()` function
- **Image Generation**: Non-blocking, handles failures gracefully
- **Validation**: All checks before database operation

## Future Enhancements

Potential areas for expansion:
- Inline image generation and injection between content sections
- Multiple content variations for A/B testing
- Category/tag auto-assignment based on keywords
- Reading time estimation
- SEO keyword density analysis
- Translation support via external integrations
- Custom post type support
- Excerpt auto-generation
