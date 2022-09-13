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

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\InvoiceRepository;
use Psr\Log\LoggerInterface;


class InvoiceStatusObserver implements ObserverInterface

{
    /**
     * @param  Observer  $observer
     * @return void
     */

    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;


    protected $_logger;


    public function __construct(
        InvoiceRepository $invoiceRepository,
        LoggerInterface $logger
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->_logger = $logger;
    }

    public function execute(Observer $observer)
    {
        /** @var Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();
        $invoice_id = $invoice->getIncrementId();
        $invoiceData = $this->invoiceRepository->get($invoice_id);
        $existingIvoice = $invoiceData->getData('invoice_exists');
        $pending = Invoice::STATE_OPEN;

        if (is_null($existingIvoice)) {
            $invoiceData->setState($pending)->setStatus($pending);
            $this->invoiceRepository->save($invoiceData);
        } else {
            $this->_logger->debug('Invoice id'.$invoice_id.'status not changed to pending');
        }
    }


}
