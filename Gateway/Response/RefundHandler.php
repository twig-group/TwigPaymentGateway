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

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

class RefundHandler implements HandlerInterface
{
    const TXN_ID = 'TXN_ID';

    /**
     * Handles transaction id
     *
     * @param  array  $handlingSubject
     * @param  array  $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if ($response['code'] === 400 || $response['code'] === 403 || $response['code'] === 500) {
            $error = json_decode($response['body']);
            throw new InvalidArgumentException('Twig Payment Gateway : '.$error->message);
        } else {
            /** @var PaymentDataObjectInterface $paymentDO */
            $paymentDO = $handlingSubject['payment'];
            $payment = $paymentDO->getPayment();

            /** @var $payment Payment */
            $payment->setTransactionId($this->generateTxnId());
            $payment->setIsTransactionClosed(false);
            $payment->setIsTransactionPending(true);
        }
    }

    /**
     * @return string
     */
    protected function generateTxnId(): string
    {
        return md5(mt_rand(0, 1000));
    }

}
