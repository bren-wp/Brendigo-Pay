/* Brendigo Pay WP admin interactions; no remote resources. */
'use strict';
(function () {
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-copy-field],[data-copy-text]');
        if (!button) return;
        var fieldId = button.getAttribute('data-copy-field');
        var field = fieldId ? document.getElementById(fieldId) : null;
        var value = field ? field.value : button.getAttribute('data-copy-text');
        if (!value) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(function () {
                button.textContent = 'Kopirano ✓';
            }).catch(function () {
                if (field) field.select();
                button.textContent = 'Odaberite tekst';
            });
        } else {
            if (field) field.select();
            button.textContent = field ? 'Odaberite tekst' : 'Kopiranje nije dostupno';
        }
    });
})();
