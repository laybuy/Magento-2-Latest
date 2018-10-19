/**
 * Copyright © 2018 Overdose.digital. All rights reserved.
 * See COPYING.txt for license d
 */
/*browser:true*/
/*global define*/
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

        var config = window.checkoutConfig.payment,
            type = 'laybuy_payment';

        if(config[type].isActive) {
            rendererList.push(
                {
                    type: type,
                    component: 'Laybuy_Laybuy/js/view/payment/method-renderer/laybuy'
                }
            );
        }
        /** Add view logic here if needed */
        return Component.extend({});
    }
);