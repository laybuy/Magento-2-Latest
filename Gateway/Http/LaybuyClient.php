<?php

namespace Laybuy\Laybuy\Gateway\Http;

use Laybuy\Laybuy\Model\Config;
use Laybuy\Laybuy\Model\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class LaybuyClient
 * @package Laybuy\Laybuy\Gateway\Http
 */
class LaybuyClient
{
    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var integer|string
     */
    private $merchantId;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var RestClient
     */
    public $restClient;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Logger $logger
     * @param Config $config
     * @param RestClient $restClient
     */
    public function __construct(
        Logger $logger,
        Config $config,
        RestClient $restClient
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->restClient = $restClient;
        $this->setupLaybuyClient($config);
    }

    /**
     *
     * @param $laybuyOrder
     * @return bool
     */
    public function getRedirectUrlAndToken($laybuyOrder)
    {
        if (!$this->restClient) {
            return false;
        }

        $body = $this->restClient->restPost(Config::API_ORDER_CREATE, json_encode($laybuyOrder));
        $returnData = [];
        $this->logger->debug([__METHOD__ => $body]);

        if ($body->result == Config::LAYBUY_SUCCESS) {
            if (!$body->paymentUrl || !$body->token) {
                return false;
            }
            $returnData['redirectUrl'] = $body->paymentUrl;
            $returnData['token'] = $body->token;

            return $returnData;
        }

        return false;
    }

    /**
     * @param $token
     * @return bool
     */
    public function getLaybuyConfirmationOrderId($token)
    {
        if (!$this->restClient) {
            return false;
        }

        $body = $this->restClient->restPost(Config::API_ORDER_CONFIRM, json_encode(['token' => $token]));

        $this->logger->debug(['method' => __METHOD__, $token => $body]);

        if ($body->result == Config::LAYBUY_SUCCESS) {
            if (!$body->orderId) {
                return false;
            }

            return $body->orderId;
        }

        return false;
    }

    /**
     * @param $token
     * @return bool
     */
    public function cancelLaybuyOrder($token)
    {
        $body = $this->restClient->restGet(Config::API_ORDER_CANCEL . '/' . $token);

        if ($body->result == Config::LAYBUY_SUCCESS) {
            return true;
        }

        return false;
    }

    /**
     * @param Config $config
     * @return bool
     */
    protected function setupLaybuyClient(Config $config)
    {
        if (!$config->getMerchantId() || !$config->getApiKey()) {
            return false;
        }

        if ($config->getUseSandbox()) {
            $this->endpoint = $config::API_ENDPOINT_SANDBOX;
        } else {
            $this->endpoint = $config::API_ENDPOINT_LIVE;
        }

        $this->merchantId = $config->getMerchantId();
        $this->apiKey = $config->getApiKey();

        try {
            $this->restClient->setUri($this->endpoint);
            $this->restClient->setAuth($this->merchantId, $this->apiKey);
        } catch (\Exception $e) {
            $this->restClient = false;
        }
    }

    /**
     * @param array $refundDetails
     * @return integer
     * @throws LocalizedException
     */
    public function refundLaybuyOrder($refundDetails, $storeId)
    {
        $this->config->setCurrentStore($storeId);
        $this->setupLaybuyClient($this->config);

        $body = $this->restClient->restPost(Config::API_ORDER_REFUND, json_encode($refundDetails));

        $this->logger->debug([
            'Refund Response:' => $body,
            'Store ID:' => $storeId
        ]);

        if ($body->result === Config::LAYBUY_FAILURE) {
            $this->logger->debug(['Error while processing refund: ' . json_encode($body)]);
            throw new LocalizedException(__('Unable to process refund.'));
        }

        return $body->refundId;
    }

     /**
     * @param $token
     * @return array
     */
    public function checkMerchantOrder($merchantReference)
    {
        if (!$this->restClient) {
            return false;
        }

        $body = $this->restClient->restGet(Config::API_ORDER_CHECK . '/' . $merchantReference);

        $this->logger->debug(['method' => __METHOD__, 'Response' => $body]);

        if ($body->result == Config::LAYBUY_SUCCESS) {
            return $body;
        }

        return false;
    }
}
