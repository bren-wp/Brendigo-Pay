/* Brendigo Pay Gutenberg blocks: tenant-scoped real link picker, no client-side amounts. */
(function (wp) {
    'use strict';
    if (!wp || !wp.blocks || !wp.element || !wp.components || !wp.blockEditor) return;
    var el = wp.element.createElement;
    var useEffect = wp.element.useEffect;
    var useState = wp.element.useState;
    var I = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var ToggleControl = wp.components.ToggleControl;
    var SelectControl = wp.components.SelectControl;
    var Notice = wp.components.Notice;
    var apiFetch = wp.apiFetch;
    function money(minor) {
        return new Intl.NumberFormat('hr-HR', { style: 'currency', currency: 'EUR' }).format((minor || 0) / 100);
    }
    function PaymentEditor(props) {
        var a = props.attributes;
        var useLinks = useState([]), links = useLinks[0], setLinks = useLinks[1];
        var useLoad = useState('loading'), load = useLoad[0], setLoad = useLoad[1];
        var set = function (key) { return function (val) { var p = {}; p[key] = val; props.setAttributes(p); }; };
        var qrOnly = props.name === 'brendigo-pay/qr-code';
        var isCard = props.name === 'brendigo-pay/payment-card' || qrOnly;
        useEffect(function () {
            var active = true;
            if (!apiFetch) { setLoad('error'); return; }
            apiFetch({ path: '/brendigo-pay/v1/links' }).then(function (value) {
                if (active) { setLinks(Array.isArray(value.items) ? value.items : []); setLoad('ready'); }
            }).catch(function () { if (active) setLoad('error'); });
            return function () { active = false; };
        }, []);
        var link = links.find(function (x) { return x.id === a.id; });
        var options = [{ value: '', label: 'Odaberite naplatnu poveznicu…' }];
        links.forEach(function (x) {
            options.push({ value: x.id, label: x.title + ' — ' + money(x.amount_minor) });
        });
        if (a.id && !link) options.push({ value: a.id, label: 'Postojeći ID: ' + a.id });
        var isValid = /^[a-f0-9]{24}$/.test(a.id || '');
        return el(wp.element.Fragment, {},
            el(I, {},
                el(PanelBody, { title: 'Osnovne postavke', initialOpen: true },
                    load === 'ready' ? el(SelectControl, { label: 'Platni link', options: options, value: a.id, onChange: set('id'), help: 'Iznos i naziv čitaju se iz vašeg Brendigo Pay računa.' }) : null,
                    load === 'loading' ? el('p', {}, 'Učitavanje platnih poveznica…') : null,
                    load === 'error' ? el(Notice, { status: 'warning', isDismissible: false }, 'Nije moguće učitati poveznice. Povežite dodatak s platformom.') : null,
                    el(TextControl, { label: 'ID platnog linka', value: a.id, onChange: set('id'), help: 'Možete ručno unijeti ID od 24 heksadecimalna znaka.' }),
                    el(TextControl, { label: 'Tekst gumba', value: a.label, onChange: set('label') }),
                    link ? el('div', { className: 'bp-editor-verified-amount', role: 'status' }, 'Iznos s platforme: ' + money(link.amount_minor) + ' · EUR (zaključano)') : null
                ),
                el(PanelBody, { title: 'Izgled gumba', initialOpen: true },
                    el(SelectControl, { label: 'Stil gumba', value: a.style, onChange: set('style'), options: [
                        { label: 'Primarni', value: 'primary' }, { label: 'Outline', value: 'outline' }, { label: 'Ghost', value: 'ghost' }
                    ] })
                ),
                el(PanelBody, { title: 'QR prikaz', initialOpen: true },
                    el(ToggleControl, { label: 'Prikaži QR kod', checked: qrOnly || !!a.show_qr, disabled: qrOnly, onChange: set('show_qr') })
                ),
                el(PanelBody, { title: 'Sigurnost', initialOpen: false },
                    el('p', {}, 'Iznos i status određuje poslužitelj. Dodatak ne prikuplja podatke kartica.')
                )
            ),
            el('div', { className: 'bp-editor-shell' },
                el('div', { className: 'bp-editor-heading' }, 'BRENDIGO PAY · WORDPRESS NAPLATA · SANDBOX'),
                el('div', { className: 'bp-editor-preview' },
                    el('div', { className: 'bp-editor-preview-art', 'aria-hidden': true },
                        el('div', { className: 'bp-editor-preview-device' }, '▥', el('span', {}, 'Brendigo Pay'))
                    ),
                    el('div', { className: 'bp-editor-preview-body' },
                        el('span', { className: 'bp-editor-provider-label' }, '◈ Brendigo Pay · SANDBOX'),
                        el('h3', {}, link ? link.title : (a.title || 'Plaćanje za uslugu')),
                        link ? el('strong', { className: 'bp-editor-amount' }, money(link.amount_minor)) : null,
                        el('p', {}, link ? link.description : 'Odaberite platni link kako biste vidjeli stvarni iznos.'),
                        isValid ? el('div', { className: 'bp-editor-button' }, a.label || 'Plati putem Brendigo Paya', ' →') :
                            el('div', { className: 'bp-editor-missing' }, 'Najprije odaberite platni link.'),
                        (qrOnly || a.show_qr) ? el('div', { className: 'bp-editor-qr-placeholder' }, '▦', el('small', {}, 'QR kod prikazuje se na objavljenoj stranici.')) : null,
                        el('small', {}, isValid ? 'Poveznica: ' + a.id : 'Samo testna plaćanja')
                    )
                )
            )
        );
    }
    [
        ['payment-button', 'Brendigo Pay – Naplata', 'money-alt'],
        ['payment-link', 'Brendigo Pay – Platni link', 'admin-links'],
        ['qr-code', 'Brendigo Pay – QR kod', 'grid-view'],
        ['payment-card', 'Brendigo Pay – Kartica naplate', 'index-card']
    ].forEach(function (info) {
        wp.blocks.registerBlockType('brendigo-pay/' + info[0], {
            title: info[1], icon: info[2], category: 'widgets',
            attributes: {
                id: { type: 'string', default: '' },
                label: { type: 'string', default: 'Plati putem Brendigo Paya' },
                title: { type: 'string', default: 'Plaćanje za uslugu' },
                style: { type: 'string', default: 'primary' },
                show_qr: { type: 'boolean', default: false }
            },
            edit: PaymentEditor, save: function () { return null; }
        });
    });
})(window.wp);
