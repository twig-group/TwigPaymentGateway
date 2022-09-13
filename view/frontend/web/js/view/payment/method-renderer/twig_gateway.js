/**
 * TwigPaymentGateway
 *
 * @description Twig Payment Gateway
 * @author   Twig Team <twigpay@twig-group.com>
 * @license  MIT
 * @copyright Copyright © 2022 https://twigcard.com/
 *
 */
/*browser:true*/
/*global define*/

define(
    [
        "Magento_Customer/js/model/customer",
        "Magento_Checkout/js/model/resource-url-manager",
        "mage/storage",
        "Magento_Checkout/js/view/payment/default",
        "jquery",
        "Magento_Checkout/js/model/payment/additional-validators",
        "Magento_Checkout/js/action/set-payment-information",
        "mage/url",
        'Magento_Ui/js/modal/modal'
    ],
    function (
        customer,
        resourceUrlManager,
        storage,
        Component,
        $,
        additionalValidators,
        setPaymentInformationAction,
        mageUrl,
        modal
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Twigpay_TwigPaymentGateway/payment/form',
                transactionResult: ''
            },

            getLogoUrl: function () {
                return window.checkoutConfig.payment.twig_gateway.twig_logo.twig_logo_checkout;
            },
            initObservable: function () {

                this._super()
                    .observe([
                        'transactionResult'
                    ]);
                return this;
            },

            getCode: function() {
                return 'twig_gateway';
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'transaction_result': this.transactionResult()
                    }
                };
            },

            getTransactionResults: function() {
                return _.map(window.checkoutConfig.payment.twig_gateway.transactionResults, function(value, key) {
                    return {
                        'value': key,
                        'transaction_result': value
                    }
                });
            },
            redirectToTwigController: function (data) {
                // Make a post request to redirect controller

                var modaloption = {
                    type: 'popup',
                    modalClass: 'modal-popup',
                    responsive: true,
                    innerScroll: true,
                    clickableOverlay: true,
                    title: $.mage.__('Something went wrong!'),
                    modalContent: '[data-role="content"]',
                    appendTo: 'body',
                    buttons: [{
                       text: $.mage.__('Close'),
                       class: '',
                       click: function () {
                           this.closeModal();
                       }
                   }],
                   trigger: '[data-trigger=openmymodal]'
                };

                var modelcreate = modal(modaloption, $('#messageModal'));

                var url = mageUrl.build("twigpaymentgateway/standard/redirect");
                $.ajax({
                    url: url,
                    method: "post",
                    showLoader: true,
                    data: data,
                    success: function (response) {
                        if (response.info == 'success') {
                           window.location.href = response.checkoutUrl;
                        }
                        else {
                            // let result = response.message;
                            let result = "Please try again later.";
                            $("#messageModal").html(result).modal(modaloption).modal('openModal');
                        }
                    }
                });
            },
            _getElem: function (elem) {
                return this.modal.find(elem);
            },
            continueToTwig: function () {
                var data = $("#co-shipping-form").serialize();
                this.redirectToTwigController(data);
            },
        });
    }
);
