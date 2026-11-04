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
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.bp-admin-tabs .bp-admin-tab'));
    function activateTab(hash) {
        var target = document.getElementById((hash || '').replace(/^#/, ''));
        var chosen = tabs.find(function (tab) { return target && tab.hash === '#' + target.id; }) || tabs[0];
        tabs.forEach(function (tab) {
            var selected = tab === chosen;
            tab.classList.toggle('bp-admin-tab-active', selected);
            if (selected) {
                tab.setAttribute('aria-current', 'location');
            } else {
                tab.removeAttribute('aria-current');
            }
        });
    }
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () { activateTab(tab.hash); });
    });
    window.addEventListener('hashchange', function () { activateTab(window.location.hash); });
    activateTab(window.location.hash);
})();
