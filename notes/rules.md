# WordPress Plugin Development Rules

## Code Standards

### Naming Conventions
- **Functions:** `my_plugin_function_name()` - lowercase with underscores
- **Classes:** `My_Plugin_Class_Name` - PascalCase with underscores
- **Variables:** `$my_variable` - lowercase with underscores
- **Constants:** `MY_PLUGIN_CONSTANT` - uppercase with underscores
- **File names:** `class-my-plugin.php` - lowercase with hyphens

### Prefixing
- All global functions MUST be prefixed with unique plugin identifier
- All global variables MUST be prefixed
- All constants MUST be prefixed
- Database table names MUST use `$wpdb->prefix` and be prefixed with plugin name

### Code Organization
- Use namespaces when possible: `namespace MyPlugin\Feature;`
- Implement Composer autoloading for PSR-4
- One class per file
- File name matches class name (e.g., `class My_Feature` → `class-my-feature.php`)

## Security Requirements

### Input Sanitization (ALWAYS)
```php
// Text input
$value = sanitize_text_field( $_POST['field'] );

// Email
$email = sanitize_email( $_POST['email'] );

// URL
$url = esc_url_raw( $_POST['url'] );

// Integer
$number = absint( $_POST['number'] );

// Textarea
$text = sanitize_textarea_field( $_POST['textarea'] );

// HTML (only allowed tags)
$html = wp_kses_post( $_POST['content'] );
```

### Output Escaping (ALWAYS)
```php
// HTML content
echo esc_html( $content );

// HTML attribute
echo '<div class="' . esc_attr( $class ) . '">';

// URL
echo '<a href="' . esc_url( $link ) . '">';

// JavaScript
echo '<script>var x = ' . esc_js( $value ) . ';</script>';
```

### Nonce Verification (ALWAYS for forms)
```php
// Create nonce
wp_nonce_field( 'my_action', 'my_nonce' );

// Verify nonce
if ( ! isset( $_POST['my_nonce'] ) || ! wp_verify_nonce( $_POST['my_nonce'], 'my_action' ) ) {
    wp_die( 'Security check failed' );
}

// For AJAX
check_ajax_referer( 'my_ajax_nonce', 'nonce' );
```

### Capability Checks (ALWAYS for privileged actions)
```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Unauthorized access' );
}
```

### Database Queries (ALWAYS use prepared statements)
```php
global $wpdb;

// Prepared statement
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}table WHERE column = %s AND id = %d",
        $string_value,
        $int_value
    )
);

// NEVER do this
$wpdb->query( "SELECT * FROM table WHERE id = {$_GET['id']}" ); // WRONG!
```

## WordPress Best Practices

### Never Modify Core
- NEVER modify WordPress core files
- NEVER modify other plugins
- NEVER modify themes directly (use child themes)
- Use hooks and filters instead

### Use WordPress Functions
- Use `wp_remote_get()` instead of `curl` or `file_get_contents()`
- Use `wp_insert_post()` instead of direct database inserts
- Use `wp_mail()` instead of PHP `mail()`
- Use `wp_safe_redirect()` for redirects
- Use `wp_enqueue_script()` and `wp_enqueue_style()` for assets

### Enqueue Assets Properly
```php
add_action( 'wp_enqueue_scripts', 'my_plugin_enqueue_assets' );
function my_plugin_enqueue_assets() {
    // CSS
    wp_enqueue_style(
        'my-plugin-style',
        plugins_url( 'assets/css/style.css', __FILE__ ),
        array(),
        '1.0.0'
    );

    // JavaScript
    wp_enqueue_script(
        'my-plugin-script',
        plugins_url( 'assets/js/script.js', __FILE__ ),
        array( 'jquery' ),
        '1.0.0',
        true // Load in footer
    );
}
```

### Internationalization (i18n)
```php
// Translatable strings
__( 'Text', 'my-plugin' );
_e( 'Text', 'my-plugin' );
_x( 'Text', 'Context', 'my-plugin' );
esc_html__( 'Text', 'my-plugin' );
esc_html_e( 'Text', 'my-plugin' );

// Never do this
echo 'Hard-coded text'; // WRONG!
```

### Error Handling
```php
// Development (wp-config.php)
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

// Logging
error_log( 'Debug message: ' . print_r( $data, true ) );

// Never use var_dump or print_r in production
```

## Performance

### Database Optimization
- Minimize database queries
- Use transients for caching
- Avoid queries in loops
- Use `WP_Query` efficiently
- Clean up on uninstall

```php
// Caching with transients
$data = get_transient( 'my_plugin_data' );
if ( false === $data ) {
    $data = expensive_function();
    set_transient( 'my_plugin_data', $data, HOUR_IN_SECONDS );
}
```

### Asset Optimization
- Minify CSS and JavaScript
- Conditional loading (only load where needed)
- Combine files when possible
- Use WordPress's built-in libraries (jQuery, etc.)

## File Structure

```
my-plugin/
├── my-plugin.php              # Main plugin file
├── uninstall.php              # Uninstall cleanup
├── readme.txt                 # WordPress readme
├── composer.json              # Composer dependencies
├── includes/                  # PHP classes
│   ├── class-main.php
│   ├── class-admin.php
│   └── class-frontend.php
├── admin/                     # Admin-specific files
│   ├── class-settings.php
│   └── views/
├── public/                    # Frontend-specific files
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── languages/                 # Translation files
└── tests/                     # Unit tests
```

## Documentation

### PHPDoc Comments (Required)
```php
/**
 * Brief description.
 *
 * Longer description if needed.
 *
 * @since 1.0.0
 *
 * @param string $param Description of parameter.
 * @param int    $number Description of parameter.
 * @return bool Description of return value.
 */
function my_plugin_function( $param, $number ) {
    // Code
}
```

### File Headers
```php
<?php
/**
 * Class Name
 *
 * Description of the class.
 *
 * @package    My_Plugin
 * @subpackage My_Plugin/includes
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

## Testing

### Development Environment
- Always test with `WP_DEBUG` enabled
- Test with latest WordPress version
- Test with PHP 7.4+ and 8.0+
- Test with common themes
- Test with common plugins

### Before Release
- [ ] Security audit completed
- [ ] All inputs sanitized
- [ ] All outputs escaped
- [ ] Nonces implemented
- [ ] Capability checks in place
- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities
- [ ] Code follows WPCS
- [ ] Translation ready
- [ ] Tested with WP_DEBUG
- [ ] No PHP warnings/errors
- [ ] Assets enqueued properly
- [ ] Uninstall cleanup works

## Forbidden Practices

### NEVER DO THESE:
1. ❌ Modify WordPress core files
2. ❌ Use `$_GET`, `$_POST` without sanitization
3. ❌ Echo output without escaping
4. ❌ Execute database queries without prepared statements
5. ❌ Use `is_admin()` for security checks
6. ❌ Hardcode database table prefixes
7. ❌ Skip nonce verification
8. ❌ Skip capability checks
9. ❌ Use global namespace without prefix
10. ❌ Include files with user input paths
11. ❌ Use `eval()`, `system()`, `exec()` with user input
12. ❌ Store sensitive data unencrypted
13. ❌ Output debug info in production
14. ❌ Use deprecated WordPress functions

## Additional Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Security Whitepaper](https://wordpress.org/about/security/)
- [Plugin Review Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
