<?php
/**
 * Template Loader - Load templates from plugin or theme
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Template_Loader {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('template_include', [$this, 'template_loader']);
        add_filter('single_template', [$this, 'single_product_template']);
        add_filter('archive_template', [$this, 'archive_product_template']);
    }

    /**
     * Load template for products
     */
    public function template_loader($template) {
        if (is_post_type_archive('hs_product')) {
            $new_template = $this->locate_template('archive-product.php');
            if ($new_template) {
                return $new_template;
            }
        }

        return $template;
    }

    /**
     * Load single product template
     */
    public function single_product_template($template) {
        if (is_singular('hs_product')) {
            global $post;

            // Get selected template from meta
            $template_choice = get_post_meta($post->ID, '_product_template', true);

            // Map template choices to file names
            $template_files = [
                'minimal' => 'single-product-minimal.php',
                'detailed' => 'single-product-detailed.php',
                'landing' => 'single-product-landing.php',
                'default' => 'single-product.php'
            ];

            // Get template file name (default if not set or invalid)
            $template_file = isset($template_files[$template_choice]) ? $template_files[$template_choice] : $template_files['default'];

            // Try to locate the selected template
            $new_template = $this->locate_template($template_file);

            // Fallback to default if selected template doesn't exist
            if (!$new_template && $template_file !== 'single-product.php') {
                $new_template = $this->locate_template('single-product.php');
            }

            if ($new_template) {
                return $new_template;
            }
        }

        return $template;
    }

    /**
     * Load archive product template
     */
    public function archive_product_template($template) {
        if (is_post_type_archive('hs_product')) {
            $new_template = $this->locate_template('archive-product.php');
            if ($new_template) {
                return $new_template;
            }
        }

        return $template;
    }

    /**
     * Locate template
     * Checks theme first, then plugin
     */
    public function locate_template($template_name) {
        // Check in theme/hubspot-ecommerce/ directory
        $theme_template = locate_template([
            'hubspot-ecommerce/' . $template_name,
            $template_name,
        ]);

        if ($theme_template) {
            return $theme_template;
        }

        // Check in plugin templates directory
        $plugin_template = HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'templates/' . $template_name;

        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return false;
    }

    /**
     * Get template part
     */
    public function get_template_part($slug, $name = null, $args = []) {
        $templates = [];

        if ($name) {
            $templates[] = "{$slug}-{$name}.php";
        }

        $templates[] = "{$slug}.php";

        $located = false;

        foreach ($templates as $template_name) {
            $located = $this->locate_template($template_name);
            if ($located) {
                break;
            }
        }

        if ($located && $args) {
            extract($args);
        }

        if ($located) {
            include $located;
        }
    }

    /**
     * Get template with args
     */
    public function get_template($template_name, $args = []) {
        if ($args && is_array($args)) {
            extract($args);
        }

        $located = $this->locate_template($template_name);

        if ($located) {
            include $located;
        }
    }
}
