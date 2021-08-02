<?php

namespace Laybuy\Laybuy\Plugin\Block\Adminhtml\Order;

use Magento\Backend\Model\UrlInterface;
use Magento\Sales\Block\Adminhtml\Order\View as Subject;
use Magento\Sales\Model\Order;
use Laybuy\Laybuy\Model\Config;

/**
 * Class View
 */
class View
{
    /**
     * @var UrlInterface
     */
    protected $backendUrl;

    /**
     * View constructor.
     * @param UrlInterface $backendUrl
     */
    public function __construct(
        UrlInterface $backendUrl
    ) {
        $this->backendUrl = $backendUrl;
    }

    /**
     * @param Subject $subject
     */
    public function beforeSetLayout(Subject $subject)
    {
        $order = $subject->getOrder();
        if ($order->getState() === Order::STATE_PENDING_PAYMENT
            && $order->getPayment()->getMethod() === Config::CODE
            && !$order->hasInvoices()) {
            $sendOrder = $this->backendUrl->getUrl('laybuy/order/invoice/order_id/' . $subject->getOrderId());
            $subject->addButton(
                'laybuyInvoiceOrder',
                [
                    'label' => __('Laybuy Invoice'),
                    'onclick' => "setLocation('" . $sendOrder . "')",
                    'class' => 'action-default'
                ]
            );
        }
    }
}
