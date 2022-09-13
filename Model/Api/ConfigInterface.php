<?php
/**
 * TwigPaymentGateway
 *
 * @description Twig Payment Gateway
 * @author   Twig Team < twigpay@twig-group.com >
 * @license  MIT
 * @copyright Copyright © 2022 https://twigcard.com/
 *
 */
namespace Twigpay\TwigPaymentGateway\Model\Api;


/**
 * Interface ConfigInterface
 * @package Twigpay\TwigPaymentGateway\Model\Api
 */
interface ConfigInterface
{
    /**
     * Get success url
     * @param $orderId
     * @param $reference
     * @param $quoteId
     * @return mixed
     */
    public function getSuccessUrl($orderId, $reference, $quoteId);

    /**
     * Get cancel url
     * @param $orderId
     * @param $reference
     * @return mixed
     */
    public function getCancelUrl($orderId, $reference);

    /**
     * Get push url
     * @return mixed
     */
    public function getPushUrl();

}
