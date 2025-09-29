(function(window) {
    class Utilities {
        static onlyNumbers(value) {
            return value.replace(/\D/g, '');
        }

        static onlyLetters(value) {
            return value.replace(/[^a-zA-ZğüşıöçĞÜŞİÖÇ\s]/g, '');
        }
    }
    window.Utilities = Utilities;
})(window);