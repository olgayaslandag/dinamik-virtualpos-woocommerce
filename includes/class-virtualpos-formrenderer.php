<?php
class VirtualPOS_FormRenderer {
    public static function render($order, $single_payment_total, $params) {
        
        wc_print_notices(); // Başarısız ödeme sonrası anında notice göster
        VirtualPOS_Helper::load_template('form', compact('order', 'single_payment_total', 'params'));



        // PayTR taksit oranlarını getir
        require_once plugin_dir_path(__FILE__) . 'class-payment-gateway.php';
        $installmentService = new Installment_Service(new Dinamik_VirtualPOS_Gateway());
        $result = $installmentService->fetch_installments();        
        
        $body = $result['data'];
        $oranlar = $body['oranlar'] ?? [];       
        $amount = $order->get_total();
        VirtualPOS_Helper::load_template('rates-old', compact('oranlar', 'amount'));
        
    }
}