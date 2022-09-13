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
namespace Twigpay\TwigPaymentGateway\Model\Rewrite;

use Magento\Sales\Model\Order;

class State extends \Magento\Sales\Model\ResourceModel\Order\Handler\State
{
    /**
     * Check order status and adjust the status before save
     *
     * @param  Order  $order
     * @return  \Twigpay\TwigPaymentGateway\Model\Rewrite\State
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function check(Order $order)
    {
        $currentState = $order->getState();
        if ($currentState == Order::STATE_NEW && $order->getIsInProcess()) {
            $order->setState(Order::STATE_PROCESSING)
                ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
            $currentState = Order::STATE_PROCESSING;
        }

        if (!$order->isCanceled() && !$order->canUnhold() && !$order->canInvoice()) {
            if (in_array($currentState, [Order::STATE_PROCESSING, Order::STATE_COMPLETE])
                && !$order->canCreditmemo()
                && !$order->canShip()
                && $order->getIsNotVirtual()
            ) {
                $order->setState(Order::STATE_PROCESSING)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
            } elseif ($currentState === Order::STATE_PROCESSING && !$order->canShip()) {
                $order->setState(Order::STATE_COMPLETE)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_COMPLETE));
            }
        }
        return $this;
    }
}
