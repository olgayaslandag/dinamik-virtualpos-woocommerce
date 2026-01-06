<?php
class VirtualPOS_Helper {
    
    public static function get_user_ip() 
    {
        //return '176.41.31.147';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    private static function get_discount_multiplier()
    {
        $discount_rate = intval(get_option('_iskonto_nakit', 10));
        return (100 - $discount_rate) / 100;
    }

    public static function calculate_single_price($order)
    {
        return $order->get_total() * self::get_discount_multiplier();
    }
    
    public static function build_basket($order, $apply_discount = false, $target_amount = null)
    {
        $basket = [];
        $basket_total = 0;
        $discount_multiplier = $apply_discount ? self::get_discount_multiplier() : 1;
        
        // 1. Ürünleri ekle
        foreach ($order->get_items() as $item) {
            $item_price = $item->get_total() * $discount_multiplier;
            $item_price_formatted = number_format($item_price, 2, '.', '');
            
            $basket[] = [
                $item->get_name(),
                $item_price_formatted,
                (int)$item->get_quantity()
            ];
            
            $basket_total += $item_price;
        }
        
        // 2. Kargo ekle (varsa)
        $shipping_total = $order->get_shipping_total();
        if ($shipping_total > 0) {
            $shipping_price = $shipping_total * $discount_multiplier;
            $shipping_price_formatted = number_format($shipping_price, 2, '.', '');
            
            $basket[] = [
                'Kargo',
                $shipping_price_formatted,
                1
            ];
            
            $basket_total += $shipping_price;
        }
        
        // 3. Vergiler ekle (varsa ve ayrı gösterilmesi gerekiyorsa)
        $tax_total = $order->get_total_tax();
        if ($tax_total > 0 && $apply_discount) {
            // Not: Genelde vergiler ürün fiyatına dahildir, 
            // ancak ayrı gösterilmesi gerekirse:
            $tax_price = $tax_total * $discount_multiplier;
            $tax_price_formatted = number_format($tax_price, 2, '.', '');
            
            $basket[] = [
                'KDV',
                $tax_price_formatted,
                1
            ];
            
            $basket_total += $tax_price;
        }
        
        // 4. Boş sepet kontrolü
        if (empty($basket)) {
            $default_price = $target_amount ?? $order->get_total();
            $basket[] = [
                'Sipariş',
                number_format($default_price, 2, '.', ''),
                1
            ];
            $basket_total = floatval($default_price);
        }
        
        // 5. KRITIK: Sepet toplamı ile hedef tutar arasındaki farkı düzelt
        if ($target_amount !== null) {
            $diff = floatval($target_amount) - $basket_total;
            
            if (abs($diff) > 0.01) {  // 1 kuruş tolerans
                $basket[] = [
                    $diff > 0 ? 'Diğer Ücretler' : 'İndirim',
                    number_format(abs($diff), 2, '.', ''),
                    1
                ];
                $basket_total = floatval($target_amount);
            }
        }
        
        return [
            'basket' => $basket,
            'total' => $basket_total
        ];
    }

    public static function encode_basket($basket)
    {
        return htmlentities(json_encode($basket));
        //return base64_encode(json_encode($basket, JSON_UNESCAPED_UNICODE));
    }

    public static function validate_basket($basket, $payment_amount)
    {
        $total = 0;
        
        foreach ($basket as $item) {
            $price = floatval($item[1]);
            $quantity = intval($item[2]);
            $total += $price * $quantity;
        }
        
        $diff = abs(floatval($payment_amount) - $total);
        
        if ($diff > 0.01) {
            error_log(sprintf(
                'PayTR Basket Validation Error - Payment: %s | Basket Total: %s | Diff: %s',
                $payment_amount,
                number_format($total, 2),
                number_format($diff, 2)
            ));
            return false;
        }
        
        return true;
    }

    private static function price_format($price): array
    {
        
        $price = str_replace(',', '.', (string) $price);
        $price = preg_replace('/[^0-9.]/', '', $price);
        $price = number_format((float) $price, 2, '.', '');

        $price_krs = (int) str_replace('.', '', $price);

        return [
            'price_tl'  => $price,
            'price_krs' => $price_krs,
        ];
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

        $token = base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, true));

        return $token;
    }

    
    public static function build_base_params($order, $gateway, $installment_count = 0, $price = null)
    {
        // Gateway ayarları
        $merchant_id   = $gateway->get_option('merchant_id');
        $merchant_key  = $gateway->get_option('merchant_key');
        $merchant_salt = $gateway->get_option('merchant_salt');
        $test_mode     = $gateway->get_option('test_mode') === 'yes' ? '1' : '0';

        // Taksit kontrolü - 0 olmalı kalmalı
        $installment_count = max(0, intval($installment_count));
        $is_single_payment = ($installment_count === 0);
        
        // Fiyat belirleme
        $final_price = $is_single_payment 
            ? self::calculate_single_price($order) 
            : ($price ?? $order->get_total());

        $prices = self::price_format($final_price);
         
        /*
        // Sepet oluştur
        $basket_data = self::build_basket(
            $order, 
            $is_single_payment,  // Peşin ödemede indirim uygula
            $prices['price_tl']        // Hedef tutar
        );
        
        // Sepet doğrula (debug için)
        if ($test_mode == '1') {
            self::validate_basket($basket_data['basket'], $prices['price_tl']);
        }

        */
        $user_basket = self::encode_basket([
            ['PayTR Ürünü', $prices['price_tl'], 1]
        ]);
        
        // Sepeti encode et
        //$user_basket = self::encode_basket($basket_data['basket']);
        
        // PayTR parametreleri
        $params = [
            // Zorunlu temel bilgiler
            'merchant_id'        => $merchant_id,
            'user_ip'            => self::get_user_ip(),
            'merchant_oid'       => time() . 'PAYTRWOO' . $order->get_id(),
            'email'              => $order->get_billing_email(),
            'payment_type'       => 'card',
            'payment_amount'     => $prices['price_tl'],
            'installment_count'  => $installment_count, 
            'currency'           => 'TL',
            'test_mode'          => $test_mode,
            'non_3d'             => '0',
            'client_lang'        => 'tr',            
            'max_installment'    => '6',            
            'card_type'          => '',
            
            // URL'ler
            'merchant_ok_url'    => $gateway->get_return_url($order),
            'merchant_fail_url'  => $order->get_checkout_payment_url(true),
            
            // Müşteri bilgileri
            'user_name'          => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'user_address'       => $order->get_billing_address_1(),
            'user_phone'         => $order->get_billing_phone(),
            'user_basket'        => $user_basket,
            
            // Debug ve test
            'debug_on'           => $test_mode,
            'non3d_test_failed'  => '0',
        ];

        $params['paytr_token'] = self::generate_token($params, $merchant_key, $merchant_salt);

        return $params;
    }

    
    public static function load_template($template_name, $data = []) 
    {
        extract($data);
        include plugin_dir_path(__FILE__) . "../views/{$template_name}.php";
    }

    public static function hesapla_taksitler($oranlar, $fiyat, $brand, $reflect) 
    {
        $sonuc = [];
        
        // Taksitli ödemeler
        foreach ($oranlar as $key => $oran) {
            preg_match('/taksit_(\d+)/', $key, $matches);
            $taksitSayisi = intval($matches[1] ?? 0);

            if ($taksitSayisi > 0) {
                $toplam = $reflect === "yes" 
                    ? $fiyat * (1 + $oran / 100) 
                    : $fiyat;
                
                $aylik = $toplam / $taksitSayisi;

                $sonuc[$taksitSayisi] = [
                    'toplam' => round($toplam, 2),
                    'aylik'  => round($aylik, 2),
                    'price'  => round($fiyat, 2),
                    'oran'   => $oran,
                    //'price'  => intval($toplam * 100),
                    //'toplam' => intval($toplam * 100),
                    //'aylik'  => intval($aylik * 100),
                    'brand'  => $brand
                ];
            }
        }

        // Peşin ödeme (%10 indirim) - installment_count = 0
        $discount_multiplier = self::get_discount_multiplier();
        $sonuc[0] = [
            'toplam' => round($fiyat * $discount_multiplier, 2),
            'aylik'  => round($fiyat * $discount_multiplier, 2),
            'oran'   => $discount_multiplier,
            'price'  => round($fiyat, 2),
            'brand'  => $brand,
        ];

        ksort($sonuc);
        return $sonuc;
    }
}