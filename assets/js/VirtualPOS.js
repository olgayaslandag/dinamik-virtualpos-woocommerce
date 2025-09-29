(function(window, $) {
    class VirtualPOS {
        constructor() {
            this.cardFormatter = new CardFormatter();
            this.expiryHandler = new ExpiryHandler();
            this.cvvHandler = new CVVHandler();
            this.cardNameHandler = new CardNameHandler();
            this.binChecker = new BinChecker();
            this.hiddenFields = new HiddenFields();
            this.installmentHandler = new InstallmentHandler();

            this.bindEvents();
        }

        bindEvents() {
            $('#card-number').on('input', (e) => this.cardFormatter.formatCardNumber(e));
            $('#card-expiry').on('input', (e) => this.expiryHandler.formatExpiry(e));
            $('#card-cvv').on('input', (e) => this.cvvHandler.formatCVV(e));
            $('#card-name').on('input', (e) => this.cardNameHandler.formatName(e));
            $('#card-number').on('input', (e) => this.binChecker.checkBin(e));

            $('#card-expiry').on('input blur change', () => this.hiddenFields.updateExpiry());
            $('#card-number').on('input blur change', () => this.hiddenFields.updateCardNumber());

            $('input').on('focus', function() { ErrorHandler.clear($(this)); });

            $('.virtualpos_payment_form').on('submit', (e) => this.validateForm(e));
        }

        validateForm(e) {
            let valid = true;
            const num = $('#card-number').val().replace(/\s/g,'');
            if (num.length !== 16) { ErrorHandler.show($('#card-number'), 'Kart numarası 16 haneli olmalıdır'); valid = false; }

            const exp = $('#card-expiry').val();
            if (!/^\d{2}\/\d{2}$/.test(exp)) { ErrorHandler.show($('#card-expiry'), 'Geçerli bir tarih girin (AA/YY)'); valid = false; }

            const cvv = $('#card-cvv').val();
            if (cvv.length < 3) { ErrorHandler.show($('#card-cvv'), 'CVV en az 3 haneli olmalıdır'); valid = false; }

            if (!valid) {
                e.preventDefault();
                $('.error').first().focus();
            }
        }
    }
    window.VirtualPOS = VirtualPOS;
})(window, jQuery);