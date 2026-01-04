
    <?php        

        $merchant_ok_url="http://site-ismi/basarili";
        $merchant_fail_url= $order->get_checkout_payment_url(true);

        $user_basket = htmlentities(json_encode(array(
            array("Altis Renkli Deniz Yatağı - Mavi", "18.00", 1),
            array("Pharmasol Güneş Kremi 50+ Yetişkin & Bepanthol Cilt Bakım Kremi", "33,25", 2),
            array("Bestway Çocuklar İçin Plaj Seti Beach Set ÇANTADA DENİZ TOPU-BOT-KOLLUK", "45,42", 1)
        )));

        //srand(time(null));
        $merchant_oid = rand();

        $test_mode="0";

        //3d'siz işlem
        $non_3d="0";

        //Ödeme süreci dil seçeneği tr veya en
        $client_lang = "tr";

        //non3d işlemde, başarısız işlemi test etmek için 1 gönderilir (test_mode ve non_3d değerleri 1 ise dikkate alınır!)
        $non3d_test_failed="0";

        if( isset( $_SERVER["HTTP_CLIENT_IP"] ) ) {
            $user_ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif( isset( $_SERVER["HTTP_X_FORWARDED_FOR"] ) ) {
            $user_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            $user_ip = $_SERVER["REMOTE_ADDR"];
        }

        $email = "testnon3d@paytr.com";

        // 100.99 TL ödeme
        $payment_amount = $order->get_total();
        $currency="TL";
        //
        $payment_type = "card";

      $card_type = "bonus";       // Alabileceği değerler; advantage, axess, combo, bonus, cardfinans, maximum, paraf, world, saglamkart
      $installment_count = 0;

        $post_url = "https://www.paytr.com/odeme";

        $hash_str = $merchant_id . $user_ip . $merchant_oid . $email . $payment_amount . $payment_type . $installment_count. $currency. $test_mode. $non_3d;
        $token = base64_encode(hash_hmac('sha256',$hash_str.$merchant_salt,$merchant_key,true));
    ?>

    <body>
        <?php print_r($_POST); ?>
        <form action="<?php echo $post_url;?>" method="post">
          Kart Sahibi Adı: <input type="text" name="cc_owner" value="TEST KARTI"><br>
          Kart Numarası: <input type="text" name="card_number" value="9792030394440796"><br>
          Kart Son Kullanma Ay: <input type="text" name="expiry_month" value="12" ><br>
          Kart Son Kullanma Yıl: <input type="text" name="expiry_year" value="99"><br>
          Kart Güvenlik Kodu: <input type="text" name="cvv" value="000"><br>


          <input type="hidden" name="merchant_id" value="<?php echo $merchant_id;?>">
          <input type="hidden" name="user_ip" value="<?php echo $user_ip;?>">
          <input type="hidden" name="merchant_oid" value="<?php echo $merchant_oid;?>">
          <input type="hidden" name="email" value="<?php echo $email;?>">
          <input type="hidden" name="payment_type" value="<?php echo $payment_type;?>">
          <input type="hidden" name="payment_amount" value="<?php echo $payment_amount;?>">
          <input type="hidden" name="currency" value="<?php echo $currency;?>">
          <input type="hidden" name="test_mode" value="<?php echo $test_mode;?>">
          <input type="hidden" name="non_3d" value="<?php echo $non_3d;?>">
          <input type="hidden" name="merchant_ok_url" value="<?php echo $merchant_ok_url;?>">
          <input type="hidden" name="merchant_fail_url" value="<?php echo $merchant_fail_url;?>">
          <input type="hidden" name="user_name" value="Paytr Test">
          <input type="hidden" name="user_address" value="test test test">
          <input type="hidden" name="user_phone" value="05555555555">
          <input type="hidden" name="user_basket" value="<?php echo $user_basket; ?>">
          <input type="hidden" name="debug_on" value="1">
          <input type="hidden" name="client_lang" value="<?php echo $client_lang; ?>">
          <input type="hidden" name="paytr_token" value="<?php echo $token; ?>">
          <input type="hidden" name="non3d_test_failed" value="<?php echo $non3d_test_failed; ?>">
          <input type="hidden" name="installment_count" value="<?php echo $installment_count; ?>">
          <input type="hidden" name="card_type" value="<?php echo $card_type; ?>">
          <input type="hidden" name="no_installment" value="0">
          <input type="hidden" name="max_installment" value="12">
          <input type="hidden" name="lang" value="tr">
          <input type="submit" value="Submit">
        </form>