# WordPress Hook/Filter Generator

You need to generate a WordPress hook (action) or filter implementation.

## Steps:

1. Ask the user for:
   - Hook type (action or filter)
   - Hook name (e.g., 'init', 'wp_enqueue_scripts', 'the_content', etc.)
   - What the hook should do
   - Priority (default: 10)
   - Number of accepted arguments (default: 1)

2. Generate a properly structured function that:
   - Follows WordPress naming conventions
   - Uses proper namespacing if the project uses it
   - Includes complete PHPDoc comments
   - Implements security best practices if handling user data
   - Escapes output appropriately
   - Follows WordPress Coding Standards

3. Show how to add the hook using `add_action()` or `add_filter()`

4. Explain:
   - When the hook fires
   - What parameters are available
   - Any important considerations

## Example Output Format:

For an action:
```php
/**
 * Description of what this does.
 *
 * @since 1.0.0
 */
function my_plugin_hook_callback() {
    // Implementation
}
add_action( 'hook_name', 'my_plugin_hook_callback', 10, 1 );
```

For a filter:
```php
/**
 * Description of what this filters.
 *
 * @since 1.0.0
 *
 * @param mixed $value The value to filter.
 * @return mixed The filtered value.
 */
function my_plugin_filter_callback( $value ) {
    // Modify $value
    return $value;
}
add_filter( 'filter_name', 'my_plugin_filter_callback', 10, 1 );
```
