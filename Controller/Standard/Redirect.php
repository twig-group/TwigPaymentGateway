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
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Twigpay\TwigPaymentGateway\Model\TwigPay;

class Redirect extends Action
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
     * Twigpay constructor.
     * @param  Context  $context
     * @param  Session  $checkoutSession
     * @param  TwigPay  $twigpayModel
     * @param  JsonFactory  $resultJsonFactory
     * @param  CookieManagerInterface  $cookieManager
     */

    public function __construct(
        Context $context,
        Session $checkoutSession,
        TwigPay $twigpayModel,
        JsonFactory $resultJsonFactory,
        CookieManagerInterface $cookieManager
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_twigpayModel = $twigpayModel;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cookieManager = $cookieManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->_cookieManager->deleteCookie('mage-messages');

        $quote = $this->_checkoutSession->getQuote();
        $payment = $quote->getPayment();
        $payment->setMethod('twig_gateway');
        $payment->save();

        $quote->reserveOrderId();
        $quote->setPayment($payment);
        $quote->save();
        $this->_checkoutSession->replaceQuote($quote);
        $checkout = json_decode($this->_twigpayModel->getTwigRedirectUrl($quote));

        if (isset($checkout->id)) {
            $checkoutUrl = $checkout->id;
            $resultJson = $this->_resultJsonFactory->create();
            $response = $resultJson->setData(['info' => 'success', 'checkoutUrl' => $checkoutUrl]);
        } else {
            $message = $checkout->message;
            $resultJson = $this->_resultJsonFactory->create();
            $response = $resultJson->setData(['info' => 'fail', 'message' => $message]);
        }

        return $response;
    }

}
