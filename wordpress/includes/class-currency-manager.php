<?php
/**
 * Currency Manager - Syncs and manages currencies from HubSpot
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Currency_Manager {

    private static $instance = null;
    private $api;

    /**
     * Comprehensive currency data with proper formatting rules
     * Based on ISO 4217 standards
     */
    private $currency_data = [
        'USD' => ['symbol' => '$', 'name' => 'US Dollar', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'EUR' => ['symbol' => '€', 'name' => 'Euro', 'decimals' => 2, 'position' => 'before', 'thousands' => '.', 'decimal' => ','],
        'GBP' => ['symbol' => '£', 'name' => 'British Pound', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'JPY' => ['symbol' => '¥', 'name' => 'Japanese Yen', 'decimals' => 0, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'AUD' => ['symbol' => '$', 'name' => 'Australian Dollar', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'CAD' => ['symbol' => '$', 'name' => 'Canadian Dollar', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'CHF' => ['symbol' => 'Fr', 'name' => 'Swiss Franc', 'decimals' => 2, 'position' => 'before', 'thousands' => "'", 'decimal' => '.'],
        'CNY' => ['symbol' => '¥', 'name' => 'Chinese Yuan', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'SEK' => ['symbol' => 'kr', 'name' => 'Swedish Krona', 'decimals' => 2, 'position' => 'after', 'thousands' => ' ', 'decimal' => ','],
        'NZD' => ['symbol' => '$', 'name' => 'New Zealand Dollar', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'MXN' => ['symbol' => '$', 'name' => 'Mexican Peso', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'SGD' => ['symbol' => '$', 'name' => 'Singapore Dollar', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'HKD' => ['symbol' => '$', 'name' => 'Hong Kong Dollar', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'NOK' => ['symbol' => 'kr', 'name' => 'Norwegian Krone', 'decimals' => 2, 'position' => 'after', 'thousands' => ' ', 'decimal' => ','],
        'KRW' => ['symbol' => '₩', 'name' => 'South Korean Won', 'decimals' => 0, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'TRY' => ['symbol' => '₺', 'name' => 'Turkish Lira', 'decimals' => 2, 'position' => 'before', 'thousands' => '.', 'decimal' => ','],
        'RUB' => ['symbol' => '₽', 'name' => 'Russian Ruble', 'decimals' => 2, 'position' => 'after', 'thousands' => ' ', 'decimal' => ','],
        'INR' => ['symbol' => '₹', 'name' => 'Indian Rupee', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'BRL' => ['symbol' => 'R$', 'name' => 'Brazilian Real', 'decimals' => 2, 'position' => 'before', 'thousands' => '.', 'decimal' => ','],
        'ZAR' => ['symbol' => 'R', 'name' => 'South African Rand', 'decimals' => 2, 'position' => 'before', 'thousands' => ' ', 'decimal' => '.'],
        'DKK' => ['symbol' => 'kr', 'name' => 'Danish Krone', 'decimals' => 2, 'position' => 'after', 'thousands' => '.', 'decimal' => ','],
        'PLN' => ['symbol' => 'zł', 'name' => 'Polish Zloty', 'decimals' => 2, 'position' => 'after', 'thousands' => ' ', 'decimal' => ','],
        'THB' => ['symbol' => '฿', 'name' => 'Thai Baht', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'IDR' => ['symbol' => 'Rp', 'name' => 'Indonesian Rupiah', 'decimals' => 0, 'position' => 'before', 'thousands' => '.', 'decimal' => ','],
        'HUF' => ['symbol' => 'Ft', 'name' => 'Hungarian Forint', 'decimals' => 0, 'position' => 'after', 'thousands' => ' ', 'decimal' => ','],
        'CZK' => ['symbol' => 'Kč', 'name' => 'Czech Koruna', 'decimals' => 2, 'position' => 'after', 'thousands' => ' ', 'decimal' => ','],
        'ILS' => ['symbol' => '₪', 'name' => 'Israeli Shekel', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'CLP' => ['symbol' => '$', 'name' => 'Chilean Peso', 'decimals' => 0, 'position' => 'before', 'thousands' => '.', 'decimal' => ','],
        'PHP' => ['symbol' => '₱', 'name' => 'Philippine Peso', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'AED' => ['symbol' => 'د.إ', 'name' => 'UAE Dirham', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'COP' => ['symbol' => '$', 'name' => 'Colombian Peso', 'decimals' => 0, 'position' => 'before', 'thousands' => '.', 'decimal' => ','],
        'SAR' => ['symbol' => '﷼', 'name' => 'Saudi Riyal', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'MYR' => ['symbol' => 'RM', 'name' => 'Malaysian Ringgit', 'decimals' => 2, 'position' => 'before', 'thousands' => ',', 'decimal' => '.'],
        'RON' => ['symbol' => 'lei', 'name' => 'Romanian Leu', 'decimals' => 2, 'position' => 'after', 'thousands' => '.', 'decimal' => ','],
    ];

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->api = HubSpot_Ecommerce_API::instance();

        // Schedule currency sync
        add_action('hubspot_ecommerce_sync_currencies', [$this, 'sync_currencies']);

        // Sync currencies daily
        if (!wp_next_scheduled('hubspot_ecommerce_sync_currencies')) {
            wp_schedule_event(time(), 'daily', 'hubspot_ecommerce_sync_currencies');
        }
    }

    /**
     * Sync currencies from HubSpot account
     *
     * @return array Results of sync operation
     */
    public function sync_currencies() {
        $results = [
            'company_currency' => null,
            'enabled_currencies' => [],
            'errors' => [],
        ];

        // Get company currency
        $company_currency = $this->api->get_company_currency();
        if (is_wp_error($company_currency)) {
            $results['errors'][] = 'Failed to get company currency: ' . $company_currency->get_error_message();
        } else {
            $results['company_currency'] = $company_currency['currencyCode'] ?? null;

            // Update default currency option
            if (!empty($results['company_currency'])) {
                update_option('hubspot_ecommerce_currency', $results['company_currency']);
            }
        }

        // Get all enabled currencies
        $account_currencies = $this->api->get_account_currencies();
        if (is_wp_error($account_currencies)) {
            $results['errors'][] = 'Failed to get account currencies: ' . $account_currencies->get_error_message();
        } else {
            // Process currency data
            if (isset($account_currencies['results']) && is_array($account_currencies['results'])) {
                foreach ($account_currencies['results'] as $currency) {
                    if (isset($currency['fromCurrencyCode'])) {
                        $results['enabled_currencies'][] = [
                            'code' => $currency['fromCurrencyCode'],
                            'rate' => $currency['conversionRate'] ?? 1,
                            'visible' => $currency['visibleInUI'] ?? true,
                        ];
                    }
                }
            }

            // Store enabled currencies
            update_option('hubspot_ecommerce_enabled_currencies', $results['enabled_currencies']);
        }

        // Log sync results
        update_option('hubspot_ecommerce_currency_sync', [
            'timestamp' => current_time('mysql'),
            'company_currency' => $results['company_currency'],
            'enabled_count' => count($results['enabled_currencies']),
            'errors' => $results['errors'],
        ]);

        do_action('hubspot_ecommerce_currencies_synced', $results);

        return $results;
    }

    /**
     * Get enabled currencies from HubSpot or local cache
     *
     * @param bool $force_refresh Force a fresh sync from HubSpot
     * @return array Array of enabled currency codes
     */
    public function get_enabled_currencies($force_refresh = false) {
        if ($force_refresh) {
            $this->sync_currencies();
        }

        $enabled = get_option('hubspot_ecommerce_enabled_currencies', []);

        // If no currencies synced yet, return default set
        if (empty($enabled)) {
            return [
                ['code' => 'USD', 'rate' => 1, 'visible' => true],
                ['code' => 'EUR', 'rate' => 1, 'visible' => true],
                ['code' => 'GBP', 'rate' => 1, 'visible' => true],
                ['code' => 'JPY', 'rate' => 1, 'visible' => true],
            ];
        }

        return $enabled;
    }

    /**
     * Get currency data for a specific currency code
     *
     * @param string $currency_code ISO 4217 currency code
     * @return array Currency data or default
     */
    public function get_currency_data($currency_code) {
        if (isset($this->currency_data[$currency_code])) {
            return $this->currency_data[$currency_code];
        }

        // Return default for unknown currencies
        return [
            'symbol' => $currency_code . ' ',
            'name' => $currency_code,
            'decimals' => 2,
            'position' => 'before',
            'thousands' => ',',
            'decimal' => '.',
        ];
    }

    /**
     * Format price according to currency rules
     *
     * @param float $price Price to format
     * @param string $currency_code Currency code (defaults to site setting)
     * @return string Formatted price
     */
    public function format_price($price, $currency_code = null) {
        if (is_null($currency_code)) {
            $currency_code = get_option('hubspot_ecommerce_currency', 'USD');
        }

        $currency = $this->get_currency_data($currency_code);

        // Format number with proper decimals and separators
        $formatted_number = number_format(
            floatval($price),
            $currency['decimals'],
            $currency['decimal'],
            $currency['thousands']
        );

        // Add currency symbol in proper position
        if ($currency['position'] === 'before') {
            return $currency['symbol'] . $formatted_number;
        } else {
            return $formatted_number . ' ' . $currency['symbol'];
        }
    }

    /**
     * Get currency symbol
     *
     * @param string $currency_code Currency code
     * @return string Currency symbol
     */
    public function get_currency_symbol($currency_code = null) {
        if (is_null($currency_code)) {
            $currency_code = get_option('hubspot_ecommerce_currency', 'USD');
        }

        $currency = $this->get_currency_data($currency_code);
        return $currency['symbol'];
    }

    /**
     * Get all available currency data
     *
     * @return array All currency data
     */
    public function get_all_currency_data() {
        return $this->currency_data;
    }
}
