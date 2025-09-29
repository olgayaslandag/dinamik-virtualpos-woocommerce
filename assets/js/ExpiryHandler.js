(function(window, $) {
    class ExpiryHandler {
        formatExpiry(e) {
            const input = e.target;
            const cursor = input.selectionStart;
            const before = input.value;

            let cleaned = Utilities.onlyNumbers(before).substring(0, 4);
            let formatted = cleaned.length > 2 ? cleaned.substring(0,2) + '/' + cleaned.substring(2) : cleaned;

            input.value = formatted;
            const diff = formatted.length - before.length;
            input.setSelectionRange(cursor + diff, cursor + diff);

            this.validate(formatted);
        }

        validate(date) {
            if (date.length === 5) {
                const [month, year] = date.split('/');
                const now = new Date();
                const curY = now.getFullYear() % 100;
                const curM = now.getMonth() + 1;

                if (month < 1 || month > 12) ErrorHandler.show($('#card-expiry'), 'Geçersiz ay');
                else if (year < curY || (year == curY && month < curM)) ErrorHandler.show($('#card-expiry'), 'Kart süresi dolmuş');
                else ErrorHandler.clear($('#card-expiry'));
            }
        }
    }
    window.ExpiryHandler = ExpiryHandler;
})(window, jQuery);