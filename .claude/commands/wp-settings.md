# WordPress Settings Page Generator

You need to generate a WordPress admin settings page with the Settings API.

## Steps:

1. Ask the user for:
   - Settings page title
   - Menu slug
   - Parent menu (e.g., 'options-general.php' for Settings, or top-level)
   - What settings/options need to be saved
   - Field types needed (text, textarea, checkbox, select, etc.)
   - Required capability (default: 'manage_options')

2. Generate code that:
   - Uses the Settings API properly
   - Includes proper sanitization callbacks
   - Implements nonce verification
   - Follows WordPress Coding Standards
   - Includes complete PHPDoc comments
   - Uses proper capability checks

3. Include:
   - Menu registration
   - Settings registration
   - Settings page rendering
   - Sanitization callbacks
   - Form with proper nonce

## Example Output:

```php
/**
 * Add settings page to admin menu.
 *
 * @since 1.0.0
 */
function my_plugin_add_settings_page() {
    add_options_page(
        __( 'My Plugin Settings', 'my-plugin' ),
        __( 'My Plugin', 'my-plugin' ),
        'manage_options',
        'my-plugin-settings',
        'my_plugin_render_settings_page'
    );
}
add_action( 'admin_menu', 'my_plugin_add_settings_page' );

/**
 * Register settings.
 *
 * @since 1.0.0
 */
function my_plugin_register_settings() {
    register_setting(
        'my_plugin_settings_group',
        'my_plugin_option_name',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'my_plugin_sanitize_settings',
            'default'           => '',
        )
    );

    add_settings_section(
        'my_plugin_main_section',
        __( 'Main Settings', 'my-plugin' ),
        'my_plugin_section_callback',
        'my-plugin-settings'
    );

    add_settings_field(
        'my_plugin_text_field',
        __( 'Text Field', 'my-plugin' ),
        'my_plugin_text_field_callback',
        'my-plugin-settings',
        'my_plugin_main_section'
    );
}
add_action( 'admin_init', 'my_plugin_register_settings' );

/**
 * Section description callback.
 *
 * @since 1.0.0
 */
function my_plugin_section_callback() {
    echo '<p>' . esc_html__( 'Configure your plugin settings below.', 'my-plugin' ) . '</p>';
}

/**
 * Render text field.
 *
 * @since 1.0.0
 */
function my_plugin_text_field_callback() {
    $value = get_option( 'my_plugin_option_name', '' );
    ?>
    <input type="text"
           name="my_plugin_option_name"
           value="<?php echo esc_attr( $value ); ?>"
           class="regular-text">
    <?php
}

/**
 * Sanitize settings.
 *
 * @since 1.0.0
 *
 * @param mixed $input The input value.
 * @return mixed The sanitized value.
 */
function my_plugin_sanitize_settings( $input ) {
    return sanitize_text_field( $input );
}

/**
 * Render settings page.
 *
 * @since 1.0.0
 */
function my_plugin_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'my-plugin' ) );
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'my_plugin_settings_group' );
            do_settings_sections( 'my-plugin-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
```
