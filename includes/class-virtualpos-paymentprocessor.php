<?php
class VirtualPOS_PaymentProcessor 
{
    private $gateway;

    public function __construct($gateway) 
    {
        $this->gateway = $gateway;
    }

    public function handle_form_submission($order_id) 
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_number'])) {
            return $this->process_payment_form($order_id);
        }
        return false;
    }

    private function process_payment_form($order_id) 
    {        
        if (!$this->verify_nonce()) {
            wc_add_notice('Güvenlik doğrulaması başarısız.', 'error');
            return false;
        }

        $order = wc_get_order($order_id);
        $card_data = $this->sanitize_card_data($_POST);

        $payment_result = $this->send_payment_to_api($order, $card_data);        
        
        if ($payment_result['success']) {
            $order->payment_complete();
            $order->add_order_note('Ödeme başarılı: ' . $payment_result['message']);
            wp_redirect($this->gateway->get_return_url($order));
            exit;
        } else {
            wc_add_notice('Ödeme başarısız: ' . $payment_result['message'], 'error');
            wp_redirect($order->get_checkout_payment_url(true));
            exit;
        }
    }

    private function verify_nonce() 
    {
        return wp_verify_nonce($_POST['virtualpos_payment_nonce'], 'virtualpos_payment_action');
    }

    private function sanitize_card_data($post_data) 
    {
        return [
            'number'      => sanitize_text_field($post_data['card_number']),
            'name'        => sanitize_text_field($post_data['card_name']),
            'expiry'      => sanitize_text_field($post_data['card_expiry']),
            'cvv'         => sanitize_text_field($post_data['card_cvv']),
            'installment' => intval($post_data['installment'])
        ];
    }

    private function send_payment_to_api($order, $card_data) 
    {
        // Base params helper’dan geliyor
        $post_vals = VirtualPOS_Helper::build_base_params($order, $this->gateway, $card_data);

        // Kart bilgilerini ekle
        $post_vals = array_merge($post_vals, [
            'cc_owner'          => $card_data['name'],
            'card_number'       => preg_replace('/\s+/', '', $card_data['number']),
            'expiry_month'      => substr($card_data['expiry'], 0, 2),
            'expiry_year'       => substr($card_data['expiry'], -2),
            'cvv'               => $card_data['cvv'],
            'installment_count' => $card_data['installment'] > 1 ? $card_data['installment'] : 0,            
        ]);

        // Gateway ayarlarından test_mode oku
        $test_mode = $this->gateway->get_option('test_mode') === 'yes';

        // WordPress HTTP API ile POST isteği
        $response = wp_remote_post( "https://www.paytr.com/odeme", [
            'timeout'   => 90,
            'sslverify' => !$test_mode, // test_mode YES ise false, değilse true
            'body'      => $post_vals,
        ] );

        // Hata kontrolü
        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => 'Bağlantı hatası: ' . $response->get_error_message(),
            ];
        }

        // Response body’yi al
        $body = wp_remote_retrieve_body( $response );
        $result = json_decode( $body, true );

        // Başarılı yanıt
        if ( $result && isset($result['status']) && $result['status'] === 'success' ) {
            return [
                'success' => true,
                'message' => 'Ödeme başarılı',
                'data'    => $result,
            ];
        }

        // Başarısız yanıt
        return [
            'success' => false,
            'message' => $result['reason'] . json_encode($post_vals) ?? $body,
            'data'    => $result,
        ];
    }
}