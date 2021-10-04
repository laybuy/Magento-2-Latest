<?php

namespace Laybuy\Laybuy\Model;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use \Magento\Payment\Gateway\Config\Config as ParentConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Class Config
 * @package Laybuy\Laybuy\Model
 */
class Config extends ParentConfig
{
    const CODE = 'laybuy_payment';

    const KEY_ACTIVE = 'active';

    const USE_SANDBOX = 'sandbox';

    const KEY_MIN_ORDER_TOTAL = 'min_order_total';

    const KEY_MAX_ORDER_TOTAL = 'max_order_total';

    const PAYMENT_ACTION = 'payment_action';

    const KEY_TITLE = 'title';

    const KEY_MERCHANT_ID = 'merchant_id';

    const KEY_API_KEY = 'merchant_api_key';

    const KEY_SHOW_IN_PRODUCT_PAGE = 'show_in_product_page';

    const KEY_SHOW_IN_CATEGORY_PAGE = 'show_in_category_page';

    const KEY_SHOW_IN_CART_PAGE = 'show_in_cart_page';

    const KEY_SHOW_FULL_LOGO = 'show_full_logo';

    const ENABLE_CRON_EMAIL_REPORT = 'enable_email_cron_report';

    const PAYMENT_ERROR_EMAIl = 'payment_error_email';

    const EMAIL_TEMPLATE = 'email_template';

    const EMAIL_IDENTITY = 'email_identity';

    const API_ENDPOINT_LIVE = 'https://api.laybuy.com';

    const API_ENDPOINT_SANDBOX = 'https://sandbox-api.laybuy.com';

    const API_ORDER_CREATE = '/order/create';

    const API_ORDER_CONFIRM = '/order/confirm';

    const API_ORDER_CANCEL = '/order/cancel';

    const API_ORDER_REFUND = '/order/refund';

    const API_ORDER_CHECK = '/order/merchant';

    const API_ORDER_CHECK_BY_ID = '/order';

    const LAYBUY_SUCCESS = 'SUCCESS';

    const LAYBUY_FAILURE = 'ERROR';

    const LAYBUY_CANCELLED = 'CANCELLED';

    const LAYBUY_FIELD_REFERENCE_ORDER_ID = 'Reference Order Id';

    const SUPPORTED_CURRENCY_CODES = ['NZD', 'AUD', 'GBP', 'USD'];

    const FULL_LOGO = 'logo/full.svg';

    const SMALL_LOGO = 'logo/small.svg';

    const ASSET_URL = 'https://integration-assets.laybuy.com/magento1_laybuy/';

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var int
     */
    protected $currentStore;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param string $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        $methodCode = self::CODE,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
    }

    /**
     * Get Payment configuration status
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->getValue(self::KEY_ACTIVE, $this->getCurrentStore());
    }

    /**
     * Get Show In Page Configuration
     *
     * @return bool
     */
    public function showInProductPage()
    {
        return (bool)$this->getValue(self::KEY_SHOW_IN_PRODUCT_PAGE, $this->getCurrentStore());
    }

    /**
     * Get Show In Category Configuration
     *
     * @return bool
     */
    public function showInCategoryPage()
    {
        return (bool)$this->getValue(self::KEY_SHOW_IN_CATEGORY_PAGE, $this->getCurrentStore());
    }

    /**
     * Get Show In Cart Configuration
     *
     * @return bool
     */
    public function showInCartPage()
    {
        return (bool)$this->getValue(self::KEY_SHOW_IN_CART_PAGE, $this->getCurrentStore());
    }

    /**
     * Get Payment configuration status
     *
     * @return string
     */
    public function getLogo()
    {
        if((bool)$this->getValue(self::KEY_SHOW_FULL_LOGO, $this->getCurrentStore())){
            return self::FULL_LOGO;
        } else {
            return self::SMALL_LOGO;
        }

    }

    /**
     * Get Image from CDN path
     *
     * @return string
     */
    public function getMagentoAssetUrl($imagePath)
    {
        return self::ASSET_URL.$imagePath;
    }

    /**
     * Get The Laybuy Merchant ID
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->getValue(self::KEY_MERCHANT_ID, $this->getCurrentStore());
    }

    /**
     * Get teh laybuy API key (secret)
     *
     * @return string
     */
    public function getApiKey()
    {
        $value = $this->getValue(self::KEY_API_KEY, $this->getCurrentStore());
        return $value ? $this->encryptor->decrypt($value) : $value;
    }


    /**
     * Gey Payment action
     *
     * @return mixed
     */
    public function getPaymentAction()
    {
        return $this->getValue(self::PAYMENT_ACTION, $this->getCurrentStore());
    }

    /**
     * Get minimum order total
     *
     * @return mixed
     */
    public function getMinOrderTotal()
    {
        return $this->getValue(self::KEY_MIN_ORDER_TOTAL, $this->getCurrentStore());
    }

    /**
     * Get max order total
     *
     * @return mixed
     */
    public function getMaxOrderTotal()
    {
        return $this->getValue(self::KEY_MAX_ORDER_TOTAL, $this->getCurrentStore());
    }

    /**
     * Get use sanbox flag
     *
     * @return string
     */
    public function getUseSandbox()
    {
        return $this->getValue(self::USE_SANDBOX, $this->getCurrentStore());
    }

    /**
     * @return int
     */
    public function setCurrentStore($storeId)
    {
        $this->currentStore = $storeId;
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCurrentStore()
    {
        if (!$this->currentStore) {
            $this->currentStore = $this->storeManager->getStore()->getId();
        }

        return $this->currentStore;
    }

    /**
     * @param $storeId
     * @return mixed|null
     */
    public function enableEmailReport($storeId)
    {
        return $this->getValue(self::ENABLE_CRON_EMAIL_REPORT, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed|null
     */
    public function getPaymentErrorEmail($storeId)
    {
        return $this->getValue(self::PAYMENT_ERROR_EMAIl, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed|null
     */
    public function getEmailTemplate($storeId)
    {
        return $this->getValue(self::EMAIL_TEMPLATE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed|null
     */
    public function getEmailIdentity($storeId)
    {
        return $this->getValue(self::EMAIL_IDENTITY, $storeId);
    }
}
