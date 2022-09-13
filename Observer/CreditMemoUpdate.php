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
namespace Twigpay\TwigPaymentGateway\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Twigpay\TwigPaymentGateway\Model\CreditMemo\UpdateCreditMemo;

class CreditMemoUpdate implements ObserverInterface

{
    public function __construct(
        UpdateCreditMemo $updateCreditMemo
    ) {
        $this->updateCreditMemo = $updateCreditMemo;
    }

    /**
     *
     * @param  Observer  $observer
     * @return void
     * @throws Exception
     */

    public function execute(Observer $observer)
    {
        /** @var Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $creditmemoId = $creditmemo->getId();
        $creditMemoState = 1;
        $this->updateCreditMemo->updateCreditMemo($creditmemoId, $creditMemoState);
    }

}
