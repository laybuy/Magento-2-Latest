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

        getRedirectUrl: function() {
            var laybuyUrl, redirectUrl, self = this;

            /**
             * Checkout for guest and registered customer.
             */
            if (!customer.isLoggedIn()) {
                laybuyUrl = window.checkoutConfig.payment['laybuy_payment'].laybuyProcessUrl + 'guest-email/' + quote.guestEmail;
            } else {
                laybuyUrl = window.checkoutConfig.payment['laybuy_payment'].laybuyProcessUrl;
            }

            setBillingAddressAction(globalMessageList);

            $.ajax({
                url: laybuyUrl,
                method: 'post',
                cache: false,
                async: false
            }).success(function(data) {
                if (data.success) {
                    redirectUrl = data.redirect_url;

                    if (window.checkoutConfig.payment['laybuy_payment'].paymentAction == 'authorize_capture') {
                        $.mage.redirect(redirectUrl);
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
