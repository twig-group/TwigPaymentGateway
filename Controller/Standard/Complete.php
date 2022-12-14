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
namespace Twigpay\TwigPaymentGateway\Controller\Standard;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\OrderFactory;
use Twigpay\TwigPaymentGateway\Model\TwigPay;


class Complete extends Action
{
    /**
     * @var Session
     */
    protected $_checkoutSession;
    /**
     * @var TwigPay
     */
    protected $_twigpayModel;
    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;
    /**
     * @var QuoteManagement
     */
    protected $_quoteManagement;
    /**
     * @var OrderFactory
     */
    protected $_orderFactory;
    /**
     * @var OrderSender
     */
    protected $_orderSender;
    /**
     * @var Data
     */
    protected $_jsonHelper;

    protected $invoiceRepository;


    /**
     * Twigpay constructor.
     * @param  Context  $context
     * @param  Session  $checkoutSession
     * @param  TwigPay  $twigpayModel
     * @param  JsonFactory  $resultJsonFactory
     * @param  QuoteManagement  $quoteManagement
     * @param  OrderFactory  $orderFactory
     * @param  OrderSender  $orderSender
     * @param  Data  $jsonHelper
     */

    public function __construct(
        Context $context,
        Session $checkoutSession,
        TwigPay $twigpayModel,
        JsonFactory $resultJsonFactory,
        QuoteManagement $quoteManagement,
        InvoiceRepository $invoiceRepository,
        OrderFactory $orderFactory,
        OrderSender $orderSender,
        Data $jsonHelper,
        CookieManagerInterface $cookieManager

    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_twigpayModel = $twigpayModel;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_quoteManagement = $quoteManagement;
        $this->invoiceRepository = $invoiceRepository;
        $this->_orderFactory = $orderFactory;
        $this->_orderSender = $orderSender;
        $this->_jsonHelper = $jsonHelper;
        $this->_cookieManager = $cookieManager;
        parent::__construct($context);
    }


    public function execute()
    {
        $this->_cookieManager->deleteCookie('mage-messages');

        // Create order before redirect to success
        $quote = $this->_checkoutSession->getQuote();
        if ($quote->getCustomerEmail() === null && $quote->getBillingAddress()->getEmail() !== null) {
            $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
        }
        $quoteId = $quote->getId();
        $quote->collectTotals()->save();

        $this->_checkoutSession
            ->setLastQuoteId($quote->getId())
            ->setLastSuccessQuoteId($quote->getId())
            ->clearHelperData();

        $order = $this->_quoteManagement->submit($quote);

        $invoiceCollection = $order->getInvoiceCollection();
        $data = $invoiceCollection->getData();
        if ($data) {
            foreach ($invoiceCollection as $invoice):
                $invoice->setState(Invoice::STATE_OPEN);
                $this->invoiceRepository->save($invoice);
            endforeach;
        }


        $payment = $quote->getPayment();
        $payment->getAdditionalInformation('twig_order_id');
        $payment->setMethod('twig_gateway');
        $payment->save();
        $quote->reserveOrderId();
        $quote->setPayment($payment);
        $quote->save();
        $this->_checkoutSession->replaceQuote($quote);

        $twigId = $payment->getAdditionalInformation('twig_order_id');
        $order->setData('twig_order_id', $twigId);
        $order->save();
        $this->_checkoutSession->setLastQuoteId($quoteId);

        if ($order) {
            // send email
            try {
                $this->_orderSender->send($order);
            } catch (\Exception $e) {
               $this->_helper->debug("Transaction Email Sending Error: " . json_encode($e));
            };
            
            $orderId = $this->getRequest()->getParam("id");
            $quoteId = $this->getRequest()->getParam("quote_id");

            $this->_checkoutSession->setLastSuccessQuoteId($quoteId);
            $this->_checkoutSession->setLastQuoteId($quoteId);
            $this->_checkoutSession->setLastOrderId($order->getId())
                                       ->setLastRealOrderId($order->getIncrementId())
                                       ->setLastOrderStatus($order->getStatus());

            $this->getResponse()->setRedirect(
                $this->_url->getUrl('checkout/onepage/success')
            );
        } else {
            $this->getResponse()->setRedirect(
                $this->_url->getUrl('checkout/cart')
            );
        }
    }
}
