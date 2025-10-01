(function(window, $) {
    class BinChecker 
    {
        constructor() 
        {
            this.lastBin = null;
        }

        checkBin(e) 
        {
            const digits = Utilities.onlyNumbers($(e.target).val());
            const $select = $('select#installment');
            const $info = $('#card-type');
            const $orderTotal = $('input[name=order_total]');

            if (digits.length >= 6) {
                const bin = digits.substr(0,6);
                if (bin === this.lastBin) return;
                this.lastBin = bin;

                
                $select.html($("<option>", { text: "Hesaplanıyor...", disabled: true }));

                $.post(virtualpos_params.ajax_url, {
                    action: 'dinamik_bin_lookup',
                    security: virtualpos_params.security,
                    bin,
                    order_total: $orderTotal.val()
                }, (res) => {
                    console.log(res)
                    if (!res || !res.success) {
                        $info.text('Kart bilgisi bulunamadı');
                        return;
                    }
                    $select.html('');
                    $.each(res.data.prices, (i, price) => {
                        let idx = parseInt(i,10);
                        let taksitText = idx === 1 ? 'Tek Çekim' : idx + " Taksit";
                        let text = taksitText + ": " + price.aylik.toFixed(2) + " TL";
                        if (idx > 1) text += " x " + idx;
                        $select.append($("<option>", {
                            value: idx, text, "data-price": price.toplam
                        }));

                        if(idx === 1) {
                            $('input[name=card_type]').val(price.brand);                            
                        }
                    });                    
                    
                    $select.find('option:first').prop('selected', true);

                    // Change event’ini tetikle
                    $select.trigger('change');
                    
                    let label = [];
                    if (res.data.scheme) label.push(res.data.scheme);
                    if (res.data.bank_name) label.push(res.data.bank_name);
                    if (res.data.product) label.push(`(${res.data.product})`);
                    $info.text(label.join(' — '));
                }, 'json').fail(() => {
                    $('#card-type').text('Sorgu hatası');
                });
            } else {
                this.lastBin = null;
                $('#card-type').text('');
                $select.html('');
            }
        }
    }
    window.BinChecker = BinChecker;
})(window, jQuery);