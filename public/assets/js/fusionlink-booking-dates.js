(function (window) {
    function parseLocalDate(dateStr) {
        if (!dateStr) {
            return null;
        }

        var parts = dateStr.split('-').map(function (part) {
            return parseInt(part, 10);
        });

        if (parts.length !== 3 || parts.some(function (n) { return Number.isNaN(n); })) {
            return null;
        }

        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function formatLocalDate(date) {
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');

        return date.getFullYear() + '-' + month + '-' + day;
    }

    function isSunday(dateStr) {
        var date = parseLocalDate(dateStr);

        return !!date && date.getDay() === 0;
    }

    function nextOpenDate(dateStr) {
        var date = parseLocalDate(dateStr) || new Date();

        while (date.getDay() === 0) {
            date.setDate(date.getDate() + 1);
        }

        return formatLocalDate(date);
    }

    function bindNoSunday(input, message) {
        if (!input) {
            return;
        }

        var alertMessage = message || 'Sunday is closed. Please choose Monday to Saturday.';

        function enforce() {
            if (!input.value || !isSunday(input.value)) {
                return;
            }

            window.alert(alertMessage);
            var fallback = input.min && !isSunday(input.min) ? input.min : nextOpenDate(input.value);
            input.value = nextOpenDate(fallback);
            input.dispatchEvent(new Event('change'));
        }

        input.addEventListener('change', enforce);
        input.addEventListener('blur', enforce);

        if (input.value && isSunday(input.value)) {
            input.value = nextOpenDate(input.min || input.value);
        }
    }

    window.FusionLinkBookingDates = {
        isSunday: isSunday,
        nextOpenDate: nextOpenDate,
        bindNoSunday: bindNoSunday,
    };
})(window);
