define(
        [
            'uiComponent',
            'Magento_Checkout/js/model/payment/renderer-list'
        ],
        function (
                Component,
                rendererList
                ) {
            'use strict';
            rendererList.push(
                    {
                        type: 'paynl_payment_ideal',
                        component: 'Paynl_Payment/js/view/payment/method-renderer/default'
                    }
            );
            rendererList.push(
                    {
                        type: 'paynl_payment_mistercash',
                        component: 'Paynl_Payment/js/view/payment/method-renderer/default'
                    }
            );

            return Component.extend({});
        }
);