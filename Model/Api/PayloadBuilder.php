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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Twigpay\TwigPaymentGateway\Model\Api\ConfigInterface as TwigApiConfig;

class PayloadBuilder
{
    /** @var CheckoutSession */
    protected CheckoutSession $checkoutSession;
    private StoreManagerInterface $storeManager;
    private TwigApiConfig $twigApiConfig;
    private  $config;

    /**
     * @param StoreManagerInterface $storeManager
     * @param TwigApiConfig $twigApiConfig
     * @param ConfigInterface $config
     */


    public function __construct(
        StoreManagerInterface $storeManager,
        TwigApiConfig         $twigApiConfig ,
        ScopeConfigInterface $config
    )
    {
        $this->storeManager = $storeManager;
        $this->twigApiConfig = $twigApiConfig;
        $this->config = $config;

    }

    /**
     * Build Twig Checkout Payload
     * @param $quote
     * @param $reference
     * @return array
     * @throws NoSuchEntityException
     */
    public function buildTwigCheckoutPayload($quote, $reference): array
    {
        $configPath = 'payment/twig_gateway/payment_action';
        $payment_action=  $this->config->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        );
        if  ($payment_action == "authorize_capture"){
            $payment_action = "capture" ;
        }
        else{
            $payment_action = "authorization" ;
        }

        $buildAmountPayload = $this->buildAmountPayload($quote);
        $buildCustomerPayload = $this->buildCustomerPayload($quote);
        $buildItemPayload = $this->buildItemPayload($quote);
        $shippingPayload = $this->buildShippingPayload($quote);
        $billingPayload = $this->buildBillingPayload($quote);
        $buildMerchantUrl = $this->buildMerchantUrl($quote, $reference);

        $orderData = [
            "merchantOrderReference" => $quote->getId() . uniqid(),
            "dateCreated" => date('Y-m-d\TH:i:s.u', strtotime($quote->getCreatedAt())),
            "Intent" => $payment_action,
        ];

        $payload = array_merge_recursive(
            $orderData,
            $buildAmountPayload,
            $buildCustomerPayload,
            $buildMerchantUrl,
            $buildItemPayload,
            $shippingPayload,
            $billingPayload
        );
        return $payload;
    }


    /**
     * Build Amount  Payload
     * @param $quote
     * @return array
     * @throws NoSuchEntityException
     */
    private function buildAmountPayload($quote): array
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $amountPayload["amount"] = [
            "currencyCode" => $currencyCode,
            "value" => $this->formatValue($quote->getGrandTotal()),
            "breakdown" => [
                "items" => $this->formatValue($quote->getSubtotal()),
                "shipping" => $this->formatValue($quote->getShippingAddress()->getShippingAmount()),
                "tax" => $this->formatValue($quote->getShippingAddress()->getBaseTaxAmount()),
            ]
        ];
        return $amountPayload;
    }


    /**
     * Build Customer Payload
     * @param $quote
     * @return array
     */
    private function buildCustomerPayload($quote): array
    {
        $billingAddress = $quote->getBillingAddress();
        $customerPayload["customer"] = [
            "first_name" => $quote->getCustomerFirstname() ? $quote->getCustomerFirstname() : $billingAddress->getFirstname(),
            "last_name" => $quote->getCustomerLastname() ? $quote->getCustomerLastname() : $billingAddress->getLastname(),
            "email" => $quote->getCustomerEmail() ? $quote->getCustomerEmail() : $billingAddress->getEmail()
        ];
        return $customerPayload;
    }


    /**
     * Build Merchant Success/Cancel Url
     * @param $quote
     * @param $reference
     * @return array
     */
    private function buildMerchantUrl($quote, $reference): array
    {
        $orderId = $quote->getId();
        // $orderId = $quote->getReservedOrderId();
        $completeUrl = $this->twigApiConfig->getSuccessUrl($orderId, $reference, $quote->getId());
        $cancelUrl = $this->twigApiConfig->getCancelUrl($orderId, $reference);
        $pushUrl = $this->twigApiConfig->getPushUrl();

        $merchantUrl["merchant"] = [
            "confirmUrl" => $completeUrl,
            "cancelUrl" => $cancelUrl,
            "pushUrl" =>$pushUrl
        ];

        return $merchantUrl;
    }


    /**
     * Build Billing Address Payload
     * @param $quote
     * @return array
     */
    private function buildBillingPayload($quote): array
    {
        $billingAddress = $quote->getBillingAddress();
        $billingPayload["billing"]["address"] = [
            "firstName" => $billingAddress->getFirstname(),
            "lastName" => $billingAddress->getLastname(),
            "addressLine1" => $billingAddress->getStreetLine(1),
            "addressLine2" => $billingAddress->getStreetLine(2),
            "city" => $billingAddress->getCity(),
            "country" => $billingAddress->getRegionCode(),
            "postalCode" => $billingAddress->getPostcode(),
            "countryCode" => $billingAddress->getCountryId(),

        ];
        return $billingPayload;
    }


    /**
     * Build Shipping Address Payload
     * @param $quote
     * @return array
     */
    private function buildShippingPayload($quote): array
    {
        $shippingAddress = $quote->getShippingAddress();
        $shippingPayload["shipping"] = [
            "method" => $shippingAddress->getShippingMethod(),
            "amount" => $shippingAddress->getShippingAmount(),
            "address" => [
                "firstName" => $shippingAddress->getFirstname(),
                "lastName" => $shippingAddress->getLastname(),
                "addressLine1" => $shippingAddress->getStreetLine(1),
                "addressLine2" => $shippingAddress->getStreetLine(2),
                "city" => $shippingAddress->getCity(),
                "country" => $shippingAddress->getRegionCode() ? $shippingAddress->getRegionCode() : $shippingAddress->getCountryId(),
                "postalCode" => $shippingAddress->getPostcode(),
                "countryCode" => $shippingAddress->getCountryId()
            ],
        ];
        return $shippingPayload;
    }


    /**
     * Build Cart Item Payload
     * @param $quote
     * @return array
     * @throws NoSuchEntityException
     */
    private function buildItemPayload($quote): array
    {
        $objectManager = ObjectManager::getInstance();
        $itemPayload["lineItems"] = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $productId = $item->getId();
            $productName = $item->getName();
            $productDescription = $item->getDescription();
            $product = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getProduct()->getId());
            $productQuantity = $item->getQty();
            //  img url
            $objectManager = ObjectManager::getInstance();
            $helperImport = $objectManager->get('\Magento\Catalog\Helper\Image');
            $imageUrl = $helperImport->init($product, 'product_page_image_small')
                ->setImageFile($product->getSmallImage())
                ->resize(380)
                ->getUrl();

            $tax = $item->getTaxAmount();
            $quantity = $productQuantity;
            $category = $item->getCategory();
            $itemData = [
                "itemId" => $productId,
                "name" => $productName,
                "description" => $productDescription,
                "imageUrl" => $imageUrl,
                "amount" => strval(round($item->getPrice())),
                "tax" => $this->formatValue($tax),
                "quantity" => $quantity,
                "category" => $category,
            ];
            $itemPayload["lineItems"][] = $itemData;
        }
        return $itemPayload;
    }

    public function formatValue($value): string
    {
        return number_format((float)$value, 2, '.', '');
    }
}
