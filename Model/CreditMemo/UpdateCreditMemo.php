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
namespace Twigpay\TwigPaymentGateway\Model\CreditMemo;

use Exception;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;

class UpdateCreditMemo
{
    /**
     * @var CreditmemoRepositoryInterface
     */
    protected CreditmemoRepositoryInterface $creditmemoRepository;
    /**
     * @var OrderInterface
     */
    protected $orderInterface;

    private ResourceConnection $resourceConnection;

    /**
     * Update Credit Memo Constructor
     * @param  CreditmemoRepositoryInterface  $creditmemoRepository
     * @param  ResourceConnection  $resourceConnection
     * @param  OrderInterface  $orderInterface
     */

    public function __construct(
        CreditmemoRepositoryInterface $creditmemoRepository,
        ResourceConnection $resourceConnection,
        OrderInterface $orderInterface
    ) {
        $this->creditmemoRepository = $creditmemoRepository;
        $this->resourceConnection = $resourceConnection;
        $this->orderInterface = $orderInterface;
    }

    /**
     * @param $creditmemoId
     * @param $state
     * @return bool
     * @throws Exception
     */
    public function updateCreditMemo($creditmemoId, $state): bool
    {
        $connection = $this->resourceConnection->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $creditmemoGridTable = $connection->getTableName('sales_creditmemo_grid');
        $creditmemoTable = $connection->getTableName('sales_creditmemo');

        $connection->rawQuery('update `'.$creditmemoGridTable.'` set state='.$state.' WHERE entity_id='.$creditmemoId);
        $connection->rawQuery('update `'.$creditmemoTable.'` set state='.$state.'  WHERE entity_id='.$creditmemoId);

        return true;
    }
}
