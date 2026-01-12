# WP Content Manager Plugin

A comprehensive WordPress plugin for managing and displaying promotional blocks with advanced features including caching, AJAX loading, and REST API support.

## Features

- Custom Post Type for Promo Blocks with rich content support
- Admin configuration panel with toggle controls
- Multiple display methods: Shortcode and REST API
- Performance optimization with caching and lazy loading
- AJAX loading support
- Comprehensive security implementation
- Translation ready

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings under "Settings → Dynamic Content"
4. Create promo blocks under "Promo Blocks" in the admin menu

## Usage

### Shortcode
Use `[dynamic_promo]` in any post, page, or widget area to display promo blocks.

### REST API
Access promo blocks via REST API at `/wp-json/dcm/v1/promos`

Optional parameters:
- `limit`: Number of promo blocks to return (default: 5, max: 50)

### Configuration
1. Go to "Settings → Dynamic Content"
2. Toggle feature on/off
3. Set maximum number of blocks to display
4. Configure cache TTL
5. Enable/disable AJAX loading

## Architecture

### Key Components

1. **Post Type Handler**: Manages the Promo Block custom post type and meta fields
2. **Settings Manager**: Handles admin configuration with proper sanitization
3. **Frontend Renderer**: Manages shortcode rendering and conditional asset loading
4. **Cache System**: Implements object caching with automatic invalidation
5. **REST API**: Provides JSON endpoint for promo blocks
6. **AJAX Handler**: Manages asynchronous loading of content

### Performance Optimizations

1. **Object Caching**: Uses WordPress object cache with configurable TTL
2. **Conditional Asset Loading**: CSS/JS only loads where shortcode is used
3. **Lazy Loading**: Images load lazily to improve initial page load
4. **Query Optimization**: Efficient meta queries with proper indexing
5. **Cache Invalidation**: Automatic cache clearing on post save/update

## Security Measures

- Nonce verification for all form submissions and AJAX requests
- Capability checks for admin operations
- Data sanitization and validation for all inputs
- Proper escaping for all outputs
- Secure REST API endpoint with permission checks

## Assumptions and Trade-offs

### Assumptions
1. WordPress 5.0+ with REST API enabled
2. Object caching available (either through persistent cache or transients)
3. Modern browser support for JavaScript features used
4. PHP 8.3+ for proper type hints and security features

### Trade-offs
1. **Caching Strategy**: Using WordPress object cache which may vary by hosting environment
2. **AJAX Fallback**: If JavaScript is disabled, AJAX loading gracefully degrades
3. **Image Handling**: Uses WordPress native image sizes for simplicity
4. **Error Handling**: Simplified error states for cleaner user experience

## Bonus Features

### WP-CLI Command
Clear cache using WP-CLI:
```bash
wp wpcmp cache clear