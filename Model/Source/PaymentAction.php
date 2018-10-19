<?php

namespace Laybuy\Laybuy\Model\Source;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentAction
 * @package Laybuy\Laybuy\Model\Source
 */
class PaymentAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Possible actions on order place
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => AbstractMethod::ACTION_ORDER,
                'label' => __('Order'),
            ],
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture'),
            ]
        ];
    }
}