<div class="payment-form-container">
    <h2>Kredi Kartı ile Ödeme</h2>
    
    <?php if(isset($_POST['fail_message'])): ?>
    <div class="paytr-fail-message">
        <?php echo $params['fail_message']; ?>
    </div>
    <?php endif; ?>



    <form class="virtualpos_payment_form" method="post" action="https://www.paytr.com/odeme">
        <?php wp_nonce_field('virtualpos_payment_action', 'virtualpos_payment_nonce'); ?> 
        <input type="hidden" name="single_payment_total" value="<?php echo $single_payment_total; ?>">
        <input type="hidden" name="order_total" value="<?php echo $order->get_total(); ?>">
        <input type="hidden" name="order_id" value="<?php echo $order->get_id(); ?>">
        


        <div class="form-group">
            <label for="card-name">Kart Üzerindeki İsim *</label>
            <input type="text" id="card-name" name="cc_owner" placeholder="AD SOYAD" required />
        </div>

        <div class="form-group">
            <label for="card-number">Kart Numarası *</label>
            <input type="text" id="card-number" name="number" placeholder="0000 0000 0000 0000" 
                   pattern="[0-9]{4}( [0-9]{4}){3}" maxlength="19" required />

            <input type="hidden" name="card_number" value="">
        </div>        

        <div class="form-roww">
            <div class="form-group">
                <label for="card-expiry">Son Kullanma Tarihi *</label>
                <input type="text" id="card-expiry" name="card_expiry" placeholder="AA/YY" 
                       pattern="(0[1-9]|1[0-2])\/([0-9]{2})" maxlength="5" required />

                <input type="hidden" id="expiry-month" name="expiry_month" value="12">
                <input type="hidden" id="expiry-year" name="expiry_year" value="30">
            </div>
            
            <div class="form-group">
                <label for="card-cvv">CVV *</label>
                <input type="number" id="card-cvv" name="cvv" placeholder="000"
                       pattern="[0-9]{3,4}" maxlength="3" required />
            </div>
        </div>

        <div class="form-group">
            <label for="installment_count">Taksit Seçeneği *</label>
            <select id="installment_count" name="installment_count" required onchange="updateInstallment(this.value)"></select>
        </div>

        <button type="submit" class="pay-button">
            Ödemeyi Tamamla
        </button>
        <span id="card-type"></span>


        <?php foreach($params as $key => $value): 
            if($key !== 'installment_count'): ?>
                <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
            <?php endif; ?>
        <?php endforeach; ?>
    </form>
</div>



<style>
    .pay-button {
        position: relative;
    }

    .pay-button:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        pointer-events: none;
        color: transparent;
    }

    .pay-button:disabled::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 18px;
        height: 18px;
        margin-top: -9px;
        margin-left: -9px;
        border: 2px solid rgba(255,255,255,.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>


<script>
function updateInstallment(value) {
    document.querySelector('select[name="installment_count"]').value = value;
}
</script>