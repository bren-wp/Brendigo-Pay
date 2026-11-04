/* Brendigo Pay v0.5.0: progressive enhancement; no inline JS, no remote calls. */
'use strict';
(function () {
    const get = (s, root = document) => root.querySelector(s);
    const all = (s, root = document) => Array.from(root.querySelectorAll(s));
    const newLink = get('#bp-new-link');
    const preview = () => {
        if (!newLink) return;
        const title = get('[data-preview-title]', newLink)?.value.trim() || 'Vaš novi platni link';
        const desc = get('[data-preview-description]', newLink)?.value.trim() || 'Unesite podatke u obrazac kako biste vidjeli pregled.';
        const amount = get('[data-preview-amount]', newLink)?.value.trim() || '0';
        const numeric = Number(amount.replace(/\s/g, '').replace(/\./g, '').replace(',', '.'));
        const formatted = Number.isFinite(numeric) && numeric >= 0 && numeric <= 9999999
            ? new Intl.NumberFormat('hr-HR', { style: 'currency', currency: 'EUR' }).format(numeric)
            : '— €';
        get('[data-live-title]', newLink).textContent = title;
        get('[data-live-description]', newLink).textContent = desc;
        get('[data-live-amount]', newLink).textContent = formatted;
    };
    if (newLink) all('input,textarea', newLink).forEach((el) => el.addEventListener('input', preview));
    const filterHost = get('#bp-transaction-list');
    const applyFilters = () => {
        if (!filterHost) return;
        const query = (get('[data-txn-filter="query"]', filterHost)?.value || '').trim().toLocaleLowerCase('hr-HR');
        const provider = get('[data-txn-filter="provider"]', filterHost)?.value || '';
        const status = get('[data-txn-filter="status"]', filterHost)?.value || '';
        let count = 0;
        all('[data-txn-row]', filterHost).forEach((row) => {
            const visible = (!query || (row.getAttribute('data-txn-search') || '').includes(query))
                && (!provider || row.getAttribute('data-txn-provider') === provider)
                && (!status || row.getAttribute('data-txn-status') === status);
            row.hidden = !visible;
            if (visible) count++;
        });
        const counter = get('[data-txn-visible]', filterHost);
        if (counter) counter.textContent = String(count);
    };
    if (filterHost) {
        all('[data-txn-filter]', filterHost).forEach((field) => field.addEventListener('input', applyFilters));
        get('[data-txn-clear]', filterHost)?.addEventListener('click', () => {
            all('[data-txn-filter]', filterHost).forEach((field) => { field.value = ''; });
            applyFilters();
        });
        applyFilters();
    }
    const detailDialog = get('#bp-transaction-dialog');
    document.addEventListener('click', (event) => {
        const open = event.target.closest('[data-open-dialog]');
        if (open) {
            const dialog = document.getElementById(open.getAttribute('data-open-dialog'));
            if (dialog?.showModal) { dialog.showModal(); if (dialog === newLink) preview(); }
        }
        const close = event.target.closest('[data-close-dialog]');
        if (close) close.closest('dialog')?.close();
        const detail = event.target.closest('[data-transaction-detail]');
        if (detail && detailDialog?.showModal) {
            for (const key of ['id','date','description','provider','amount','status']) {
                const field = get('[data-txn-output="' + key + '"]', detailDialog);
                if (field) field.textContent = detail.getAttribute('data-txn-' + key) || '—';
            }
            detailDialog.showModal();
        }
        const menu = event.target.closest('[data-toggle-menu]');
        if (menu) {
            const nav = get('#bp-mobile-navigation');
            if (nav) {
                const expanded = menu.getAttribute('aria-expanded') === 'true';
                menu.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                nav.classList.toggle('bp-menu-open', !expanded);
            }
        }
    });
    all('dialog').forEach((dialog) => {
        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) dialog.close();
        });
    });
})();
