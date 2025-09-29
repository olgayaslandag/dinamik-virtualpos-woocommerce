(function(window, $) {
    class CVVHandler {
        formatCVV(e) {
            const input = e.target;
            input.value = Utilities.onlyNumbers(input.value).substring(0, 3);
            if (input.value.length === 3) {
                $('.card-icon').text('💳').attr('title', 'Visa/Mastercard');
            } else {
                $('.card-icon').text('').attr('title', '');
            }
        }
    }
    window.CVVHandler = CVVHandler;
})(window, jQuery);