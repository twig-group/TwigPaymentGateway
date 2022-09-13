<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Twigpay\TwigPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;

class CaptureRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

        /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @param ConfigInterface $config
     * @param CartRepositoryInterface      $quoteRepository
     */
    public function __construct(
        ConfigInterface $config,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->config = $config;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {

        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();

        ContextHelper::assertOrderPayment($payment);

        $quote = $this->quoteRepository->get((int) $payment->getOrder()->getQuoteId());
        $orderId =$quote->getId();


        if (!$payment instanceof OrderPaymentInterface) {
            throw new \LogicException('Order payment should be provided.');
        }

        $twigID = $payment->getAdditionalInformation('twig_order_id');
        $id = uniqid() . "" . $quote->getReservedOrderId();
        $currencyCode = $order->getCurrencyCode();
        $captureAmount = $buildSubject['amount'];
        $dateCreated = date('Y-m-d\TH:i:s.u');
        $finalAmount = $quote->getGrandTotal();

        $x_api_key = $this->config->getValue('x-api-key', $order->getStoreId());
        $twig_client_id =$this->config->getValue('twig_client_id', $order->getStoreId());
        $twig_api_version =$this->config->getValue('twig-api-version', $order->getStoreId());
        $twig_uri = 'https://api.twigpayment.com/v1/payment/order/capture';


        if ($captureAmount > $finalAmount) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Capture Amount cannot be greater than total amount!"));

        }

        $requestData = [
            'id' => $id,
            'orderId' => $twigID,
            'amount' => $this->formatValue($captureAmount),
            'finalAmount' => $this->formatValue($finalAmount),
            'currencyCode' => $order->getCurrencyCode(),
            'dateCreated' =>  $dateCreated,
            'message' => 'Capture the order'
        ];

        return [
            'body' => $requestData,
            'method' => \Zend_Http_Client::POST,
            'uri' => $twig_uri,
            'headers' => ([
                'Content-Type' => 'application/json',
                'x-api-key' => $x_api_key,
                'twig-client-id' => $twig_client_id,
                'twig-api-version' => $twig_api_version
            ])
        ];

    }

    public function formatValue ($value){
        return  number_format((float)$value, 2, '.', '') ;
    }

}
