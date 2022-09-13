<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Twigpay\TwigPaymentGateway\Gateway\Request;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Zend_Http_Client;


class RefundRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private ConfigInterface $config;

    /**
     * @var CartRepositoryInterface
     */
    protected CartRepositoryInterface $quoteRepository;

    /**
      * @param ConfigInterface $config
     * @param CartRepositoryInterface $quoteRepository
     * @codeCoverageIgnore
     */
    public function __construct(

        CartRepositoryInterface $quoteRepository,
         ConfigInterface $config
    )
    {

        $this->quoteRepository = $quoteRepository;
        $this->config = $config;
    }

    /**
     * Build Refund Request
     *
     * @param array $buildSubject
     * @return array
     * @throws NoSuchEntityException
     */

    public function build(array $buildSubject): array
    {

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();
        $twig_order_id = $payment->getAdditionalInformation('twig_order_id');
        $order = $paymentDO->getOrder();
        $currencyCode = $order->getCurrencyCode();
        $refundAmount = $buildSubject['amount'];
        $dateCreated = date('Y-m-d\TH:i:s.u');

        ContextHelper::assertOrderPayment($payment);
        $quote = $this->quoteRepository->get((int)$payment->getOrder()->getQuoteId());

        $x_api_key = $this->config->getValue('x-api-key', $order->getStoreId()) ;
        $twig_client_id =$this->config->getValue('twig_client_id', $order->getStoreId()) ;
        $twig_api_version =$this->config->getValue('twig-api-version', $order->getStoreId()) ;
        $twig_uri = 'https://api.test.twigpayment.com/v1/payment/order/refund' ;


        $id  = uniqid() . "" . $quote->getReservedOrderId();

        $requestData = [
            'id' => $id,
            'orderId' => $twig_order_id,
            'amount' => $this->formatValue($refundAmount),
            'currencyCode' => $currencyCode,
            'dateCreated' => $dateCreated,
            'message' => 'Refund the Order'
        ];

        return [
            'body' => $requestData,
            'method' => Zend_Http_Client::POST,
            'uri' => $twig_uri,
            'headers' => ([
                'Content-Type' => 'application/json',
                'x-api-key' => $x_api_key,
                'twig-client-id' =>  $twig_client_id,
                'twig-api-version' =>  $twig_api_version
            ])
        ];
    }
    public function formatValue ($value){
        return  number_format((float)$value, 2, '.', '') ;
    }

}

