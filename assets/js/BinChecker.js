(function (window, $) {

    // 🔒 Tek ve zorunlu para formatter
    function formatPrice(value) {
        return Number.parseFloat(value || 0)
            .toFixed(2)
            .replace(',', '.');
    }

    class BinChecker {

        constructor() {
            this.lastBin = null;
        }

        checkBin(e) {

            const digits      = Utilities.onlyNumbers($(e.target).val());
            const $select     = $('select#installment_count');
            const $info       = $('#card-type');
            const $orderTotal = $('input[name=order_total]');
            const $payButton  = $('button.pay-button');

            // BIN yoksa temizle
            if (digits.length < 6) {
                this.lastBin = null;
                $info.text('');
                $select.empty();
                return;
            }

            const bin = digits.substring(0, 6);
            if (bin === this.lastBin) return;
            this.lastBin = bin;

            // UI lock
            $select.html(
                $('<option>', {
                    text: 'Hesaplanıyor...',
                    disabled: true,
                    selected: true
                })
            );

            $payButton.prop('disabled', true);

            $.post(
                virtualpos_params.ajax_url,
                {
                    action: 'dinamik_bin_lookup',
                    security: virtualpos_params.security,
                    bin: bin,
                    order_total: $orderTotal.val()
                },
                (res) => {

                    if (!res || !res.success || !res.data?.prices) {
                        $info.text('Kart bilgisi bulunamadı');
                        return;
                    }

                    $select.empty();

                    $.each(res.data.prices, (i, price) => {

                        const installment = parseInt(i, 10);

                        // 🔐 maksimum 6 taksit
                        if (installment > 6) return;

                        const toplam = formatPrice(price.toplam);
                        const aylik  = formatPrice(price.aylik);

                        const label =
                            installment === 0
                                ? 'Tek Çekim (Avantajlı Fiyat)'
                                : installment + ' Taksit';

                        let text = `${label}: ${aylik} TL`;
                        if (installment > 1) {
                            text += ` x ${installment} = ${toplam} TL`;
                        }

                        $select.append(
                            $('<option>', {
                                value: installment,
                                text: text,
                                'data-price': toplam // 🔥 HER ZAMAN STRING
                            })
                        );

                        // kart tipi sadece tek çekimde set edilir
                        if (installment === 0 && price.brand) {
                            $('input[name=card_type]').val(price.brand);
                        }
                    });

                    // İlk option’u seç
                    $select.find('option:first').prop('selected', true);

                    // change tetikle
                    $select.trigger('change');

                    // Kart bilgisi label
                    const labelInfo = [];
                    if (res.data.scheme)    labelInfo.push(res.data.scheme);
                    if (res.data.bank_name) labelInfo.push(res.data.bank_name);
                    if (res.data.product)   labelInfo.push(`(${res.data.product})`);

                    $info.text(labelInfo.join(' — '));

                    $payButton.prop('disabled', false);
                },
                'json'
            ).fail(() => {
                $info.text('Sorgu hatası');
                $payButton.prop('disabled', false);
            });
        }
    }

    window.BinChecker = BinChecker;

})(window, jQuery);