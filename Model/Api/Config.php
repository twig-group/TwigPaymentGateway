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

use Magento\Framework\UrlInterface;

/**
 * Class Config
 * @package Twigpay\TwigPaymentGateway\Model\Api
 */
class Config implements ConfigInterface
{
    private UrlInterface $urlBuilder;

    /**
     * Config constructor.
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        UrlInterface $urlBuilder

    )
    {
        $this->urlBuilder = $urlBuilder;

    }


    /**
     * Get complete url
     * @param $orderId
     * @param $reference
     * @param $quoteId
     * @return mixed
     */
    public function getSuccessUrl($orderId, $reference, $quoteId)
    {
        return $this->urlBuilder->getUrl("twigpaymentgateway/standard/complete/id/$orderId/magento_twig_id/$reference/quote_id/$quoteId", ['_secure' => true]);
    }

    /**
     * Get cancel url
     * @param $orderId
     * @param $reference
     * @return mixed
     */
    public function getCancelUrl($orderId, $reference)
    {
        return $this->urlBuilder->getUrl("twigpaymentgateway/standard/cancel/id/$orderId/magento_twig_id/$reference/submitted/0", ['_secure' => true]);
    }

    /**
     * Get push url
     * @return mixed
     */
    public function getPushUrl()
    {
        return $this->urlBuilder->getUrl("twigpaymentgateway/webhook/index/", ['_secure' => true]);
    }

}
