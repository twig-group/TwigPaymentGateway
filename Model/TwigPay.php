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
namespace Twigpay\TwigPaymentGateway\Model;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Twigpay\TwigPaymentGateway\Model\Api\PayloadBuilder;
use Twigpay\TwigPaymentGateway\Model\Api\Processor;
use Magento\Payment\Model\Method\AbstractMethod;
use Zend_Http_Client;

class TwigPay extends AbstractMethod
{
    const ADDITIONAL_INFORMATION_KEY_ORDERID = 'twig_order_id';

    private PayloadBuilder $payloadBuilder;
    private Processor $processor;
    /**
     * @var bool
     */

    protected $_logger;


    public function __construct(
        PayloadBuilder $payloadBuilder,
        Processor $processor,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->payloadBuilder = $payloadBuilder;
        $this->processor = $processor;
        $this->_logger = $logger;
    }

    /**
     * Get Twig redirect url
     * @param $quote
     * @return string|null
     * @throws LocalizedException
     */

    public function getTwigRedirectUrl($quote): ?string
    {
        $reference = uniqid()."-".$quote->getReservedOrderId();
        $url = 'https://api.test.twigpayment.com/v1/operations/order/init';
        $requestBody = $this->payloadBuilder->buildTwigCheckoutPayload($quote, $reference);
        $requestBody = json_encode($requestBody, JSON_UNESCAPED_SLASHES);
        try {
            $response = $this->processor->call(
                $url,
                $requestBody,
                Zend_Http_Client::POST
            );
        } catch (Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
        $result = json_decode($response);

        if ($result->info != 200) {
            $this->_logger->debug($response);
            $message = $result->body->message;
            $arr = array();
            $arr['message'] = $message;
        } else {
            if ($result->info == 200) {
                $checkoutUrl = $result->body->checkoutUrl;
                $arr = array();
                $arr['id'] = $checkoutUrl;
                $twigId = $result->body->order->orderId;
                $payment = $quote->getPayment();
                $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDERID, $twigId);
                $payment->save();
            }
        }

        $response = json_encode($arr);
        return $response;
    }


}





