(function (wc, wp) {
    'use strict';
    if (!wc || !wc.wcBlocksRegistry || !wc.wcSettings || !wp || !wp.element) return;
    var settings = wc.wcSettings.getSetting('brendigo_pay_data', {});
    var el = wp.element.createElement;
    var label = settings.title || 'Brendigo Pay — SANDBOX';
    var options = settings.providers || [
        { id: 'stripe', label: 'Stripe TEST' },
        { id: 'paypal', label: 'PayPal Sandbox' },
        { id: 'revolut', label: 'Revolut Sandbox' }
    ];
    function PaymentContent(props) {
        var state = wp.element.useState(settings.defaultProvider || 'stripe');
        var provider = state[0], setProvider = state[1];
        var onPaymentSetup = props.eventRegistration && props.eventRegistration.onPaymentSetup;
        wp.element.useEffect(function () {
            if (!onPaymentSetup) return;
            return onPaymentSetup(function () {
                return {
                    type: props.emitResponse.responseTypes.SUCCESS,
                    meta: { paymentMethodData: { bp_provider: provider } }
                };
            });
        }, [onPaymentSetup, provider, props.emitResponse]);
        return el('div', { className: 'brendigo-pay-block' },
            el('div', { className: 'bp-wc-title' },
                el('img', { src: settings.icon || '', alt: '', width: 34, height: 34 }),
                el('strong', {}, 'Brendigo Pay'), el('span', { className: 'bp-wc-sandbox-message' }, 'SANDBOX')),
            el('p', {}, settings.description || 'Sigurno sandbox plaćanje kroz odabranog pružatelja.'),
            el('div', { className: 'bp-block-provider-list' },
                options.map(function (item) {
                    return el('label', { key: item.id, className: 'bp-wc-provider' },
                        el('input', { type: 'radio', name: 'bp_blocks_provider', value: item.id,
                            checked: provider === item.id,
                            onChange: function () { setProvider(item.id); } }),
                        el('span', { className: 'bp-wc-provider-content' },
                            el('b', {}, item.label), el('small', {}, 'Dovršite testno plaćanje na stranici pružatelja'))
                    );
                })
            ),
            el('p', { className: 'bp-wc-sandbox-message' }, 'SANDBOX • Nema stvarnog terećenja')
        );
    }
    wc.wcBlocksRegistry.registerPaymentMethod({
        name: 'brendigo_pay',
        label: el('span', {}, label),
        content: el(PaymentContent),
        edit: el('span', {}, 'Brendigo Pay — Stripe, PayPal i Revolut testni načini'),
        canMakePayment: function () { return true; },
        ariaLabel: label,
        paymentMethodId: 'brendigo_pay',
        supports: { features: settings.supports || ['products'] }
    });
})(window.wc, window.wp);
