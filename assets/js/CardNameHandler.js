(function(window, $) {
    class CardNameHandler {
        formatName(e) {
            const input = e.target;
            let cleaned = Utilities.onlyLetters(input.value).toUpperCase();
            input.value = cleaned;
        }
    }
    window.CardNameHandler = CardNameHandler;
})(window, jQuery);