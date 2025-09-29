<?php
class VirtualPOS_Helper {
    public static function get_user_ip() 
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    public static function generate_token($params, $merchant_key, $merchant_salt) 
    {
        $hash_str = $params['merchant_id']
            . $params['user_ip']
            . $params['merchant_oid']
            . $params['email']
            . $params['payment_amount']
            . $params['payment_type']
            . $params['installment_count']
            . $params['currency']
            . $params['test_mode']
            . $params['non_3d'];

        return base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, true));
    }

    public static function build_base_params($order, $gateway, $card_data, $price=null)
    {
        $merchant_id   = $gateway->get_option('merchant_id');
        $merchant_key  = $gateway->get_option('merchant_key');
        $merchant_salt = $gateway->get_option('merchant_salt');
        $test_mode     = $gateway->get_option('test_mode') === 'yes' ? 1 : 0;

        $basket = [];
        foreach ($order->get_items() as $item) {
            $basket[] = [$item->get_name(), number_format($item->get_total(), 2, '.', ''), $item->get_quantity()];
        }
        $user_basket = htmlentities(json_encode($basket));

        $installment_count = (isset($card_data['installment']) && $card_data['installment'] > 1) ? $card_data['installment'] : 0;
        $no_installment = ($installment_count === 0) ? 1 : 0;
        $price = $price ?? $order->get_total();

        $params = [
            'merchant_id'     => $merchant_id,
            'user_ip'         => self::get_user_ip(),
            'merchant_oid'    => $order->get_id() . time(),
            'email'           => $order->get_billing_email(),
            'payment_type'    => 'card',
            'payment_amount'  => $price,
            'currency'        => 'TL',
            'test_mode'       => $test_mode,
            'non_3d'          => 0,
            'merchant_ok_url' => $gateway->get_return_url($order),
            'merchant_fail_url' => $order->get_checkout_payment_url(true),
            //'merchant_fail_url' => $order->get_cancel_order_url(),
            'user_name'       => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'user_address'    => $order->get_billing_address_1(),
            'user_phone'      => $order->get_billing_phone(),
            'user_basket'     => $user_basket,
            'debug_on'        => $test_mode,
            'client_lang'     => 'tr',
            'non3d_test_failed' => 0,
            'installment_count' => $installment_count,
            'no_installment'    => $no_installment,
            'lang' => 'tr',
            'card_type' => ''
        ];

        $params['paytr_token'] = self::generate_token($params, $merchant_key, $merchant_salt);

        return $params;
    }

    public static function load_template($template_name, $data = []) 
    {
        extract($data);
        include plugin_dir_path(__FILE__) . "../views/{$template_name}.php";
    }

    public static function hesapla_taksitler($oranlar, $fiyat, $brand) 
    {
        $sonuc = [];
        foreach ($oranlar as $key => $oran) {
            // "taksit_3" -> 3
            preg_match('/taksit_(\d+)/', $key, $matches);
            $taksitSayisi = intval($matches[1] ?? 0);

            if ($taksitSayisi > 0) {
                // Toplam fiyat = ana fiyat + oran
                $toplam = $fiyat * (1 + $oran / 100);

                // Aylık taksit
                $aylik = $toplam / $taksitSayisi;

                $sonuc[$taksitSayisi] = [
                    'toplam' => round($toplam, 2),
                    'aylik'  => round($aylik, 2),
                    'oran' => $oran,
                    'price' => round($fiyat, 2)
                ];
            }
        }

        // Peşin (taksit 1 için oran yok, direkt fiyat)
        $sonuc[1] = [
            'toplam' => round($fiyat * 0.9, 2),
            'aylik'  => round($fiyat * 0.9, 2),
            'oran' => 0.9,
            'price' => round($fiyat, 2),
            'brand' => $brand,
        ];

        ksort($sonuc);
        return $sonuc;
    }
}