<?php

namespace Laybuy\Laybuy\Gateway\Http;

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
     * @var null|\Zend_Http_Client
     */
    protected static $_httpClient = null;

    /**
     * Flag indicating the Zend_Http_Client is fresh and needs no reset.
     * Must be set explicitly if you want to keep preset parameters.
     * @var bool true if you do not want a reset. Default false.
     */
    protected $_noReset = false;

    public function __construct($uri = null)
    {
        if(!empty($uri)) {
            $this->setUri($uri);
        }
    }

    /**
     * @param $uri
     * @return $this
     * @throws \Zend_Uri_Exception
     */
    public function setUri($uri)
    {
        if ($uri instanceof \Zend_Uri_Http) {
            $this->_uri = $uri;
        } else {
            $this->_uri = \Zend_Uri::factory($uri);
        }

        return $this;
    }

    /**
     * @param \Zend_Http_Client $httpClient
     */
    final public static function setHttpClient(\Zend_Http_Client $httpClient)
    {
        self::$_httpClient = $httpClient;
    }

    /**
     * Gets the HTTP client object.
     *
     * @return \Zend_Http_Client
     */
    final public static function getHttpClient()
    {
        if (!self::$_httpClient instanceof \Zend_Http_Client) {
            self::$_httpClient = new \Zend_Http_Client();
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
     * @throws \Zend_Http_Client_Exception
     * @throws \Zend_Uri_Exception
     */
    private function _prepareRest($path)
    {
        // Get the URI object and configure it
        if (!$this->_uri instanceof \Zend_Uri_Http) {
            throw new \Exception('URI object must be set before performing call');
        }

        $uri = $this->_uri->getUri();

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
     * @return \Zend_Http_Response
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     * @throws \Zend_Uri_Exception
     */
    public function restGet($path, array $query = null)
    {
        $this->_prepareRest($path);
        $client = self::getHttpClient();
        $client->setParameterGet($query);
        return $client->request('GET');
    }


    /**
     * @param $method
     * @param null $data
     * @return \Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
     */
    protected function _performPost($method, $data = null)
    {
        $client = self::getHttpClient();
        if (is_string($data)) {
            $client->setRawData($data);
        } elseif (is_array($data) || is_object($data)) {
            $client->setParameterPost((array) $data);
        }
        return $client->request($method);
    }

    /**
     * @param $path
     * @param null $data
     * @return \Zend_Http_Response
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     * @throws \Zend_Uri_Exception
     */
    public function restPost($path, $data = null)
    {
        $this->_prepareRest($path);
        return $this->_performPost('POST', $data);
    }

    /**
     * @param $path
     * @param null $data
     * @return \Zend_Http_Response
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     * @throws \Zend_Uri_Exception
     */
    public function restPut($path, $data = null)
    {
        $this->_prepareRest($path);
        return $this->_performPost('PUT', $data);
    }

    /**
     * @param $path
     * @param null $data
     * @return \Zend_Http_Response
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     * @throws \Zend_Uri_Exception
     */
    public function restDelete($path, $data = null)
    {
        $this->_prepareRest($path);
        return $this->_performPost('DELETE', $data);
    }
}