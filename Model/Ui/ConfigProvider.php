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

namespace Twigpay\TwigPaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Twigpay\TwigPaymentGateway\Gateway\Http\Client\ClientMock;
use Magento\Framework\View\Asset\Repository;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Repository
     */
    private $assetRepository;

    /**
     *
     * @param  Repository  $assetRepository
     */
    public function __construct(
        Repository $assetRepository
    ) {
        $this->assetRepository = $assetRepository;
    }

    const CODE = 'twig_gateway';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $banktransferLogoUrl = $this->assetRepository->getUrlWithParams(
            'Twigpay_TwigPaymentGateway::images/twig_pay.svg',
            []
        );
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud'),
                    ],
                    'twig_logo' => [
                        'twig_logo_checkout' => $banktransferLogoUrl
                    ]
                ]
            ]
        ];
    }
}
