<?php
/**
 * HubSpot Commerce Hub Setup Wizard
 *
 * Guides users through Commerce Hub setup and configuration.
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Setup_Wizard {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * HubSpot API instance
     */
    private $api;

    /**
     * Current setup step
     */
    private $current_step = '';

    /**
     * Setup steps
     */
    private $steps = [];

    /**
     * Get singleton instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->api = HubSpot_Ecommerce_API::instance();

        $this->steps = [
            'welcome' => [
                'name' => __('Welcome', 'hubspot-ecommerce'),
                'view' => [$this, 'setup_welcome'],
                'handler' => '',
            ],
            'requirements' => [
                'name' => __('Requirements Check', 'hubspot-ecommerce'),
                'view' => [$this, 'setup_requirements'],
                'handler' => '',
            ],
            'commerce_hub' => [
                'name' => __('Commerce Hub', 'hubspot-ecommerce'),
                'view' => [$this, 'setup_commerce_hub'],
                'handler' => [$this, 'save_commerce_hub'],
            ],
            'payment' => [
                'name' => __('Payment Setup', 'hubspot-ecommerce'),
                'view' => [$this, 'setup_payment'],
                'handler' => [$this, 'save_payment'],
            ],
            'pages' => [
                'name' => __('Shop Pages', 'hubspot-ecommerce'),
                'view' => [$this, 'setup_pages'],
                'handler' => [$this, 'save_pages'],
            ],
            'complete' => [
                'name' => __('Complete', 'hubspot-ecommerce'),
                'view' => [$this, 'setup_complete'],
                'handler' => '',
            ],
        ];

        add_action('admin_menu', [$this, 'add_wizard_page']);
        add_action('admin_init', [$this, 'handle_wizard_steps']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_wizard_assets']);
    }

    /**
     * Add wizard page to admin menu
     */
    public function add_wizard_page() {
        // Only show if setup not completed
        if (get_option('hubspot_ecommerce_setup_complete')) {
            return;
        }

        add_dashboard_page(
            __('HubSpot Ecommerce Setup', 'hubspot-ecommerce'),
            __('HubSpot Setup', 'hubspot-ecommerce'),
            'manage_options',
            'hubspot-ecommerce-setup',
            [$this, 'render_wizard']
        );
    }

    /**
     * Enqueue wizard assets
     */
    public function enqueue_wizard_assets($hook) {
        if ('dashboard_page_hubspot-ecommerce-setup' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'hubspot-ecommerce-wizard',
            HUBSPOT_ECOMMERCE_PLUGIN_URL . 'assets/css/wizard.css',
            [],
            HUBSPOT_ECOMMERCE_VERSION
        );

        wp_enqueue_script(
            'hubspot-ecommerce-wizard',
            HUBSPOT_ECOMMERCE_PLUGIN_URL . 'assets/js/wizard.js',
            ['jquery'],
            HUBSPOT_ECOMMERCE_VERSION,
            true
        );

        wp_localize_script('hubspot-ecommerce-wizard', 'hubspotWizard', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hubspot_ecommerce_wizard'),
        ]);
    }

    /**
     * Get current step
     */
    private function get_current_step() {
        if (!empty($this->current_step)) {
            return $this->current_step;
        }

        $this->current_step = isset($_GET['step']) ? sanitize_key($_GET['step']) : 'welcome';

        if (!array_key_exists($this->current_step, $this->steps)) {
            $this->current_step = 'welcome';
        }

        return $this->current_step;
    }

    /**
     * Get next step
     */
    private function get_next_step() {
        $keys = array_keys($this->steps);
        $current_index = array_search($this->get_current_step(), $keys);

        if ($current_index !== false && isset($keys[$current_index + 1])) {
            return $keys[$current_index + 1];
        }

        return '';
    }

    /**
     * Get previous step
     */
    private function get_previous_step() {
        $keys = array_keys($this->steps);
        $current_index = array_search($this->get_current_step(), $keys);

        if ($current_index > 0) {
            return $keys[$current_index - 1];
        }

        return '';
    }

    /**
     * Handle wizard step submissions
     */
    public function handle_wizard_steps() {
        if (!isset($_POST['hubspot_wizard_step']) || !isset($_POST['_wpnonce'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $step = sanitize_key($_POST['hubspot_wizard_step']);

        if (!array_key_exists($step, $this->steps)) {
            return;
        }

        check_admin_referer('hubspot_ecommerce_wizard_' . $step);

        $handler = $this->steps[$step]['handler'];

        if (!empty($handler) && is_callable($handler)) {
            call_user_func($handler);
        }

        // Redirect to next step
        $next_step = $this->get_next_step();
        wp_safe_redirect(admin_url('index.php?page=hubspot-ecommerce-setup&step=' . $next_step));
        exit;
    }

    /**
     * Render wizard
     */
    public function render_wizard() {
        $this->current_step = $this->get_current_step();
        ?>
        <div class="wrap hubspot-wizard">
            <h1><?php esc_html_e('HubSpot Ecommerce Setup', 'hubspot-ecommerce'); ?></h1>

            <?php $this->render_wizard_steps(); ?>

            <div class="hubspot-wizard-content">
                <?php
                if (!empty($this->steps[$this->current_step]['view'])) {
                    call_user_func($this->steps[$this->current_step]['view']);
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render wizard steps progress
     */
    private function render_wizard_steps() {
        ?>
        <ul class="hubspot-wizard-steps">
            <?php
            $step_index = 0;
            $current_index = array_search($this->current_step, array_keys($this->steps));

            foreach ($this->steps as $step_key => $step) {
                $class = '';
                if ($step_index < $current_index) {
                    $class = 'completed';
                } elseif ($step_index === $current_index) {
                    $class = 'active';
                }
                ?>
                <li class="<?php echo esc_attr($class); ?>">
                    <span class="step-number"><?php echo esc_html($step_index + 1); ?></span>
                    <span class="step-name"><?php echo esc_html($step['name']); ?></span>
                </li>
                <?php
                $step_index++;
            }
            ?>
        </ul>
        <?php
    }

    /**
     * Welcome step
     */
    private function setup_welcome() {
        ?>
        <div class="hubspot-wizard-panel">
            <h2><?php esc_html_e('Welcome to HubSpot Ecommerce!', 'hubspot-ecommerce'); ?></h2>

            <p class="lead">
                <?php esc_html_e('This wizard will help you set up your HubSpot-powered ecommerce store.', 'hubspot-ecommerce'); ?>
            </p>

            <div class="feature-list">
                <h3><?php esc_html_e('What you\'ll get:', 'hubspot-ecommerce'); ?></h3>
                <ul>
                    <li>✅ <?php esc_html_e('Product sync from HubSpot', 'hubspot-ecommerce'); ?></li>
                    <li>✅ <?php esc_html_e('Shopping cart and checkout', 'hubspot-ecommerce'); ?></li>
                    <li>✅ <?php esc_html_e('Secure payment processing via HubSpot Commerce Hub', 'hubspot-ecommerce'); ?></li>
                    <li>✅ <?php esc_html_e('Customer management and CRM integration', 'hubspot-ecommerce'); ?></li>
                    <li>✅ <?php esc_html_e('Order tracking and deal management', 'hubspot-ecommerce'); ?></li>
                </ul>
            </div>

            <div class="notice notice-info inline">
                <p>
                    <strong><?php esc_html_e('Requirements:', 'hubspot-ecommerce'); ?></strong>
                    <?php esc_html_e('You\'ll need HubSpot Marketing Hub Professional or higher with Commerce Hub enabled.', 'hubspot-ecommerce'); ?>
                </p>
            </div>

            <?php $this->render_wizard_actions('welcome'); ?>
        </div>
        <?php
    }

    /**
     * Requirements check step
     */
    private function setup_requirements() {
        $checks = $this->check_requirements();
        $all_passed = !in_array(false, array_column($checks, 'status'), true);
        ?>
        <div class="hubspot-wizard-panel">
            <h2><?php esc_html_e('Requirements Check', 'hubspot-ecommerce'); ?></h2>

            <p><?php esc_html_e('Let\'s make sure your environment is ready:', 'hubspot-ecommerce'); ?></p>

            <table class="requirements-table">
                <?php foreach ($checks as $check): ?>
                <tr class="<?php echo $check['status'] ? 'passed' : 'failed'; ?>">
                    <td class="status">
                        <?php echo $check['status'] ? '✅' : '❌'; ?>
                    </td>
                    <td class="name">
                        <strong><?php echo esc_html($check['name']); ?></strong>
                        <?php if (!empty($check['description'])): ?>
                            <p class="description"><?php echo esc_html($check['description']); ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="result">
                        <?php echo esc_html($check['message']); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <?php if (!$all_passed): ?>
                <div class="notice notice-error inline">
                    <p>
                        <?php esc_html_e('Please fix the failed requirements before continuing.', 'hubspot-ecommerce'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php $this->render_wizard_actions('requirements', $all_passed); ?>
        </div>
        <?php
    }

    /**
     * Commerce Hub setup step
     */
    private function setup_commerce_hub() {
        $api_key = get_option('hubspot_ecommerce_api_key', '');
        $commerce_hub_status = $this->check_commerce_hub_status();
        ?>
        <div class="hubspot-wizard-panel">
            <h2><?php esc_html_e('HubSpot Commerce Hub Setup', 'hubspot-ecommerce'); ?></h2>

            <?php if (empty($api_key)): ?>
                <div class="notice notice-warning inline">
                    <p>
                        <strong><?php esc_html_e('API Connection Required', 'hubspot-ecommerce'); ?></strong><br>
                        <?php esc_html_e('Please connect your HubSpot account first.', 'hubspot-ecommerce'); ?>
                    </p>
                </div>

                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=hubspot-ecommerce-settings')); ?>" class="button button-primary">
                        <?php esc_html_e('Configure HubSpot API', 'hubspot-ecommerce'); ?>
                    </a>
                </p>
            <?php else: ?>
                <div class="commerce-hub-status">
                    <?php if ($commerce_hub_status['enabled']): ?>
                        <div class="notice notice-success inline">
                            <p>
                                ✅ <strong><?php esc_html_e('Commerce Hub is enabled!', 'hubspot-ecommerce'); ?></strong>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="notice notice-warning inline">
                            <p>
                                <strong><?php esc_html_e('Commerce Hub Not Detected', 'hubspot-ecommerce'); ?></strong>
                            </p>
                        </div>

                        <div class="setup-instructions">
                            <h3><?php esc_html_e('How to Enable Commerce Hub:', 'hubspot-ecommerce'); ?></h3>

                            <ol>
                                <li>
                                    <strong><?php esc_html_e('Check Your HubSpot Subscription', 'hubspot-ecommerce'); ?></strong>
                                    <p><?php esc_html_e('Commerce Hub requires Marketing Hub Professional or Enterprise.', 'hubspot-ecommerce'); ?></p>
                                    <p>
                                        <a href="https://www.hubspot.com/pricing/marketing" target="_blank" class="button">
                                            <?php esc_html_e('View HubSpot Pricing', 'hubspot-ecommerce'); ?>
                                        </a>
                                    </p>
                                </li>

                                <li>
                                    <strong><?php esc_html_e('Enable Commerce Hub in HubSpot', 'hubspot-ecommerce'); ?></strong>
                                    <ol type="a">
                                        <li><?php esc_html_e('Log into your HubSpot account', 'hubspot-ecommerce'); ?></li>
                                        <li><?php esc_html_e('Go to Settings (⚙️) → Commerce Hub', 'hubspot-ecommerce'); ?></li>
                                        <li><?php esc_html_e('Click "Get Started" or "Enable Commerce Hub"', 'hubspot-ecommerce'); ?></li>
                                        <li><?php esc_html_e('Follow the setup wizard in HubSpot', 'hubspot-ecommerce'); ?></li>
                                    </ol>
                                    <p>
                                        <a href="https://app.hubspot.com/settings/commerce" target="_blank" class="button button-primary">
                                            <?php esc_html_e('Open HubSpot Commerce Settings', 'hubspot-ecommerce'); ?>
                                        </a>
                                    </p>
                                </li>

                                <li>
                                    <strong><?php esc_html_e('Grant API Permissions', 'hubspot-ecommerce'); ?></strong>
                                    <p><?php esc_html_e('Make sure your API key has these scopes:', 'hubspot-ecommerce'); ?></p>
                                    <ul>
                                        <li><code>crm.objects.invoices.read</code></li>
                                        <li><code>crm.objects.invoices.write</code></li>
                                        <li><code>crm.objects.commerce_payments.read</code></li>
                                    </ul>
                                    <p>
                                        <a href="https://app.hubspot.com/settings/integrations/private-apps" target="_blank" class="button">
                                            <?php esc_html_e('Manage API Permissions', 'hubspot-ecommerce'); ?>
                                        </a>
                                    </p>
                                </li>

                                <li>
                                    <strong><?php esc_html_e('Verify Setup', 'hubspot-ecommerce'); ?></strong>
                                    <p>
                                        <button type="button" id="check-commerce-hub" class="button">
                                            <?php esc_html_e('Re-check Commerce Hub Status', 'hubspot-ecommerce'); ?>
                                        </button>
                                    </p>
                                </li>
                            </ol>
                        </div>

                        <div class="help-section">
                            <h4><?php esc_html_e('Need Help?', 'hubspot-ecommerce'); ?></h4>
                            <p>
                                <a href="https://knowledge.hubspot.com/get-started/collect-payments-with-commerce-tools" target="_blank">
                                    <?php esc_html_e('📚 HubSpot Commerce Hub Documentation', 'hubspot-ecommerce'); ?>
                                </a>
                            </p>
                            <p>
                                <a href="https://knowledge.hubspot.com/payment-processing/set-up-payments" target="_blank">
                                    <?php esc_html_e('💳 Payment Processing Setup Guide', 'hubspot-ecommerce'); ?>
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php $this->render_wizard_actions('commerce_hub', $commerce_hub_status['enabled']); ?>
        </div>
        <?php
    }

    /**
     * Payment setup step
     */
    private function setup_payment() {
        $payment_status = $this->check_payment_processor_status();
        ?>
        <div class="hubspot-wizard-panel">
            <h2><?php esc_html_e('Payment Processor Setup', 'hubspot-ecommerce'); ?></h2>

            <?php if ($payment_status['configured']): ?>
                <div class="notice notice-success inline">
                    <p>
                        ✅ <strong><?php esc_html_e('Payment processor is configured!', 'hubspot-ecommerce'); ?></strong><br>
                        <?php
                        printf(
                            esc_html__('Processor: %s', 'hubspot-ecommerce'),
                            esc_html($payment_status['processor'])
                        );
                        ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="notice notice-warning inline">
                    <p>
                        <strong><?php esc_html_e('Payment Processor Not Configured', 'hubspot-ecommerce'); ?></strong>
                    </p>
                </div>

                <div class="payment-options">
                    <h3><?php esc_html_e('Choose Your Payment Processor:', 'hubspot-ecommerce'); ?></h3>

                    <div class="payment-option">
                        <h4>🏦 <?php esc_html_e('HubSpot Payments (Recommended)', 'hubspot-ecommerce'); ?></h4>
                        <p><?php esc_html_e('Built directly into HubSpot Commerce Hub.', 'hubspot-ecommerce'); ?></p>

                        <ul>
                            <li>✅ <?php esc_html_e('No monthly fees', 'hubspot-ecommerce'); ?></li>
                            <li>✅ <?php esc_html_e('2.9% for cards, 0.5% (capped at $10) for ACH', 'hubspot-ecommerce'); ?></li>
                            <li>✅ <?php esc_html_e('Seamless integration', 'hubspot-ecommerce'); ?></li>
                            <li>✅ <?php esc_html_e('Accept cards and ACH', 'hubspot-ecommerce'); ?></li>
                        </ul>

                        <p>
                            <a href="https://knowledge.hubspot.com/payment-processing/set-up-payments" target="_blank" class="button button-primary">
                                <?php esc_html_e('Set Up HubSpot Payments', 'hubspot-ecommerce'); ?>
                            </a>
                        </p>
                    </div>

                    <div class="payment-option">
                        <h4>💳 <?php esc_html_e('Stripe', 'hubspot-ecommerce'); ?></h4>
                        <p><?php esc_html_e('Connect your existing Stripe account.', 'hubspot-ecommerce'); ?></p>

                        <ul>
                            <li>✅ <?php esc_html_e('Use existing Stripe account', 'hubspot-ecommerce'); ?></li>
                            <li>✅ <?php esc_html_e('Your Stripe rates + 0.5% platform fee', 'hubspot-ecommerce'); ?></li>
                            <li>✅ <?php esc_html_e('Keep existing payment workflows', 'hubspot-ecommerce'); ?></li>
                        </ul>

                        <p>
                            <a href="https://knowledge.hubspot.com/payment-processing/connect-your-stripe-account-as-a-payment-processor-in-hubspot" target="_blank" class="button">
                                <?php esc_html_e('Connect Stripe to HubSpot', 'hubspot-ecommerce'); ?>
                            </a>
                        </p>
                    </div>

                    <div class="setup-instructions">
                        <h4><?php esc_html_e('Setup Steps:', 'hubspot-ecommerce'); ?></h4>
                        <ol>
                            <li><?php esc_html_e('Choose a payment processor above', 'hubspot-ecommerce'); ?></li>
                            <li><?php esc_html_e('Complete setup in HubSpot (link will open in new tab)', 'hubspot-ecommerce'); ?></li>
                            <li><?php esc_html_e('Complete business verification (for HubSpot Payments)', 'hubspot-ecommerce'); ?></li>
                            <li><?php esc_html_e('Test payment processing in HubSpot', 'hubspot-ecommerce'); ?></li>
                            <li><?php esc_html_e('Return here and verify configuration', 'hubspot-ecommerce'); ?></li>
                        </ol>
                    </div>

                    <p>
                        <button type="button" id="check-payment-processor" class="button">
                            <?php esc_html_e('Re-check Payment Processor', 'hubspot-ecommerce'); ?>
                        </button>
                    </p>
                </div>
            <?php endif; ?>

            <?php $this->render_wizard_actions('payment', $payment_status['configured']); ?>
        </div>
        <?php
    }

    /**
     * Pages setup step
     */
    private function setup_pages() {
        ?>
        <div class="hubspot-wizard-panel">
            <h2><?php esc_html_e('Shop Pages Setup', 'hubspot-ecommerce'); ?></h2>

            <p><?php esc_html_e('Select which pages will be used for your shop:', 'hubspot-ecommerce'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('hubspot_ecommerce_wizard_pages'); ?>
                <input type="hidden" name="hubspot_wizard_step" value="pages">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="shop_page"><?php esc_html_e('Shop Page', 'hubspot-ecommerce'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_pages([
                                'name' => 'shop_page',
                                'id' => 'shop_page',
                                'selected' => get_option('hubspot_ecommerce_shop_page'),
                                'show_option_none' => __('— Select —', 'hubspot-ecommerce'),
                            ]);
                            ?>
                            <p class="description">
                                <?php esc_html_e('The main shop page displaying all products.', 'hubspot-ecommerce'); ?>
                                <?php esc_html_e('Add the [hubspot_products] shortcode to this page.', 'hubspot-ecommerce'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="cart_page"><?php esc_html_e('Cart Page', 'hubspot-ecommerce'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_pages([
                                'name' => 'cart_page',
                                'id' => 'cart_page',
                                'selected' => get_option('hubspot_ecommerce_cart_page'),
                                'show_option_none' => __('— Select —', 'hubspot-ecommerce'),
                            ]);
                            ?>
                            <p class="description">
                                <?php esc_html_e('Shopping cart page.', 'hubspot-ecommerce'); ?>
                                <?php esc_html_e('Add the [hubspot_cart] shortcode to this page.', 'hubspot-ecommerce'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="checkout_page"><?php esc_html_e('Checkout Page', 'hubspot-ecommerce'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_pages([
                                'name' => 'checkout_page',
                                'id' => 'checkout_page',
                                'selected' => get_option('hubspot_ecommerce_checkout_page'),
                                'show_option_none' => __('— Select —', 'hubspot-ecommerce'),
                            ]);
                            ?>
                            <p class="description">
                                <?php esc_html_e('Checkout page.', 'hubspot-ecommerce'); ?>
                                <?php esc_html_e('Add the [hubspot_checkout] shortcode to this page.', 'hubspot-ecommerce'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="account_page"><?php esc_html_e('Account Page', 'hubspot-ecommerce'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_pages([
                                'name' => 'account_page',
                                'id' => 'account_page',
                                'selected' => get_option('hubspot_ecommerce_account_page'),
                                'show_option_none' => __('— Select —', 'hubspot-ecommerce'),
                            ]);
                            ?>
                            <p class="description">
                                <?php esc_html_e('Customer account page.', 'hubspot-ecommerce'); ?>
                                <?php esc_html_e('Add the [hubspot_account] shortcode to this page.', 'hubspot-ecommerce'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <div class="notice notice-info inline">
                    <p>
                        <strong><?php esc_html_e('Don\'t have these pages yet?', 'hubspot-ecommerce'); ?></strong><br>
                        <?php esc_html_e('You can create them now and add the shortcodes, or do it later from the WordPress admin.', 'hubspot-ecommerce'); ?>
                    </p>
                </div>

                <?php $this->render_wizard_actions('pages', true, true); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Complete step
     */
    private function setup_complete() {
        ?>
        <div class="hubspot-wizard-panel complete">
            <h2>🎉 <?php esc_html_e('Setup Complete!', 'hubspot-ecommerce'); ?></h2>

            <p class="lead">
                <?php esc_html_e('Your HubSpot Ecommerce store is ready to go!', 'hubspot-ecommerce'); ?>
            </p>

            <div class="next-steps">
                <h3><?php esc_html_e('Next Steps:', 'hubspot-ecommerce'); ?></h3>

                <div class="step-card">
                    <h4>1. <?php esc_html_e('Sync Your Products', 'hubspot-ecommerce'); ?></h4>
                    <p><?php esc_html_e('Import products from HubSpot to your WordPress store.', 'hubspot-ecommerce'); ?></p>
                    <p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=hubspot-ecommerce-sync')); ?>" class="button button-primary">
                            <?php esc_html_e('Sync Products Now', 'hubspot-ecommerce'); ?>
                        </a>
                    </p>
                </div>

                <div class="step-card">
                    <h4>2. <?php esc_html_e('Configure Shop Settings', 'hubspot-ecommerce'); ?></h4>
                    <p><?php esc_html_e('Set up currency, sync intervals, and other options.', 'hubspot-ecommerce'); ?></p>
                    <p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=hubspot-ecommerce-settings')); ?>" class="button">
                            <?php esc_html_e('Go to Settings', 'hubspot-ecommerce'); ?>
                        </a>
                    </p>
                </div>

                <div class="step-card">
                    <h4>3. <?php esc_html_e('Test Your Store', 'hubspot-ecommerce'); ?></h4>
                    <p><?php esc_html_e('Place a test order to make sure everything works.', 'hubspot-ecommerce'); ?></p>
                    <p>
                        <?php
                        $shop_page_id = get_option('hubspot_ecommerce_shop_page');
                        if ($shop_page_id) {
                            $shop_url = get_permalink($shop_page_id);
                        } else {
                            $shop_url = home_url('/shop/');
                        }
                        ?>
                        <a href="<?php echo esc_url($shop_url); ?>" class="button">
                            <?php esc_html_e('Visit Shop', 'hubspot-ecommerce'); ?>
                        </a>
                    </p>
                </div>

                <div class="step-card">
                    <h4>4. <?php esc_html_e('View Documentation', 'hubspot-ecommerce'); ?></h4>
                    <p><?php esc_html_e('Learn about features, shortcodes, and customization.', 'hubspot-ecommerce'); ?></p>
                    <p>
                        <a href="<?php echo esc_url(HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'README.md'); ?>" class="button">
                            <?php esc_html_e('Read Documentation', 'hubspot-ecommerce'); ?>
                        </a>
                    </p>
                </div>
            </div>

            <div class="wizard-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=hubspot-ecommerce')); ?>" class="button button-primary button-hero">
                    <?php esc_html_e('Go to Dashboard', 'hubspot-ecommerce'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render wizard action buttons
     */
    private function render_wizard_actions($step, $can_continue = true, $is_form = false) {
        $previous = $this->get_previous_step();
        $next = $this->get_next_step();
        ?>
        <div class="wizard-actions">
            <?php if ($previous): ?>
                <a href="<?php echo esc_url(admin_url('index.php?page=hubspot-ecommerce-setup&step=' . $previous)); ?>" class="button">
                    <?php esc_html_e('← Previous', 'hubspot-ecommerce'); ?>
                </a>
            <?php endif; ?>

            <?php if ($next && !$is_form): ?>
                <?php if ($can_continue): ?>
                    <a href="<?php echo esc_url(admin_url('index.php?page=hubspot-ecommerce-setup&step=' . $next)); ?>" class="button button-primary">
                        <?php esc_html_e('Continue →', 'hubspot-ecommerce'); ?>
                    </a>
                <?php else: ?>
                    <button type="button" class="button button-primary" disabled>
                        <?php esc_html_e('Fix Requirements to Continue', 'hubspot-ecommerce'); ?>
                    </button>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($is_form): ?>
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Save & Continue →', 'hubspot-ecommerce'); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Check system requirements
     */
    private function check_requirements() {
        $checks = [];

        // PHP version
        $checks[] = [
            'name' => __('PHP Version', 'hubspot-ecommerce'),
            'status' => version_compare(PHP_VERSION, '8.0', '>='),
            'message' => PHP_VERSION,
            'description' => __('PHP 8.0 or higher required', 'hubspot-ecommerce'),
        ];

        // WordPress version
        global $wp_version;
        $checks[] = [
            'name' => __('WordPress Version', 'hubspot-ecommerce'),
            'status' => version_compare($wp_version, '6.4', '>='),
            'message' => $wp_version,
            'description' => __('WordPress 6.4 or higher required', 'hubspot-ecommerce'),
        ];

        // HTTPS
        $checks[] = [
            'name' => __('HTTPS', 'hubspot-ecommerce'),
            'status' => is_ssl(),
            'message' => is_ssl() ? __('Enabled', 'hubspot-ecommerce') : __('Not enabled', 'hubspot-ecommerce'),
            'description' => __('Required for webhooks', 'hubspot-ecommerce'),
        ];

        // Permalinks
        $permalink_structure = get_option('permalink_structure');
        $checks[] = [
            'name' => __('Permalinks', 'hubspot-ecommerce'),
            'status' => !empty($permalink_structure),
            'message' => !empty($permalink_structure) ? __('Enabled', 'hubspot-ecommerce') : __('Plain permalinks', 'hubspot-ecommerce'),
            'description' => __('Pretty permalinks required', 'hubspot-ecommerce'),
        ];

        // cURL
        $checks[] = [
            'name' => __('cURL', 'hubspot-ecommerce'),
            'status' => function_exists('curl_version'),
            'message' => function_exists('curl_version') ? __('Installed', 'hubspot-ecommerce') : __('Not installed', 'hubspot-ecommerce'),
            'description' => __('Required for API calls', 'hubspot-ecommerce'),
        ];

        return $checks;
    }

    /**
     * Check Commerce Hub status
     */
    private function check_commerce_hub_status() {
        // Try to create a test invoice to verify Commerce Hub access
        $test_result = $this->api->request('/crm/v3/objects/invoices', 'GET', ['limit' => 1]);

        return [
            'enabled' => !is_wp_error($test_result),
            'message' => is_wp_error($test_result) ? $test_result->get_error_message() : '',
        ];
    }

    /**
     * Check payment processor status
     */
    private function check_payment_processor_status() {
        // This would need actual API call to check payment processor
        // For now, return placeholder
        return [
            'configured' => false,
            'processor' => '',
        ];
    }

    /**
     * Save pages settings
     */
    private function save_pages() {
        $pages = ['shop_page', 'cart_page', 'checkout_page', 'account_page'];

        foreach ($pages as $page) {
            if (isset($_POST[$page])) {
                update_option('hubspot_ecommerce_' . $page, absint($_POST[$page]));
            }
        }

        // Mark setup as complete
        update_option('hubspot_ecommerce_setup_complete', true);
    }
}
