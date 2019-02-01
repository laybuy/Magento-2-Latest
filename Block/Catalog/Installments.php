<?php

namespace Laybuy\Laybuy\Block\Catalog;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Directory\Model\Currency;
use Laybuy\Laybuy\Model\Config;

/**
 * Class Installments
 * @package Laybuy\Laybuy\Block\Catalog
 */
class Installments extends Template
{
    /**
     * @var Product
     */
    protected $product;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Currency
     */
    protected $currency;

    /**
     * @var Config
     */
    protected $laybuyConfig;

    /**
     * Installments constructor.
     * @param Product $product
     * @param Registry $registry
     * @param Currency $currency
     * @param Config $config
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Product $product,
        Registry $registry,
        Currency $currency,
        Config $config,
        Template\Context $context,
        array $data = []
    )
    {
        $this->product = $product;
        $this->registry = $registry;
        $this->currency = $currency;
        $this->laybuyConfig = $config;
        parent::__construct($context, $data);
    }

    /**
     * Checks if block should be displayed
     *
     * @return bool
     */
    public function canShow()
    {
        if (!$this->laybuyConfig->isActive()  || !in_array($this->_storeManager->getStore()->getCurrentCurrency()->getCode(), \Laybuy\Laybuy\Model\Config::SUPPORTED_CURRENCY_CODES)) {
            return false;
        }

        return true;
    }

    /**
     * Returns Laybuy installments amount for product
     *
     * @return string
     */
    public function getInstallmentsAmount()
    {
        $product = $this->registry->registry('product');

        if ($price = $product->getFinalPrice()) {
            return $this->_storeManager->getStore()->getCurrentCurrency()->getCurrencySymbol() . number_format($price / 6, 2);
        }
    }


}