<?php

namespace Laybuy\Laybuy\Cron\Order\Type;

use Laybuy\Laybuy\Model\Laybuy;
use Laybuy\Laybuy\Model\Logger\ConvertQuoteLogger;
use Laybuy\Laybuy\Model\LaybuyConvertOrder;
use Magento\Framework\App\ResourceConnection;

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
     * Order constructor.
     * @param ResourceConnection $resource
     * @param ConvertQuoteLogger $logger
     * @param LaybuyConvertOrder $laybuyConvertOrder
     */
    public function __construct(
        ResourceConnection $resource,
        ConvertQuoteLogger $logger,
        LaybuyConvertOrder $laybuyConvertOrder
    )
    {
        $this->resource = $resource;
        $this->logger = $logger;
        $this->laybuyConvertOrder = $laybuyConvertOrder;
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
            $this->laybuyConvertOrder->validateAndCreateInvoiceOrder($order['entity_id'], $order['increment_id'], $token);
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
            $this->laybuyConvertOrder->validateAndCreateInvoiceOrder($order['entity_id'], $order['increment_id'], $token);
        }
    }
}
