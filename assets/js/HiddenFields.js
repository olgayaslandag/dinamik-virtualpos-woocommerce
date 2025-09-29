(function(window, $) {
    class HiddenFields {
        updateExpiry() {
            const val = $('#card-expiry').val().trim();
            const parts = val.split('/');
            if (parts.length === 2) {
                const [m, y] = parts;
                if (/^(0[1-9]|1[0-2])$/.test(m) && /^[0-9]{2}$/.test(y)) {
                    $('#expiry-month').val(m);
                    $('#expiry-year').val(y);
                    return;
                }
            }
            $('#expiry-month, #expiry-year').val('');
        }

        updateCardNumber() {
            const val = $('#card-number').val().trim();
            const clean = val.replace(/\D/g, '');
            $('input[name="card_number"]').val(clean);
        }
    }
    window.HiddenFields = HiddenFields;
})(window, jQuery);