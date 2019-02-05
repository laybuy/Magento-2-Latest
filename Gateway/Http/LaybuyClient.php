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
     * @var \Zend_Rest_Client
     */
    public $restClient;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * LaybuyClient constructor.
     *
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        Logger $logger,
        Config $config
    ) {
        $this->logger = $logger;
        $this->setupLaybuyClient($config);
    }

    /**
     *
     * @param $laybuyOrder
     * @return bool
     */
    public function getRedirectUrl($laybuyOrder)
    {
        if (!$this->restClient) {
            return false;
        }

        $response = $this->restClient->restPost(Config::API_ORDER_CREATE, json_encode($laybuyOrder));
        $body = json_decode($response->getBody());
        $this->logger->debug([__METHOD__ => $body]);

        if ($body->result == Config::LAYBUY_SUCCESS) {
            if (!$body->paymentUrl) {
                return false;
            }

            return $body->paymentUrl;
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

        $response = $this->restClient->restPost(Config::API_ORDER_CONFIRM, json_encode(['token' => $token]));
        $body = json_decode($response->getBody());

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
        $response = $this->restClient->restGet(Config::API_ORDER_CANCEL . '/' . $token);
        $body = json_decode($response->getBody());

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
            $this->restClient = new \Zend_Rest_Client($this->endpoint);
            $this->restClient->getHttpClient()->setAuth($this->merchantId, $this->apiKey,
                \Zend_Http_Client::AUTH_BASIC);
        } catch (\Exception $e) {
            $this->restClient = false;
        }
    }

    /**
     * @param array $refundDetails
     * @return integer
     * @throws LocalizedException
     */
    public function refundLaybuyOrder($refundDetails)
    {
        $response = $this->restClient->restPost(Config::API_ORDER_REFUND, json_encode($refundDetails));

        $body = json_decode($response->getBody());

        if (!$body->result === Config::LAYBUY_SUCCESS) {
            $this->logger->debug(['Error while processing refund: ' . $response->getBody()]);
            throw new LocalizedException(__('Unable to process refund.'));
        }

        return $body->refundId;
    }
}
