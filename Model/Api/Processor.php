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
namespace Twigpay\TwigPaymentGateway\Model\Api;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Zend_Http_Client;

/**
 * Class Processor
 * @package Twigpay\TwigPaymentGateway\Model\Api;
 */
class Processor
{
    /**
     * @var ScopeConfig
     */
    protected ScopeConfig $scopeConfig;
    /**
     * @var Curl
     */
    protected Curl $curl;

    protected $_logger;


    /**
     * Processor constructor.
     * @param Curl $curl
     * @param ScopeConfig $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        Curl        $curl,
        ScopeConfig $scopeConfig,
        LoggerInterface $logger
    )
    {
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->_logger = $logger;

    }

    /**
     * Call to Twig Gateway
     *
     * @param $url
     * @param bool $body
     * @param string $method
     * @return string
     * @throws LocalizedException
     */
    public function call($url, $body = false, $method = Zend_Http_Client::GET): string
    {
        try {

            $x_api_key_path = 'payment/twig_gateway/x_api_key';
            $twig_client_id_path = 'payment/twig_gateway/twig_client_id';
            $twig_api_version_path = 'payment/twig_gateway/twig_api_version';

            $x_api_key=  $this->scopeConfig->getValue($x_api_key_path, ScopeInterface::SCOPE_STORE);
            $twig_client_id=  $this->scopeConfig->getValue($twig_client_id_path, ScopeInterface::SCOPE_STORE);
            $twig_api_version=  $this->scopeConfig->getValue($twig_api_version_path, ScopeInterface::SCOPE_STORE);

            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("x-api-key", $x_api_key);
            $this->curl->addHeader("twig-client-id", $twig_client_id);
            $this->curl->addHeader("twig-api-version", $twig_api_version);
            switch ($method) {
                case 'POST':
                    $this->curl->post($url, ($body));
                    break;
                case 'GET':
                    $this->curl->get($url);
                    break;
                default:
                    break;
            }

            $body = $this->curl->getBody();
            $bodyArr = json_decode($body);
            $info = $this->curl->getStatus();
            $result = array();
            $result['body'] = $bodyArr;
            $result['info'] = $info;

        } catch (Exception $e) {
            throw new LocalizedException(
                __('Gateway error: %1', $e->getMessage())
            );
        }
        return json_encode($result);
    }

}
