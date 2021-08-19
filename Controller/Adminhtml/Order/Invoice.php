<?php

namespace Laybuy\Laybuy\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Sales\Model\OrderRepository;
use Magento\Backend\App\Action\Context;
use Laybuy\Laybuy\Model\Logger\ConvertQuoteLogger;
use Laybuy\Laybuy\Model\LaybuyConvertOrder;

/**
 * Class Invoice
 */
class Invoice extends Action
{
    /**
     * @var
     */
    protected $resultPageFactory;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var ConvertQuoteLogger
     */
    protected $logger;

    /**
     * @var LaybuyConvertOrder
     */
    protected $laybuyConvertOrder;

    /**
     * Invoice constructor.
     * @param Context $context
     * @param OrderRepository $orderRepository
     * @param ConvertQuoteLogger $logger
     * @param LaybuyConvertOrder $laybuyConvertOrder
     */
    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        ConvertQuoteLogger $logger,
        LaybuyConvertOrder $laybuyConvertOrder
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->laybuyConvertOrder = $laybuyConvertOrder;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderId = $this->getRequest()->getParam('order_id');
        if ($orderId) {
            try {
                $order = $this->orderRepository->get($orderId);
                $laybuyInformation = $order->getPayment()->getAdditionalInformation();
                $token = '';
                if (is_array($laybuyInformation)) {
                    if (isset($laybuyInformation['Token'])) {
                        $token = $laybuyInformation['Token'];
                    }
                }
                $status = $this->laybuyConvertOrder->validateAndCreateInvoiceOrder($order->getEntityId(), $order->getIncrementId(), $token);
                if ($status) {
                    $this->messageManager->addSuccessMessage(__('Create Laybuy invoice successfully.'));
                } else {
                    $this->messageManager->addErrorMessage(__('Can not create Laybuy invoice.'));
                }
            } catch (\Exception $e) {
                $this->logger->error('Can not create Laybuy invoice', [
                    'order_id' => $orderId,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->messageManager->addErrorMessage(__('Can not create Laybuy invoice.'));
            }
        } else {
            $this->messageManager->addErrorMessage(__('Can not create Laybuy invoice.'));
            return $resultRedirect->setPath('sales/order');
        }

        return $resultRedirect->setPath('sales/order/view/', ['order_id' => $orderId]);

    }
}
