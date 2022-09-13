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
                type: 'twig_gateway',
                component: 'Twigpay_TwigPaymentGateway/js/view/payment/method-renderer/twig_gateway'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
