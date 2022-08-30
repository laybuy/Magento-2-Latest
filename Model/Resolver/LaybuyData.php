<?php

namespace Laybuy\Laybuy\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class LaybuyData implements ResolverInterface
{
    private $layBuyDataProvider;

    public function __construct(
        \LayBuy\LayBuy\Model\Resolver\DataProvider\LaybuyDataProvider $layBuyDataProvider
    ) {
        $this->layBuyDataProvider = $layBuyDataProvider;
    }

    public function resolve(
        Field $field,
              $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
	
        return [$this->layBuyDataProvider->getData($args)];
    }
}
