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
namespace Twigpay\TwigPaymentGateway\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\OrderFactory;
use Twigpay\TwigPaymentGateway\Model\CreditMemo\UpdateCreditMemo;
use Psr\Log\LoggerInterface;

class Index extends Action
{
    protected $_logger;

    /**
     * @var OrderInterface
     */
    protected $orderInterface;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;

    /**
     * @param  ResourceConnection  $resourceConnection
     */

    public function __construct(
        LoggerInterface $logger,
        OrderInterface $orderInterface,
        OrderFactory $orderFactory,
        Invoice $invoiceModel,
        InvoiceRepository $invoiceRepository,
        TransactionRepositoryInterface $transactionRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoRepositoryInterface $creditmemoRepository,
        ResourceConnection $resourceConnection,
        CreditmemoManagementInterface $creditmemoManagement,
        CreditmemoInterface $creditmemoInterface,
        UpdateCreditMemo $updateCreditMemo,
        Context $context
    ) {
        $this->_logger = $logger;
        $this->orderInterface = $orderInterface;
        $this->_orderFactory = $orderFactory;
        $this->invoiceModel = $invoiceModel;
        $this->invoiceRepository = $invoiceRepository;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->resourceConnection = $resourceConnection;
        $this->creditmemoManagement = $creditmemoManagement;
        $this->creditmemoInterface = $creditmemoInterface;
        $this->updateCreditMemo = $updateCreditMemo;
        parent::__construct($context);

        // CsrfAwareAction Magento2.3 compatibility
        if (interface_exists("\Magento\Framework\App\CsrfAwareActionInterface")) {
            $request = $this->getRequest();
            if ($request->isPost() && empty($request->getParam('form_key'))) {
                $formKey = $this->_objectManager->get(\Magento\Framework\Data\Form\FormKey::class);
                $request->setParam('form_key', $formKey->getFormKey());
            }
        }
    }

    public function execute()
    {
        $request = $this->getRequest()->getContent();
        $receiveData = json_decode($request, true);
        try {
            if (!array_key_exists('operation', $receiveData)) {
                throw new \Exception('This request operation not found.', 404);
            }
            //get data from the request
            $id = $receiveData['orderId'];
            $orderobj = $this->orderInterface->loadByAttribute('twig_order_id', $id);
            $order = $orderobj->getData();
            $payment = $orderobj->getPayment();
            $orderId = $order['entity_id'];

            switch ($receiveData['operation']) {
//         CAPTURE THE ORDER
                case 'CAPTURE':
                    if ($receiveData['status'] === 'SUCCESS') {
                        $newState = \Magento\Sales\Model\Order::STATE_COMPLETE;
                        $newStatus = \Magento\Sales\Model\Order::STATE_COMPLETE;
                        $orderobj->addStatusHistoryComment('Successfully captured by Twig');


                        $invoices = $this->getAllInvoice($orderId)->getData();
                        foreach ($invoices as $invoice) {
                            $invoiceID = $invoice['entity_id'];
                            $invoiceData = $this->invoiceRepository->get($invoiceID);
                            $paid = Invoice::STATE_PAID;
                            $invoiceData->setData('invoice_exists', 1);
                            $invoiceData->setState($paid)->setStatus($paid);
                            $this->invoiceRepository->save($invoiceData);
                            $transaction_id = $invoiceData->getTransactionId();
                            $this->getTransaction($transaction_id)->setIsClosed(1)->save();
                        }

                        $this->_logger->info("Order with ID ".$orderId." has been successfully captured by Twig!");
                    } else {
                        $newState = "twig_capture_failed";
                        $newStatus = "twig_capture_failed";
                        $orderobj->addStatusHistoryComment('Capture Failed by Twig');
                        $invoices = $this->getAllInvoice($orderId)->getData();
                        foreach ($invoices as $invoice) {
                            $invoiceID = $invoice['entity_id'];
                            $invoiceData = $this->invoiceRepository->get($invoiceID);
                            $invoiceData->setState(Invoice::STATE_CANCELED);
                            $this->invoiceRepository->save($invoiceData);
                            $transaction_id = $invoiceData->getTransactionId();
                            $this->getTransaction($transaction_id)->setIsClosed(1)->save();
                        }

                        $this->_logger->error("Order with ID ".$orderId." failed to be captured by Twig!");
                    }

                    $orderobj->setState($newState)->setStatus($newStatus);
                    $orderobj->save();
                    break;


//         REFUND THE ORDER
                case 'REFUND':
                    if ($receiveData['status'] === 'SUCCESS') {
                        $newState = 'twig_refunded';
                        $newStatus = 'twig_refunded';
                        $orderobj->addStatusHistoryComment('Successfully Refunded by Twig');

                        $creditmemos = $orderobj->getCreditmemosCollection()->getData();
                        foreach ($creditmemos as $creditmemo) {
                            $creditmemoId = $creditmemo['entity_id'];
                            $creditmemoTrxID = $creditmemo['transaction_id'];
                            //State Refunded
                            $creditMemoState = 2;
                            $this->updateCreditMemo->updateCreditMemo($creditmemoId, $creditMemoState);
                            $this->getTransaction($creditmemoTrxID)->setIsClosed(1)->save();
                        }
                        $this->_logger->info("Order with ID ".$orderId." has been successfully refunded by Twig!");
                    } else {
                        $newState = "twig_refund_fail";
                        $newStatus = "twig_refund_fail";
                        $orderobj->addStatusHistoryComment('Refund Failed by Twig');
                        $creditmemos = $orderobj->getCreditmemosCollection()->getData();
                        foreach ($creditmemos as $creditmemo) {
                            $creditmemoId = $creditmemo['entity_id'];
                            $creditmemoTrxID = $creditmemo['transaction_id'];
                            //State  Canceled
                            $creditMemoState = 3;
                            $this->updateCreditMemo->updateCreditMemo($creditmemoId, $creditMemoState);
                            $this->getTransaction($creditmemoTrxID)->setIsClosed(1)->save();
                        }
                        $this->_logger->error("Order with ID ".$orderId." failed to be refunded by Twig!");
                    }

                    $orderobj->setState($newState)->setStatus($newStatus);
                    $orderobj->save();

                    break;


//         CANCEL THE ORDER
                case 'CANCEL':

                    if ($receiveData['status'] === 'SUCCESS') {
                        $newState = \Magento\Sales\Model\Order::STATE_CANCELED;
                        $newStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
                        $orderobj->addStatusHistoryComment('Successfully Canceled By Twig');
                        $this->_logger->info("Order with ID ".$orderId." has been canceled by Twig!");
                    } else {
                        $newState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                        $newStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
                        $orderobj->addStatusHistoryComment('Cancel Failed by Twig');
                        $this->_logger->error("Order with ID ".$orderId." failed to be canceled by Twig!");
                    }
                    $orderobj->setState($newState)->setStatus($newStatus);
                    $orderobj->save();
                    break;

//         EXPIRE THE ORDER
                case 'EXPIRE':
                    $newState = "twig_expired";
                    $newStatus = "twig_expired";
                    $orderobj->addStatusHistoryComment('Expired By Twig');
                    $orderobj->setState($newState)->setStatus($newStatus);
                    $orderobj->save();
                    $this->_logger->info("Order with ID ".$orderId." is expired in Twig!");
                    break;

                default:
                    $response['status'] = 200;
                    $response['message'] = 'The '.$receiveData['operation'].' event has been ignored by Magento.';
                    break;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $log['response_error'] = [
                'error_code' => $e->getCode(),
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ];

            throw new \Exception($e->getMessage(), (!empty($e->getCode()) ? (int)$e->getCode() : 500));
        }
    }

    public function getAllInvoice($orderId)
    {
        $order = $this->_orderFactory->create()->load($orderId);
        $invoiceCollection = $order->getInvoiceCollection();
        return $invoiceCollection;
    }

    public function getTransaction($transactionId)
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(
            TransactionInterface::TXN_ID,
            $transactionId
        );

        $searchCriteria = $searchCriteriaBuilder
            ->setPageSize(1)
            ->setCurrentPage(1)
            ->create();

        $transactionList = $this->transactionRepository->getList($searchCriteria);
        if (count($items = $transactionList->getItems())) {
            $transaction = current($items);
            return $transaction;
        }
    }

}
