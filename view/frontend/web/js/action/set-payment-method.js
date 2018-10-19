define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, quote, urlBuilder, storage, customer, setPaymentInformation, fullScreenLoader) {
    'use strict';

    return function (messageContainer) {
        setPaymentInformation(messageContainer, quote.paymentMethod());
    };
});
