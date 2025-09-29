<?php
if (!defined('ABSPATH')) exit;

class Dinamik_VirtualPOS_Assets 
{
    public static function init($gateway) 
    { 
        add_action('wp_enqueue_scripts', function() use ($gateway) {
            if (is_checkout() && $gateway->is_available()) {
                self::enqueue_styles();
                self::enqueue_scripts($gateway);
            }
        });
    }

    private static function enqueue_styles() 
    {
        wp_enqueue_style(
            'dinamik-virtualpos-main',
            plugin_dir_url(__FILE__) . '../assets/css/style.css',
            [],
            '1.1.0'
        );
    }

    private static function enqueue_scripts($gateway) 
    {
        // Sadece ödeme sayfasında ve gateway aktifse
        if (!is_wc_endpoint_url('order-pay') || !$gateway->is_available()) {
            return;
        }

        global $wp;
        $order_id = absint($wp->query_vars['order-pay'] ?? 0);
        
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $product_price = $order->get_total();
        $merchant_id   = $gateway->get_option('merchant_id');

        // Ana script ve bağımlılıkları
        // Utilities.js
        wp_enqueue_script(
            'dinamik-virtualpos-utilities',
            plugin_dir_url(__FILE__) . '../assets/js/Utilities.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // CardFormatter.js
        wp_enqueue_script(
            'dinamik-virtualpos-cardformatter',
            plugin_dir_url(__FILE__) . '../assets/js/CardFormatter.js',
            ['jquery', 'dinamik-virtualpos-utilities'],
            '1.0.0',
            true
        );

        // ExpiryHandler.js
        wp_enqueue_script(
            'dinamik-virtualpos-expiryhandler',
            plugin_dir_url(__FILE__) . '../assets/js/ExpiryHandler.js',
            ['jquery', 'dinamik-virtualpos-cardformatter'],
            '1.0.0',
            true
        );

        // CVVHandler.js
        wp_enqueue_script(
            'dinamik-virtualpos-cvvhandler',
            plugin_dir_url(__FILE__) . '../assets/js/CVVHandler.js',
            ['jquery', 'dinamik-virtualpos-cardformatter'],
            '1.0.0',
            true
        );

        // CardNameHandler.js
        wp_enqueue_script(
            'dinamik-virtualpos-cardnamehandler',
            plugin_dir_url(__FILE__) . '../assets/js/CardNameHandler.js',
            ['jquery', 'dinamik-virtualpos-cardformatter'],
            '1.0.0',
            true
        );

        // ErrorHandler.js
        wp_enqueue_script(
            'dinamik-virtualpos-errorhandler',
            plugin_dir_url(__FILE__) . '../assets/js/ErrorHandler.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // BinChecker.js
        wp_enqueue_script(
            'dinamik-virtualpos-binchecker',
            plugin_dir_url(__FILE__) . '../assets/js/BinChecker.js',
            ['jquery', 'dinamik-virtualpos-cardformatter'],
            '1.0.07',
            true
        );

        // HiddenFields.js
        wp_enqueue_script(
            'dinamik-virtualpos-hiddenfields',
            plugin_dir_url(__FILE__) . '../assets/js/HiddenFields.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // InstallmentHandler.js
        wp_enqueue_script(
            'dinamik-virtualpos-installmenthandler',
            plugin_dir_url(__FILE__) . '../assets/js/InstallmentHandler.js',
            ['jquery'],
            '1.0.11',
            true
        );

        // VirtualPOS.js
        wp_enqueue_script(
            'dinamik-virtualpos-virtualpos',
            plugin_dir_url(__FILE__) . '../assets/js/VirtualPOS.js',
            [
                'jquery',
                'dinamik-virtualpos-utilities',
                'dinamik-virtualpos-cardformatter',
                'dinamik-virtualpos-expiryhandler',
                'dinamik-virtualpos-cvvhandler',
                'dinamik-virtualpos-cardnamehandler',
                'dinamik-virtualpos-errorhandler',
                'dinamik-virtualpos-binchecker',
                'dinamik-virtualpos-hiddenfields',
                'dinamik-virtualpos-installmenthandler'
            ],
            '1.0.0',
            true
        );

        // Ana init script (main.js)
        wp_enqueue_script(
            'dinamik-virtualpos-main',
            plugin_dir_url(__FILE__) . '../assets/js/main.js',
            [
                'jquery',
                'dinamik-virtualpos-utilities',
                'dinamik-virtualpos-cardformatter',
                'dinamik-virtualpos-expiryhandler',
                'dinamik-virtualpos-cvvhandler',
                'dinamik-virtualpos-cardnamehandler',
                'dinamik-virtualpos-errorhandler',
                'dinamik-virtualpos-binchecker',
                'dinamik-virtualpos-hiddenfields',
                'dinamik-virtualpos-installmenthandler',
                'dinamik-virtualpos-virtualpos'
            ],
            '1.1.53',
            true
        );

        // PayTR taksit tablosu
        $script_url = add_query_arg([
            'merchant_id' => $merchant_id,
            'amount'      => number_format($product_price, 2, '.', ''),
            'taksit'      => 6,
            'tumu'        => 0,
            'token'       => 'c050960c0d24067b7f935e8898497cae5a88e0821ecdf268455a5683d8f7705f'
        ], 'https://www.paytr.com/odeme/taksit-tablosu/v2');

        /*
        wp_enqueue_script(
            'paytr-installment-table',
            $script_url,
            [],
            null,
            true
        );
        */

        // Main script parametreleri
        wp_localize_script('dinamik-virtualpos-main', 'virtualpos_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('virtualpos_nonce')
        ]);
    }
}