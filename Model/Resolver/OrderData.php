<?php

namespace Laybuy\Laybuy\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class OrderData implements ResolverInterface
{
    private $orderDataProvider;

    public function __construct(
        \Laybuy\Laybuy\Model\Resolver\DataProvider\OrderDataProvider $orderDataProvider
    ) {
        $this->orderDataProvider = $orderDataProvider;
    }

    public function resolve(
        Field $field,
              $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {

        return [$this->orderDataProvider->getData($args)];
    }
}
