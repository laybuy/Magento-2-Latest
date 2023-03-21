<?php

namespace Laybuy\Laybuy\Gateway\Http;

use Magento\Framework\HTTP\Client\Curl;
use Laminas\Http\Client;
use Laminas\Uri\Http;

/**
 * Class RestClient
 * @package Laybuy\Laybuy\Gateway\Http
 */
class RestClient
{
    /**
     * @var array
     */
    protected $_data = [];

    /**
     * @var null|string
     */
    protected $_uri = null;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var
     */
    protected $auth;

    protected static $_httpClient = null;

    protected $_noReset = false;

    /**
     * @param Curl $curl
     * @param $uri
     */
    public function __construct(Curl $curl, $uri = null)
    {
        $this->curl = $curl;
        if (!empty($uri)) {
            $this->setUri($uri);
        }
    }

    /**
     * @param $user
     * @param $pass
     * @return void
     */
    public function setAuth($user, $pass)
    {
        $this->curl->setCredentials($user, $pass);
    }

    /**
     * @param $uri
     * @return $this
     */
    public function setUri($uri)
    {
        if ($uri instanceof Http) {
            $this->_uri = $uri;
        } else {
            $this->_uri = new Http($uri);
        }
        return $this;
    }

    /**
     * @param Client $httpClient
     */
    final public static function setHttpClient(\Laminas\Http\Client $httpClient)
    {
        self::$_httpClient = $httpClient;
    }

    /**
     * Gets the HTTP client object.
     *
     * @return Client
     */
    final public static function getHttpClient()
    {
        if (!self::$_httpClient instanceof Client) {
            self::$_httpClient = new Client();
        }

        return self::$_httpClient;
    }

    /**
     * @return null|string
     */
    public function getUri()
    {
        return $this->_uri;
    }

    /**
     * @param $path
     * @throws \Exception
     */
    private function _prepareRest($path)
    {
        // Get the URI object and configure it
        if (!$this->_uri instanceof Http) {
            throw new \Exception('URI object must be set before performing call');
        }

        $uri = $this->_uri->toString();

        if ($path[0] != '/' && $uri[strlen($uri)-1] != '/') {
            $path = '/' . $path;
        }
        $this->_uri->setPath($path);
        /**
         * Get the HTTP client and configure it for the endpoint URI.  Do this each time
         * because the Zend_Http_Client instance is shared among all Zend_Service_Abstract subclasses.
         */
        if ($this->_noReset) {
            // if $_noReset we do not want to reset on this request,
            // but we do on any subsequent request
            $this->_noReset = false;
        } else {
            self::getHttpClient()->resetParameters();
        }

        self::getHttpClient()->setUri($this->_uri);
    }

    /**
     * @param bool $bool
     */
    public function setNoReset($bool = true)
    {
        $this->_noReset = $bool;
    }

    /**
     * @param $path
     * @param array|null $query
     * @return mixed
     */
    public function restGet($path, array $query = null)
    {
        $this->_prepareRest($path);
        $endPoint = $this->_uri->toString();
        $this->curl->addHeader("accept", "application/json");
        $this->curl->get($endPoint);
        $result = $this->curl->getBody();
        return json_decode($result);
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function _performPost($data = null)
    {
        $endPoint = $this->_uri->toString();
        $this->curl->addHeader("accept","application/json");
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->post($endPoint, $data);
        $result = $this->curl->getBody();
        return json_decode($result);
    }

    /**
     * @param $path
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function restPost($path, $data = null)
    {
        $this->_prepareRest($path);
        return $this->_performPost($data);
    }
}
