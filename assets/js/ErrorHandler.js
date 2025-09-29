(function(window, $) {
    class ErrorHandler {
        static show($el, message) {
            $el.addClass('error').attr('title', message);
            if (!$el.next('.error-tooltip').length) {
                $el.after('<div class="error-tooltip">' + message + '</div>');
            }
        }

        static clear($el) {
            $el.removeClass('error').removeAttr('title');
            $el.next('.error-tooltip').remove();
        }
    }
    window.ErrorHandler = ErrorHandler;
})(window, jQuery);