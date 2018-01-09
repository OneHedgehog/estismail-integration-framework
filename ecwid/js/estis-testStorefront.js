
if (typeof(Ecwid) == 'object' && typeof(Ecwid.OnPageLoad) == 'object'){


    let estismailConfig = Ecwid.getAppPublicConfig('estis-test');

    estismailConfig = JSON.parse(estismailConfig);

    if(Number(estismailConfig.storefront) === 1){
        ec.order = ec.order || {};
        ec.order.extraFields = ec.order.extraFields || {};

        ec.order.extraFields.subscribe = {
            'title': 'Subscribe to newsletter?',
            'type': 'select',
            'selectOptions' : ['Yes','No'],
            'checkoutDisplaySection': 'billing_address',
            'required' : 'true'
        };
    }

}
