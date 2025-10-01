<?php
class Dinamik_VirtualPOS_Gateway extends WC_Payment_Gateway 
{
    public function __construct() 
    {
        $this->setup_gateway_properties();
        $this->init_form_fields();
        $this->init_settings();
        $this->setup_gateway_settings();
        $this->register_hooks();
        
    }

    private function setup_gateway_properties() 
    {
        $this->id = 'dvirtualpos';
        $this->icon = '';
        $this->has_fields = false;
        $this->method_title = __('Sanal POS', 'woocommerce');
        $this->method_description = __('Basit bir özel sanal POS entegrasyonu.', 'woocommerce');
        $this->supports = array('products');
        $this->countries = array('TR');
    }

    private function setup_gateway_settings() 
    {
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->order_button_text = __('Ödeme', 'woocommerce');

        Dinamik_VirtualPOS_Assets::init($this);
    }

    private function register_hooks() 
    {
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
        add_action('woocommerce_api_wc_gateway_paytrcheckout', [$this, 'callback']);
    }

    public function init_form_fields() 
    {
        $this->form_fields = VirtualPOS_FormFields::get();
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        
        if (!$order->is_paid()) {
            return array(
                'result'   => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }
        
        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

    public function receipt_page($order_id) 
    {
        $order = wc_get_order($order_id);
        
        if ($order->is_paid()) {
            echo '<p>Ödemeniz alındı. Teşekkür ederiz!</p>';
            return;
        }
        
        $discount_rate = intval(get_option('_iskonto_nakit', 10));
        $discount_multiplier = (100 - $discount_rate) / 100;
        $single_payment_total = $order->get_total() * $discount_multiplier;
        
        $params = VirtualPOS_Helper::build_base_params($order, $this, []);
        
        echo '<div class="payment-container">';
        VirtualPOS_FormRenderer::render($order, $single_payment_total, $params, $this);
        echo '</div>';
    }    

    public function is_available()
    {
        return 'yes' === $this->enabled;
    }

    public function callback($order)
    {
        if (empty($_POST)) {
            die();
        }

        error_log('PayTR Callback Received: ' . print_r($_POST, true));
        CheckoutCallback::callback($_POST, $this, $order);
    } 
}