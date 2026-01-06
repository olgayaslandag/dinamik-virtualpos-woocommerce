<?php
class VirtualPOS_FormRenderer 
{
    public static function render($order, $single_payment_total, $params, $gateway) 
    {
        wc_print_notices(); // Başarısız ödeme sonrası anında notice göster

        self::render_form($order, $single_payment_total, $params);
        self::render_rates($order, $gateway);
    }

    private static function render_form($order, $single_payment_total, $params) 
    {
        VirtualPOS_Helper::load_template('form', compact('order', 'single_payment_total', 'params'));
    }

    private static function render_rates($order, $gateway) 
    {
        $installmentService = new Installment_Service($gateway);
        $result = $installmentService->fetch_installments();        
        
        $oranlar = $result['data']['oranlar'] ?? [];       
        $amount  = $order->get_total();
        $reflect = $gateway->get_option('reflect');


        // Her kart için peşin fiyat ekle
        foreach ($oranlar as $cardType => &$rates) {
            $rates = array_merge(
                ['tek_cekim' => $amount * 0.9],
                $rates
            );
        }
        VirtualPOS_Helper::load_template('rates', compact('oranlar', 'amount', 'reflect'));
    }
}