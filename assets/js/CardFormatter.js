(function(window, $) {
    class CardFormatter {
        formatCardNumber(e) {
            const input = e.target;
            const cursor = input.selectionStart;
            const before = input.value.length;

            let cleaned = Utilities.onlyNumbers(input.value).substring(0, 16);

            let formatted = '';
            for (let i = 0; i < cleaned.length; i++) {
                if (i > 0 && i % 4 === 0) formatted += ' ';
                formatted += cleaned[i];
            }
            input.value = formatted;

            const after = formatted.length;
            input.setSelectionRange(cursor + (after - before), cursor + (after - before));

            this.detectCardType(cleaned);
        }

        detectCardType(cardNumber) {
            const $icon = $('.card-icon');
            if (/^4/.test(cardNumber)) $icon.text('💳').attr('title', 'Visa');
            else if (/^5[1-5]/.test(cardNumber)) $icon.text('💳').attr('title', 'Mastercard');
            else if (/^3[47]/.test(cardNumber)) $icon.text('💎').attr('title', 'AmEx');
            else if (/^6/.test(cardNumber)) $icon.text('🏦').attr('title', 'Discover');
            else if (cardNumber.length) $icon.text('💳').attr('title', 'Kredi Kartı');
            else $icon.text('').attr('title', '');
        }
    }
    window.CardFormatter = CardFormatter;
})(window, jQuery);