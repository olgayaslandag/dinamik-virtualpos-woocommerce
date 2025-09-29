<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Plugin Name: Custom SanalPOS Gateway
 * Description: WooCommerce için basit sanal POS örneği
 * Version: 1.0
 */


//require_once plugin_dir_path(__FILE__) . 'class-dinamik-virtualpos.php';
//require_once plugin_dir_path(__FILE__) . 'includes/class-virtualpos-rates.php';


require_once plugin_dir_path(__FILE__) . 'includes/class-virtualpos-helper.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-virtualpos-paymentprocessor.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-virtualpos-formrenderer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-virtualpos-formfields.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-virtualpos-assets.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-virtualpos-installment.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-virtualpos-binservice.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-virtualpos-callback.php';

add_action('plugins_loaded', 'dinamik_virtualpos_init', 11);
function dinamik_virtualpos_init() 
{
    new Dinamik_VirtualPOS();
}


class Dinamik_VirtualPOS {
    
    public function __construct() 
    {
        add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));    
        

        add_action('wp_ajax_dinamik_bin_lookup', [$this, 'dinamik_bin_lookup']);
        add_action('wp_ajax_nopriv_dinamik_bin_lookup', [$this, 'dinamik_bin_lookup']);

        add_action('wp_ajax_dinamik_retokenize', [$this, 'retokenize']);
        add_action('wp_ajax_nopriv_dinamik_retokenize', [$this, 'retokenize']);
    }
    
    public function add_gateway($methods) 
    {
        require_once plugin_dir_path(__FILE__) . 'includes/class-payment-gateway.php';
        $methods[] = 'Dinamik_VirtualPOS_Gateway';
        return $methods;
    }    

    public function dinamik_bin_lookup() 
    {
        check_ajax_referer('virtualpos_nonce', 'security');
        $bin = sanitize_text_field($_POST['bin'] ?? '');
        if (strlen(preg_replace('/\D/','', $bin)) < 6) {
            wp_send_json_error(['message' => 'Geçersiz BIN']);
        }
        
         
        
        // $gateway nesneni uygun şekilde al
        require_once plugin_dir_path(__FILE__) . 'includes/class-payment-gateway.php';
        $installmentService = new Installment_Service(new Dinamik_VirtualPOS_Gateway());
        $result = $installmentService->fetch_installments();
        //$body = json_decode($result['body'] ?? '{}', true);
        $body = $result['data'];
        $oranlar = $body['oranlar'] ?? [];        

        $service = new Bin_Service((new Dinamik_VirtualPOS_Gateway()));        
        
        $res = $service->fetch_bin_details($bin);
        
        if ($res['success']) {
            $binData = $res['data'];
            $brand = strtolower($binData['brand'] ?? '');
            
            $brandOranlar = $oranlar[$brand] ?? [];
            $prices = VirtualPOS_Helper::hesapla_taksitler($brandOranlar, 14.16, $brand);
            wp_send_json_success([
                'prices' => $prices,
                'oranlar' => $oranlar
            ]);
        } else {
            wp_send_json_error(['message' => $res['message'] ?? 'Hata']);
        }
    }


    public function retokenize() 
    {        
        check_ajax_referer('virtualpos_nonce', 'security');
        
        if ( empty($_POST['order_id']) ) {
            wp_send_json_error(['message' => 'Order ID eksik.']);
        }

        $order_id = absint($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        // $gateway nesneni uygun şekilde al
        require_once plugin_dir_path(__FILE__) . 'includes/class-payment-gateway.php';
        $params = VirtualPOS_Helper::build_base_params(
            $order,
            new Dinamik_VirtualPOS_Gateway(),
            ['installment' => absint($_POST['installment'])],
            $_POST['price']
        );

        wp_send_json_success([
            'message' => 'Retoken başarılı',
            'params'  => $params,
        ]);
    }
}