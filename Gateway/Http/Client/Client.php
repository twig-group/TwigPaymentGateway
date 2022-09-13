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
namespace Twigpay\TwigPaymentGateway\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Zend_Http_Client_Exception;

class Client implements ClientInterface
{
    /**
     * @var ZendClientFactory
     */
    private ZendClientFactory $_httpClientFactory;

    public function __construct(ZendClientFactory $httpClientFactory)
    {
        $this->_httpClientFactory = $httpClientFactory;
    }

    /**
     * @throws Zend_Http_Client_Exception
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $client = $this->_httpClientFactory->create();
        $client->setUri($transferObject->getUri());
        $client->setMethod($transferObject->getMethod());
        $client->setHeaders(($transferObject->getHeaders()));
        $client->setRawData(json_encode($transferObject->getBody()));

        $response = $client->request();


        $responseFinal = array();
        $responseFinal['message'] = $response->getMessage();
        $responseFinal['code'] = $response->getStatus();
        $responseFinal['body'] = $response->getBody();

        return $responseFinal;
    }


}
