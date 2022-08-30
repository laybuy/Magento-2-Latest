<?php

namespace Laybuy\Laybuy\Model\Resolver\DataProvider;

use Laybuy\Laybuy\Model\Logger\Logger;

class OrderDataProvider
{
    protected $logger;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteria;

    /**
     * @var \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    public function __construct(
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        Logger $logger
    )
    {
        $this->orderRepository = $orderRepository;
        $this->searchCriteria = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
    }

    public function getData($args)
    {
        $quoteId = $this->maskedQuoteIdToQuoteId->execute($args['cartId']);
        if (!empty($quoteId)) {
            $searchCriteria = $this->searchCriteria
                ->addFilter('quote_id', $quoteId)
                ->create();
            $searchResult = $this->orderRepository->getList($searchCriteria);
            if ($searchResult->getTotalCount() > 0) {
                $orderList = $searchResult->getItems();
                return ['order' => ['order_number' => $orderList[0]->increment_id]];
            }
        }

        return ['order' => ['order_number' => '']];
    }
}