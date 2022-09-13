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

use  Magento\Framework\App\Action\Action;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Model\QuoteManagement;

class Cancel extends Action
{
    /**
     * @var Context
     */
    protected Context $context;
    /**
     * @var Session
     */
    protected Session $checkoutSession;
    /**
     * @var QuoteManagement
     */
    protected QuoteManagement $_quoteManagement;

    /**
     * TwigPay constructor
     * @param Context $context
     * @param Session $checkoutSession
     * @param QuoteManagement $quoteManagement
     */
    public function __construct(
        Session $checkoutSession,
        QuoteManagement $quoteManagement,
        Context $context
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_quoteManagement = $quoteManagement;
        parent::__construct($context);
    }

    public function execute()
    {
        $quote = $this->_checkoutSession->getQuote();
        if ($quote->getCustomerEmail() === null && $quote->getBillingAddress()->getEmail() !== null) {
            $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
        }
        $quote->collectTotals()->save();
        $order = $this->_quoteManagement->submit($quote);
        if ($order) {
            $order->registerCancellation('Canceled from Twig Website')->save();
        }
        $this->getResponse()->setRedirect(
            $this->_url->getUrl('checkout/cart')
        );
    }
}
