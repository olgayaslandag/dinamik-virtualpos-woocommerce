<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CheckoutCallback 
{
    public static function callback( $post, $gateway ) 
    {
        // HASH KONTROL
        $hash = base64_encode(
            hash_hmac(
                'sha256',
                sanitize_text_field($post['merchant_oid'])
                . $gateway->get_option('merchant_salt')
                . sanitize_text_field($post['status'])
                . sanitize_text_field($post['total_amount']),
                $gateway->get_option('merchant_key'),
                true
            )
        );

        if ($hash !== sanitize_text_field($post['hash'])) {
            die('PAYTR notification failed: bad hash');
        }

        // ORDER BUL
        $order_id = explode('PAYTRWOO', sanitize_text_field($post['merchant_oid']));
        $order    = wc_get_order($order_id[1]);

		if (!$order) {
            die('Order not found');
        }

        // SADECE BEKLEYEN / FAILED
        if (!in_array($order->get_status(), ['pending', 'failed'], true)) {
            echo 'OK';
            exit;
        }

        // BAŞARILI ÖDEME
        if (sanitize_text_field($post['status']) === 'success') {

            // 👉 PEŞİN İNDİRİMİ (ÖNCE)
            self::apply_discount($order, $post);

            $payment_amount = round($post['payment_amount'] / 100, 2);
            $total_amount = round((float) $order->get_total(), 2);
            $installment_dif = round($total_amount - $payment_amount, 2);

            // TAKSİT FARKI
            if ($post['installment_count'] == 1) {
                self::apply_installment_fee($order, $installment_dif);
            }

            // NOT
            $note  = "PAYTR - Ödeme Onaylandı\n";
            $note .= "Toplam: " . wc_price($total_amount, ['currency' => $order->get_currency()]) . "\n";
            $note .= "Ödenen: " . wc_price($payment_amount, ['currency' => $order->get_currency()]) . "\n";

            if (!empty($post['installment_count'])) {
                $note .= 'Taksit: ' . ($post['installment_count'] == 1 ? 'Tek Çekim' : $post['installment_count'] . ' Taksit') . "\n";
            }

            $order->add_order_note(nl2br($note));
            $order->payment_complete();

        } else {

            // BAŞARISIZ
            $note  = "PAYTR - Ödeme Başarısız\n";
            $note .= sanitize_text_field($post['failed_reason_code']) . ' - ';
            $note .= sanitize_text_field($post['failed_reason_msg']);

            $order->add_order_note(nl2br($note));
            $order->update_status('failed');
        }

        do_action('payment_commit_hook', $post);

        echo 'OK';
        exit;
    }

    /**
     * PEŞİN ÖDEME İNDİRİMİ
     */
    private static function apply_discount($order, $post)
    {
        $installment_count = intval($post['installment_count'] ?? 0);

        if ($installment_count !== 0) {
            return;
        }

        // DAHA ÖNCE EKLENMİŞ Mİ?
        foreach ($order->get_items('fee') as $fee) {
            if (strpos($fee->get_name(), 'Peşin Ödeme İndirimi') !== false) {
                return;
            }
        }

        $discount_rate = intval(get_option('_iskonto_nakit', 10));
        if ($discount_rate <= 0) {
            return;
        }

        $discount_amount = round($order->get_subtotal() * ($discount_rate / 100), 2);
        if ($discount_amount <= 0) {
            return;
        }

        $discount = new WC_Order_Item_Fee();
        $discount->set_name('Peşin Ödeme İndirimi (%' . $discount_rate . ')');
        $discount->set_amount(-$discount_amount);
        $discount->set_total(-$discount_amount);
        $discount->set_tax_status('none');

        $order->add_item($discount);
        $order->calculate_totals();
    }

    /**
     * ➕ TAKSİT FARKI
     */
    private static function apply_installment_fee(WC_Order $order, float $amount)
    {
        // Önceden eklenmiş mi kontrol et
        foreach ($order->get_items('fee') as $fee) {
            if (in_array($fee->get_name(), ['Taksit Farkı', 'Tek Çekim İndirimi'], true)) {
                return;
            }
        }

        // İndirim = negatif fee
        $discount = abs($amount);

        $fee = new WC_Order_Item_Fee();
        $fee->set_name('Tek Çekim İndirimi');

        // 🔴 KRİTİK: Fee KDV HARİÇ net tutar
        $fee->set_amount(-$discount);
        $fee->set_total(-$discount);

        // Vergiyi TAMAMEN kapat
        $fee->set_tax_status('none');
        $fee->set_tax_class('');
        $fee->set_taxes([
            'total'    => [],
            'subtotal' => [],
        ]);
        $fee->set_total_tax(0);

        $order->add_item($fee);

        // ❗ Vergileri yeniden dağıtmasın
        $order->calculate_totals(false);
    }
}