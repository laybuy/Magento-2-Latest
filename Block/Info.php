<?php
namespace Laybuy\Laybuy\Block;

/**
 * Class Info
 * @package Laybuy\Laybuy\Block
 */
class Info extends \Magento\Payment\Block\Info
{

    /**
     *
     * @var string
     */
    protected $_template = 'Laybuy_Laybuy::info/laybuy.phtml';

    /**
     * @param null $transport
     * @return $this|\Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $data = $this->getInfo()->getAdditionalInformation();
        $decodedData = [];
        foreach ($data as $key => $value) {
                $decodedData[$key] = $value;
        }

        $transport = parent::_prepareSpecificInformation($transport);

        unset($decodedData["method_title"]);
        $this->_paymentSpecificInformation = $transport->setData(array_merge($decodedData, $transport->getData()));

        return $this->_paymentSpecificInformation;
    }
}