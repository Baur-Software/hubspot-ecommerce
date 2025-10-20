# WordPress AJAX Handler Generator

You need to generate a secure WordPress AJAX handler with proper nonce verification and capability checks.

## Steps:

1. Ask the user for:
   - AJAX action name
   - What the AJAX handler should do
   - Whether it's for logged-in users only or also guests
   - What capability is required (e.g., 'edit_posts', 'manage_options')
   - What data will be sent/received

2. Generate both:
   - The PHP AJAX handler function with security checks
   - The JavaScript code to make the AJAX request
   - The code to localize the script with nonce and AJAX URL

3. Ensure the code includes:
   - Nonce verification
   - Capability checks
   - Input sanitization
   - Proper JSON responses
   - Error handling
   - Complete PHPDoc comments

## Example Output:

### PHP Handler:
```php
/**
 * Handle AJAX request for [description].
 *
 * @since 1.0.0
 */
function my_plugin_ajax_handler() {
    // Verify nonce
    check_ajax_referer( 'my_plugin_nonce', 'nonce' );

    // Check user capabilities
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array(
            'message' => __( 'Unauthorized access', 'my-plugin' ),
        ) );
    }

    // Sanitize input
    $data = isset( $_POST['data'] ) ? sanitize_text_field( $_POST['data'] ) : '';

    if ( empty( $data ) ) {
        wp_send_json_error( array(
            'message' => __( 'Missing required data', 'my-plugin' ),
        ) );
    }

    // Process the request
    // ... your logic here ...

    // Send success response
    wp_send_json_success( array(
        'message' => __( 'Success', 'my-plugin' ),
        'data'    => $result,
    ) );
}
add_action( 'wp_ajax_my_action', 'my_plugin_ajax_handler' );
add_action( 'wp_ajax_nopriv_my_action', 'my_plugin_ajax_handler' ); // For non-logged-in users
```

### Enqueue Script with Localization:
```php
/**
 * Enqueue AJAX script.
 *
 * @since 1.0.0
 */
function my_plugin_enqueue_ajax_script() {
    wp_enqueue_script(
        'my-plugin-ajax',
        plugins_url( 'assets/js/ajax.js', __FILE__ ),
        array( 'jquery' ),
        '1.0.0',
        true
    );

    wp_localize_script(
        'my-plugin-ajax',
        'myPluginAjax',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'my_plugin_nonce' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'my_plugin_enqueue_ajax_script' );
```

### JavaScript:
```javascript
jQuery(document).ready(function($) {
    $('#my-button').on('click', function(e) {
        e.preventDefault();

        var data = {
            action: 'my_action',
            nonce: myPluginAjax.nonce,
            data: $('#my-input').val()
        };

        $.post(myPluginAjax.ajax_url, data, function(response) {
            if (response.success) {
                console.log('Success:', response.data);
                // Handle success
            } else {
                console.error('Error:', response.data.message);
                // Handle error
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error:', error);
        });
    });
});
```
