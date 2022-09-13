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
namespace Twigpay\TwigPaymentGateway\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $connection = $setup->getConnection();

        if ($connection->tableColumnExists('sales_invoice', 'invoice_exists') === false) {
            $connection->addColumn(
                $setup->getTable('sales_invoice'),
                'invoice_exists',
                [
                    'type' => Table::TYPE_BOOLEAN,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Invoice already exists'
                ]
            );
        }

        if ($connection->tableColumnExists('sales_order', 'twig_order_id') === false) {
            $connection->addColumn(
                $setup->getTable('sales_order'),
                'twig_order_id',
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => false,
                    'identity' => true,
                    'comment' => 'Twig Order Id'
                ]
            );
        }
    }
}
