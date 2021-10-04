<?php

namespace Laybuy\Laybuy\Cron\Order\Type;

use Laybuy\Laybuy\Model\Laybuy;
use Laybuy\Laybuy\Model\Logger\ConvertQuoteLogger;
use Laybuy\Laybuy\Model\LaybuyConvertOrder;
use Magento\Framework\App\ResourceConnection;
use Laybuy\Laybuy\Model\Email;

/**
 * Class Order
 */
class Order
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var ConvertQuoteLogger
     */
    protected $logger;

    /**
     * @var LaybuyConvertOrder
     */
    protected $laybuyConvertOrder;

    /**
     * @var Email
     */
    protected $laybuyEmail;

    private $reportList = [];

    /**
     * Order constructor.
     * @param ResourceConnection $resource
     * @param ConvertQuoteLogger $logger
     * @param LaybuyConvertOrder $laybuyConvertOrder
     * @param Email $laybuyEmail
     */
    public function __construct(
        ResourceConnection $resource,
        ConvertQuoteLogger $logger,
        LaybuyConvertOrder $laybuyConvertOrder,
        Email $laybuyEmail
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
        $this->laybuyConvertOrder = $laybuyConvertOrder;
        $this->laybuyEmail = $laybuyEmail;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $timeToCompare = $date->modify('-120 minutes')->format('Y-m-d h:i:s');
        $connection = $this->resource->getConnection();
        $select = $connection->select()->from(
            ['sales_order' => $connection->getTableName('sales_order')],
            ['sales_order.entity_id', 'sales_order.increment_id']
        )->joinLeft(
            ['sales_order_payment' => $connection->getTableName('sales_order_payment')],
            'sales_order_payment.parent_id = sales_order.entity_id',
            ['sales_order_payment.additional_information']
        )
            ->where('sales_order.created_at > (?)', $timeToCompare)
            ->where('sales_order_payment.method = (?)', 'laybuy_payment')
            ->where('sales_order.state = (?)', \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $orderList = $connection->fetchAll($select);
        foreach ($orderList as $order) {
            $token = '';
            if ($order['additional_information']) {
                $laybuyData = json_decode($order['additional_information'], true);
                if (isset($laybuyData['Token'])) {
                    $token = $laybuyData['Token'];
                }
            }
            $this->reportList[] = $this->laybuyConvertOrder->validateAndCreateInvoiceOrder($order['entity_id'], $order['increment_id'], $token);
        }
        if (count($this->reportList)) {
            $reportByStoreList = [];
            foreach ($this->reportList as $report) {
                $reportByStoreList[$report['store_id']][] = $report;
            }
            $this->laybuyEmail->sendEmailListByStore($reportByStoreList);
        }
    }

    /**
     * @throws \Exception
     */
    public function executeCommand()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $timeToCompare = $date->modify('-10080 minutes')->format('Y-m-d h:i:s');
        $connection = $this->resource->getConnection();
        $select = $connection->select()->from(
            ['sales_order' => $connection->getTableName('sales_order')],
            ['sales_order.entity_id', 'sales_order.increment_id']
        )->joinLeft(
            ['sales_order_payment' => $connection->getTableName('sales_order_payment')],
            'sales_order_payment.parent_id = sales_order.entity_id',
            ['sales_order_payment.additional_information']
        )
            ->where('sales_order.created_at > (?)', $timeToCompare)
            ->where('sales_order_payment.method = (?)', 'laybuy_payment')
            ->where('sales_order.state = (?)', \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $orderList = $connection->fetchAll($select);
        foreach ($orderList as $order) {
            $token = '';
            if ($order['additional_information']) {
                $laybuyData = json_decode($order['additional_information'], true);
                if (isset($laybuyData['Token'])) {
                    $token = $laybuyData['Token'];
                }
            }
            $this->reportList[] = $this->laybuyConvertOrder->validateAndCreateInvoiceOrder($order['entity_id'], $order['increment_id'], $token);
        }
        if (count($this->reportList)) {
            $reportByStoreList = [];
            foreach ($this->reportList as $report) {
                $reportByStoreList[$report['store_id']][] = $report;
            }
            $this->laybuyEmail->sendEmailListByStore($reportByStoreList);
        }
    }
}
