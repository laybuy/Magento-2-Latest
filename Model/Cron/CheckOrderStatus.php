<?php

namespace Laybuy\Laybuy\Model\Cron;

/**
 * Class CheckOrderStatus
 * @package Laybuy\Laybuy\Model\Cron
 */
class CheckOrderStatus
{

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected $searchCriteria;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filter;

    /**
     * @var \Laybuy\Laybuy\Model\Laybuy
     */
    protected $laybuy;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * CheckOrderStatus constructor.
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface
     * @param \Magento\Framework\Api\FilterBuilder $filter
     * @param \Laybuy\Laybuy\Model\LaybuyFactory $laybuyFactory
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     */
    public function __construct(
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface,
        \Magento\Framework\Api\FilterBuilder $filter,
        \Laybuy\Laybuy\Model\LaybuyFactory $laybuyFactory,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->filter = $filter;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->laybuy = $laybuyFactory->create();
        $this->searchCriteria = $searchCriteriaInterface;
    }

    /**
     *
     * Cancel Laybuy Orders in pending state
     *
     * @throws \Exception
     */
    public function execute()
    {
        // We skip cancelling pending orders if Authorize Capture method selected, as pending orders won't be created
        if ($this->laybuy->getConfigPaymentAction() == \Laybuy\Laybuy\Model\Laybuy::ACTION_AUTHORIZE_CAPTURE) {
            return;
        }

        //Using UTC as magento sets UTC as default timezone, and orders created_at gets updated according to this
        //ignoring timezone
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $timeToCompare = $date->modify('-30 minutes')->format('Y-m-d h:i:s');


        $filter1 = $this->filter->create()->setField('status')->setValue('pending')->setConditionType('eq');
        $filter2 = $this->filter->create()->setField('created_at')->setValue($timeToCompare)->setConditionType('lt');
        $filterGroup = $this->filterGroupBuilder->create()->setFilters([$filter1]);
        $filterGroup2 = $this->filterGroupBuilder->create()->setFilters([$filter2]);
        $searchCriteria = $this->searchCriteria->setFilterGroups([$filterGroup, $filterGroup2]);
        $orders = $this->orderRepository->getList($searchCriteria);

        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($orders as $order) {
            if ($order->getPayment()->getMethod() == \Laybuy\Laybuy\Model\Config::CODE && !$order->hasInvoices()) {
                $this->laybuy->cancelMagentoOrder($order);
            }
        }
    }
}
