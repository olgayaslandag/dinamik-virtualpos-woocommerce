(function(window, $) {
    class InstallmentHandler 
    {
        constructor(selectId = 'installment_count') 
        {
            this.selectId = selectId;
            this.priceInput = $('input[name="payment_amount"]');

            // change event listener, document üzerinden
            $(document).on('change', `#${this.selectId}`, (e) => {
                $('.pay-button').prop('disabled', true);
                const selectedOption = $(e.currentTarget).find('option:selected');
                const price = selectedOption.data('price'); // data-price değeri
                const installment_count = selectedOption.val();
                const order_id = $('input[name="order_id"]').val();

                console.log('Seçilen taksit fiyatı:', price, 'Taksit Sayısı:', installment_count, 'Sipariş ID:', order_id);

                this.priceInput.val(price);

                this.updateBackend(price, installment_count, order_id);
                console.log(installment_count);
            });
        }

        updateBackend(price, installment_count, order_id) 
        {
            $.ajax({
                url: virtualpos_params.ajax_url,
                method: 'POST',
                data: {
                    action: 'dinamik_retokenize',
                    security: virtualpos_params.security, 
                    order_id: order_id,
                    installment_count: installment_count,
                    price: price
                },
                success: (res) => {
                    console.log('Backend güncellendi:', res.data);

                    
                    $('input[name=paytr_token]').val(res.data.params.paytr_token);
                    //$('input[name=installment_count]').val(res.data.params.installment_count);
                    $('input[name=no_installment]').val(res.data.params.no_installment);
                    $('input[name=merchant_oid]').val(res.data.params.merchant_oid);

                    $('.pay-button').prop('disabled', false);
                },
                error: (xhr, status, err) => {
                    console.error('AJAX hatası:', err);
                }
            });
        }
    }

    window.InstallmentHandler = InstallmentHandler;
})(window, jQuery);