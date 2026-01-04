<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Plugin Name: Custom SanalPOS Gateway
 * Description: WooCommerce için basit sanal POS örneği
 * Version: 1.0
*/

# Zorunlu sınıfları yükle
$includes = [
    'class-virtualpos-helper.php',
    'class-virtualpos-paymentprocessor.php',
    'class-virtualpos-formrenderer.php',
    'class-virtualpos-formfields.php',
    'class-virtualpos-assets.php',
    'class-virtualpos-installment.php',
    'class-virtualpos-binservice.php',
    'class-virtualpos-callback.php',
];
foreach ($includes as $file) {
    require_once plugin_dir_path(__FILE__) . "includes/{$file}";
}

add_action('plugins_loaded', 'dinamik_virtualpos_init', 11);
function dinamik_virtualpos_init() 
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-payment-gateway.php';    
    new Dinamik_VirtualPOS();
}

class Dinamik_VirtualPOS {

    public function __construct() 
    {        
        add_filter('woocommerce_payment_gateways', [$this, 'add_gateway']);

        add_action('wp_ajax_dinamik_bin_lookup', [$this, 'bin_lookup']);
        add_action('wp_ajax_nopriv_dinamik_bin_lookup', [$this, 'bin_lookup']);

        add_action('wp_ajax_dinamik_retokenize', [$this, 'retokenize']);
        add_action('wp_ajax_nopriv_dinamik_retokenize', [$this, 'retokenize']);
    }

    public function add_gateway($methods) 
    {
        $methods[] = 'Dinamik_VirtualPOS_Gateway';
        return $methods;
    }

    public function bin_lookup() 
    {
        check_ajax_referer('virtualpos_nonce', 'security');

        $bin = sanitize_text_field($_POST['bin'] ?? '');
        $order_total = floatval($_POST['order_total'] ?? 0);

        if (strlen(preg_replace('/\D/', '', $bin)) < 6) {
            wp_send_json_error(['message' => 'Geçersiz BIN']);
        }

        $installmentService = new Installment_Service(new Dinamik_VirtualPOS_Gateway());
        $result = $installmentService->fetch_installments();
        $oranlar = $result['data']['oranlar'] ?? [];

        $gateway = new Dinamik_VirtualPOS_Gateway();
        $service = new Bin_Service($gateway);
        $res = $service->fetch_bin_details($bin);

        if (!$res['success']) {
            wp_send_json_error(['message' => $res['message'] ?? 'Hata']);
        }

        $binData = $res['data'];
        $brand = strtolower($binData['brand'] ?? '');
        $brandOranlar = $oranlar[$brand] ?? [];

        $reflect = $gateway->get_option('reflect');
        $prices = VirtualPOS_Helper::hesapla_taksitler($brandOranlar, $order_total, $brand, $reflect);

        wp_send_json_success([
            'prices'  => $prices,
            'oranlar' => $oranlar,
        ]);
    }

    public function retokenize() 
    {
        check_ajax_referer('virtualpos_nonce', 'security');

        $order_id = absint($_POST['order_id'] ?? 0);
        if (!$order_id) {
            wp_send_json_error(['message' => 'Order ID eksik.']);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Sipariş bulunamadı.']);
        }

        $params = VirtualPOS_Helper::build_base_params(
            $order,
            new Dinamik_VirtualPOS_Gateway(),
            absint($_POST['installment_count'] ?? 0),
            round($_POST['price'], 2) ?? null
        );
        
        error_log('PARAMS: ' . print_r($params, true));
        wp_send_json_success([
            'message' => 'Retoken başarılı',
            'params'  => $params,
            //'post'    => $_POST,
        ]);
    }
}