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
namespace Twigpay\TwigPaymentGateway\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;


class CaptureHandler implements HandlerInterface
{

    const TXN_ID = 'TXN_ID';

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if ($response['code'] === 400 || $response['code'] === 403 || $response['code'] === 500) {
            throw new \InvalidArgumentException('Twig Payment Gateway : ' . $response['body']);
        }
        else {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();
        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setTransactionId($this->generateTxnId());
        $payment->setIsTransactionClosed(false);
        }
    }

        /**
     * @return string
     */
    protected function generateTxnId()
    {
        return hash('sha256', random_int(0, 1000));
    }

}
