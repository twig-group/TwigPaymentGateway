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
namespace Magento\TwigPaymentGateway\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{


    /**
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * @var QuoteSetupFactory
     */
    protected $quoteSetupFactory;

    /**
     * @param  SalesSetupFactory  $salesSetupFactory
     * @param  QuoteSetupFactory  $quoteSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        QuoteSetupFactory $quoteSetupFactory

    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /**
         * Prepare database for install
         */
        $setup->startSetup();

        $connection = $setup->getConnection();

        $data = [];
        $statuses = [
            'twig_refunded' => __('Refunded'),
            'twig_capture_failed' => __('Capture Failed'),
            'twig_refund_failed' => __('Refund Failed'),
            'twig_expired' => __('Expired'),
        ];
        foreach ($statuses as $code => $info) {
            $record = $connection->select()->from('sales_order_status')
                ->where('status = ?', $code);
            if ((empty($connection->fetchAll($record)))) {
                $data[] = ['status' => $code, 'label' => $info];
            }

        }

        if (!empty($data)){
            $setup->getConnection()
                ->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], $data);
        }
        /**
         * Prepare database after install
         */
        $setup->endSetup();
    }
}
