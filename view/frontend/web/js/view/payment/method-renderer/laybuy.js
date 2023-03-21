/* @api */
define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Laybuy_Laybuy/js/action/set-payment-method',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/set-billing-address',
    'Magento_Ui/js/model/messageList',
    'Magento_Checkout/js/action/redirect-on-success',
    'mage/url'
], function ($, Component, quote, setPaymentMethodAction, customerData, customer, additionalValidators, setBillingAddressAction, globalMessageList, redirectOnSuccessAction, url) {
    'use strict';

    return Component.extend({
        redirectAfterPlaceOrder: false,
        defaults: {
            template: 'Laybuy_Laybuy/payment/laybuy'
        },

        /** Returns is method available */
        isAvailable: function () {
            return quote.totals()['grand_total'] <= 0;
        },

        placeOrder: function (data, event) {
            var self = this;

            if (event) {
                event.preventDefault();
            }

            if (this.validate() && additionalValidators.validate()) {
                this.isPlaceOrderActionAllowed(false);

                this.getRedirectUrl();

                return true;
            }

            return false;
        },

        getLaybuyLogoSrc: function() {
            return window.checkoutConfig.payment['laybuy_payment'].logoSrc;
        },

        getLaybuyImagePaySrc: function() {
            return window.checkoutConfig.payment['laybuy_payment'].checkoutPaySrc;
        },

        getLaybuyImageScheduleSrc: function() {
            return window.checkoutConfig.payment['laybuy_payment'].checkoutScheduleSrc;
        },

        getLaybuyImageCompleteSrc: function() {
            return window.checkoutConfig.payment['laybuy_payment'].checkoutCompleteSrc;
        },

        getLaybuyImageDoneSrc: function() {
            return window.checkoutConfig.payment['laybuy_payment'].checkoutDoneSrc;
        },

        getRedirectUrl: function() {
            var redirectUrl, self = this;

            $.ajax({
                url: window.checkoutConfig.payment['laybuy_payment'].laybuyProcessUrl,
                method: 'post',
                cache: false,
                async: false,
                data: {
                    "guest-email": quote.guestEmail
                }
            }).done(function(data) {
                if (data.success) {
                    redirectUrl = data.redirect_url;

                    if (window.checkoutConfig.payment['laybuy_payment'].paymentAction == 'authorize_capture') {
                        setBillingAddressAction(globalMessageList).done(function() {
                            $.mage.redirect(redirectUrl);
                        })
                    } else {
                        self.getPlaceOrderDeferredObject()
                            .fail(
                                function () {
                                    self.isPlaceOrderActionAllowed(true);
                                }
                            ).done(
                            function () {
                                $.mage.redirect(redirectUrl);
                            }
                        );
                    }
                } else {
                    customerData.set('messages', {
                        messages: [{
                            type: 'error',
                            text: 'Couldn\'t initialize Laybuy payment method.'
                        }]
                    });
                    $.mage.redirect('/checkout/cart');
                }
            });

            return;
        }
    });
});
