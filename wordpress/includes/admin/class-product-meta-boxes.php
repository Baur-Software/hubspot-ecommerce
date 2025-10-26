<?php
/**
 * Product Meta Boxes
 *
 * Adds meta boxes for product template selection and HubSpot sync controls
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Product_Meta_Boxes {

    private static $instance = null;
    private $license_manager;
    private $product_manager;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->license_manager = HubSpot_Ecommerce_License_Manager::instance();
        $this->product_manager = HubSpot_Ecommerce_Product_Manager::instance();

        // Add meta boxes
        add_action('add_meta_boxes_hs_product', [$this, 'add_meta_boxes']);

        // Save meta box data
        add_action('save_post_hs_product', [$this, 'save_template_meta'], 10, 2);
        add_action('save_post_hs_product', [$this, 'save_sync_meta'], 10, 2);

        // Show welcome notice on first product
        add_action('admin_notices', [$this, 'show_welcome_notice']);
        add_action('wp_ajax_dismiss_product_welcome', [$this, 'dismiss_welcome_notice']);
    }

    /**
     * Add meta boxes to product edit screen
     */
    public function add_meta_boxes() {
        // Product Template selector
        add_meta_box(
            'hubspot_product_template',
            __('Product Template', 'hubspot-ecommerce'),
            [$this, 'render_template_meta_box'],
            'hs_product',
            'side',
            'high'
        );

        // HubSpot Sync Status
        add_meta_box(
            'hubspot_product_sync',
            __('HubSpot Sync', 'hubspot-ecommerce'),
            [$this, 'render_sync_meta_box'],
            'hs_product',
            'side',
            'default'
        );
    }

    /**
     * Render product template selection meta box
     */
    public function render_template_meta_box($post) {
        wp_nonce_field('hubspot_product_template_meta', 'hubspot_product_template_nonce');

        $current_template = get_post_meta($post->ID, '_product_template', true);
        if (empty($current_template)) {
            $current_template = 'default';
        }

        $templates = $this->get_available_templates();
        ?>
        <div class="hubspot-template-selector">
            <p><?php _e('Choose how this product page should be displayed:', 'hubspot-ecommerce'); ?></p>

            <select name="hubspot_product_template" id="hubspot_product_template" style="width: 100%;">
                <?php foreach ($templates as $template_key => $template_info) : ?>
                    <option value="<?php echo esc_attr($template_key); ?>" <?php selected($current_template, $template_key); ?>>
                        <?php echo esc_html($template_info['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <p class="description" id="template-description">
                <?php
                if (isset($templates[$current_template]['description'])) {
                    echo esc_html($templates[$current_template]['description']);
                }
                ?>
            </p>

            <script>
            jQuery(document).ready(function($) {
                var descriptions = <?php echo json_encode(wp_list_pluck($templates, 'description')); ?>;
                $('#hubspot_product_template').on('change', function() {
                    var template = $(this).val();
                    $('#template-description').text(descriptions[template] || '');
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Render HubSpot sync status meta box
     */
    public function render_sync_meta_box($post) {
        wp_nonce_field('hubspot_product_sync_meta', 'hubspot_product_sync_nonce');

        $hubspot_id = get_post_meta($post->ID, '_hubspot_product_id', true);
        $last_synced = get_post_meta($post->ID, '_last_synced_from_hubspot', true);
        $sync_enabled = get_post_meta($post->ID, '_hubspot_sync_enabled', true);

        if ($sync_enabled === '') {
            $sync_enabled = '1'; // Default to enabled
        }

        ?>
        <div class="hubspot-sync-status">
            <?php if ($hubspot_id) : ?>
                <div class="sync-status-badge synced">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <strong><?php _e('Synced to HubSpot', 'hubspot-ecommerce'); ?></strong>
                </div>
                <p class="description">
                    <?php printf(__('HubSpot ID: %s', 'hubspot-ecommerce'), '<code>' . esc_html($hubspot_id) . '</code>'); ?>
                </p>
                <?php if ($last_synced) : ?>
                    <p class="description">
                        <?php printf(__('Last synced: %s', 'hubspot-ecommerce'), human_time_diff(strtotime($last_synced), current_time('timestamp')) . ' ' . __('ago', 'hubspot-ecommerce')); ?>
                    </p>
                <?php endif; ?>
            <?php else : ?>
                <div class="sync-status-badge local">
                    <span class="dashicons dashicons-admin-home"></span>
                    <strong><?php _e('Local Product', 'hubspot-ecommerce'); ?></strong>
                </div>
                <p class="description">
                    <?php _e('This product exists only in WordPress.', 'hubspot-ecommerce'); ?>
                </p>
            <?php endif; ?>

            <hr style="margin: 15px 0;">

            <p>
                <label>
                    <input type="checkbox" name="hubspot_sync_enabled" value="1" <?php checked($sync_enabled, '1'); ?>>
                    <?php _e('Enable sync to HubSpot', 'hubspot-ecommerce'); ?>
                </label>
            </p>

            <div class="sync-actions" style="margin-top: 15px;">
                <?php if ($hubspot_id) : ?>
                    <button type="button" class="button button-small hubspot-pull-product" data-product-id="<?php echo esc_attr($post->ID); ?>">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Pull from HubSpot', 'hubspot-ecommerce'); ?>
                    </button>
                    <button type="button" class="button button-small hubspot-push-product" data-product-id="<?php echo esc_attr($post->ID); ?>">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Push to HubSpot', 'hubspot-ecommerce'); ?>
                    </button>
                <?php else : ?>
                    <button type="button" class="button button-primary button-small hubspot-push-product" data-product-id="<?php echo esc_attr($post->ID); ?>">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Push to HubSpot', 'hubspot-ecommerce'); ?>
                    </button>
                    <p class="description" style="margin-top: 10px;">
                        <?php _e('Click to create this product in HubSpot', 'hubspot-ecommerce'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if (!$this->license_manager->can_use_auto_sync()) : ?>
                <div class="hubspot-pro-feature" style="margin-top: 15px; padding: 10px; background: #f0f6fc; border-left: 3px solid #2271b1;">
                    <p style="margin: 0;">
                        <strong><?php _e('Pro Feature', 'hubspot-ecommerce'); ?></strong>
                    </p>
                    <p style="margin: 5px 0 0 0; font-size: 12px;">
                        <?php _e('Upgrade to enable automatic sync on save and scheduled sync from HubSpot.', 'hubspot-ecommerce'); ?>
                        <a href="https://baursoftware.com/products/hubspot-ecommerce" target="_blank"><?php _e('Learn more', 'hubspot-ecommerce'); ?></a>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .sync-status-badge {
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .sync-status-badge.synced {
            background: #d4edda;
            color: #155724;
        }
        .sync-status-badge.local {
            background: #fff3cd;
            color: #856404;
        }
        .sync-status-badge .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        .sync-actions .button {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .sync-actions .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
            margin-top: 2px;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('.hubspot-push-product, .hubspot-pull-product').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var productId = $button.data('product-id');
                var action = $button.hasClass('hubspot-push-product') ? 'push' : 'pull';

                $button.prop('disabled', true).find('.dashicons').addClass('spin');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hubspot_sync_single_product',
                        product_id: productId,
                        sync_direction: action,
                        nonce: '<?php echo wp_create_nonce('hubspot_sync_product'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    },
                    complete: function() {
                        $button.prop('disabled', false).find('.dashicons').removeClass('spin');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Get available product templates
     */
    private function get_available_templates() {
        $templates = [
            'default' => [
                'label' => __('Default', 'hubspot-ecommerce'),
                'description' => __('Standard product layout with images, description, price, and add to cart.', 'hubspot-ecommerce'),
                'file' => 'single-product.php'
            ],
            'minimal' => [
                'label' => __('Minimal', 'hubspot-ecommerce'),
                'description' => __('Clean, simple layout with essential information only.', 'hubspot-ecommerce'),
                'file' => 'single-product-minimal.php'
            ],
            'detailed' => [
                'label' => __('Detailed', 'hubspot-ecommerce'),
                'description' => __('Extended layout with tabs for description, specifications, and reviews.', 'hubspot-ecommerce'),
                'file' => 'single-product-detailed.php'
            ],
            'landing' => [
                'label' => __('Landing Page', 'hubspot-ecommerce'),
                'description' => __('Full-width marketing-focused layout perfect for single product campaigns.', 'hubspot-ecommerce'),
                'file' => 'single-product-landing.php'
            ]
        ];

        // Allow themes to register custom templates
        return apply_filters('hubspot_ecommerce_product_templates', $templates);
    }

    /**
     * Save template meta
     */
    public function save_template_meta($post_id, $post) {
        // Check nonce
        if (!isset($_POST['hubspot_product_template_nonce']) ||
            !wp_verify_nonce($_POST['hubspot_product_template_nonce'], 'hubspot_product_template_meta')) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Don't save on autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Save template selection
        if (isset($_POST['hubspot_product_template'])) {
            $template = sanitize_text_field($_POST['hubspot_product_template']);
            update_post_meta($post_id, '_product_template', $template);
        }
    }

    /**
     * Save sync meta
     */
    public function save_sync_meta($post_id, $post) {
        // Check nonce
        if (!isset($_POST['hubspot_product_sync_nonce']) ||
            !wp_verify_nonce($_POST['hubspot_product_sync_nonce'], 'hubspot_product_sync_meta')) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Don't save on autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Save sync enabled status
        $sync_enabled = isset($_POST['hubspot_sync_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_hubspot_sync_enabled', $sync_enabled);

        // If Pro and sync enabled and auto-push enabled, push to HubSpot
        if ($this->license_manager->can_use_auto_sync() && $sync_enabled === '1') {
            $auto_push = get_option('hubspot_ecommerce_auto_push_products', false);
            if ($auto_push) {
                // Push to HubSpot (we'll implement this in product manager)
                do_action('hubspot_ecommerce_auto_push_product', $post_id);
            }
        }
    }

    /**
     * Show welcome notice on first product creation
     */
    public function show_welcome_notice() {
        $screen = get_current_screen();

        if (!$screen || $screen->id !== 'hs_product') {
            return;
        }

        // Check if already dismissed
        if (get_option('hubspot_ecommerce_product_welcome_dismissed')) {
            return;
        }

        // Check if this is a new product
        global $post;
        if (!$post || $post->post_status === 'auto-draft' || get_post_meta($post->ID, '_hubspot_product_id', true)) {
            return;
        }

        ?>
        <div class="notice notice-info is-dismissible hubspot-product-welcome">
            <h3><?php _e('Welcome to Product Management!', 'hubspot-ecommerce'); ?></h3>
            <p><?php _e('You\'re creating a new product. Here\'s what you need to know:', 'hubspot-ecommerce'); ?></p>
            <ul style="list-style: disc; margin-left: 25px;">
                <li><?php _e('Products are treated like WordPress posts, giving you full flexibility to design your product pages.', 'hubspot-ecommerce'); ?></li>
                <li><?php _e('Choose a Product Template (in the sidebar) to control how your product page looks.', 'hubspot-ecommerce'); ?></li>
                <li><?php _e('Products stay local by default. Use the "Push to HubSpot" button when you\'re ready to sync.', 'hubspot-ecommerce'); ?></li>
                <li><?php _e('You can edit product details, pricing, and images directly in WordPress.', 'hubspot-ecommerce'); ?></li>
            </ul>
            <p>
                <a href="https://baursoftware.com/docs/hubspot-ecommerce/products" target="_blank" class="button button-primary">
                    <?php _e('Learn More', 'hubspot-ecommerce'); ?>
                </a>
                <button type="button" class="button dismiss-welcome">
                    <?php _e('Got it, don\'t show again', 'hubspot-ecommerce'); ?>
                </button>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.hubspot-product-welcome .dismiss-welcome').on('click', function() {
                $('.hubspot-product-welcome').fadeOut();
                $.post(ajaxurl, {
                    action: 'dismiss_product_welcome',
                    nonce: '<?php echo wp_create_nonce('dismiss_product_welcome'); ?>'
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Dismiss welcome notice
     */
    public function dismiss_welcome_notice() {
        check_ajax_referer('dismiss_product_welcome', 'nonce');
        update_option('hubspot_ecommerce_product_welcome_dismissed', true);
        wp_send_json_success();
    }
}
