<?php
/**
 * Copyright (c) 2019 Unbxd Inc.
 */

/**
 * Init development:
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace Unbxd\ProductFeed\Model\Feed\Api;

use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Unbxd\ProductFeed\Model\Serializer;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Unbxd\ProductFeed\Model\Feed\Api\Response\Factory as ResponseFactory;

/**
 * Class Connector
 * @package Unbxd\ProductFeed\Model\Feed\Api
 */
class Connector
{
    /**
     * Content-Type HTTP header for json.
     */
    const CONTENT_TYPE_HEADER_JSON = "Content-Type: application/json";
    const CONTENT_TYPE_HEADER_MULTIPART = "Content-Type: multipart/form-data";

    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * API request headers
     *
     * @var array
     */
    private $headers = [
//        self::CONTENT_TYPE_HEADER_MULTIPART
    ];

    /**
     * API request params
     *
     * @var array
     */
    private $params = [];

    /**
     * API request method
     *
     * @var string
     */
    private $requestMethod = '';

    /**
     * API endpoint
     *
     * @var string
     */
    private $url = '';

    /**
     * API site key
     *
     * @var string
     */
    private $siteKey = '';

    /**
     * Local cache for file options
     *
     * @var array
     */
    protected $fileConfig = [];

    /**
     * @var bool
     */
    protected $isIncludeFileConfig = false;

    /**
     * @var \Unbxd\ProductFeed\Model\Feed\Api\Response
     */
    private $responseManager = null;

    /**
     * Connector constructor.
     * @param CurlFactory $curlFactory
     * @param ResponseFactory $responseFactory
     * @param EncryptorInterface $encryptor
     * @param HelperData $helperData
     * @param Serializer $serializer
     */
    public function __construct(
        CurlFactory $curlFactory,
        ResponseFactory $responseFactory,
        EncryptorInterface $encryptor,
        HelperData $helperData,
        Serializer $serializer
    ) {
        $this->curlFactory = $curlFactory;
        $this->responseFactory = $responseFactory;
        $this->encryptor = $encryptor;
        $this->helperData = $helperData;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Serializer::class);
    }

    /**
     * As for API Keys field in configuration we use type 'obscure' we need to decrypt value before use
     *
     * @param $value
     * @return string
     */
    private function getDecryptedKey($value)
    {
        return $this->encryptor->decrypt(trim($value));
    }

    /**
     * @param array $headers
     */
    private function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * @return array
     */
    private function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return bool
     */
    private function resetHeaders()
    {
        $this->setHeaders([]);

        return true;
    }

    /**
     * @param array $params
     */
    private function setParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * @return array
     */
    private function getParams()
    {
        return $this->params;
    }

    /**
     * @return bool
     */
    private function resetParams()
    {
        $this->setParams([]);

        return true;
    }

    /**
     * @param string $method
     */
    private function setRequestMethod($method = \Zend_Http_Client::POST)
    {
        $this->requestMethod = (string) $method;
    }

    /**
     * @return string
     */
    private function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * @param string $url
     */
    private function setApiUrl($url)
    {
        $this->url = (string) $url;
    }

    /**
     * @return string
     */
    private function getApiUrl()
    {
        return $this->url;
    }

    /**
     * @param string $siteKey
     */
    private function setSiteKey($siteKey)
    {
        $this->siteKey = (string) $siteKey;
    }

    /**
     * @return mixed
     */
    private function getSiteKey()
    {
        return $this->siteKey;
    }

    /**
     * @param array $config
     */
    public function setFileConfig(array $config)
    {
        $this->fileConfig = $config;
    }

    /**
     * @return array
     */
    public function getFileConfig()
    {
        return $this->fileConfig;
    }

    /**
     * @param $flag
     */
    private function setIsIncludeFileConfig($flag)
    {
        $this->isIncludeFileConfig = (bool) $flag;
    }

    /**
     * @return bool
     */
    private function getIsIncludeFileConfig()
    {
        return $this->isIncludeFileConfig;
    }

    /**
     * Prepare API authorization params for request
     *
     * @return bool
     */
    private function prepareAuthorizationParams()
    {
        $secretKey = $this->helperData->getSecretKey();
        $siteKey = $this->helperData->getSiteKey();
        if (!$secretKey || !$siteKey) {
            return false;
        }

        $this->setHeaders([
            "Authorization: {$secretKey}"
        ]);

        $this->setSiteKey($siteKey);

        return true;
    }

    /**
     * Prepare API url for request
     *
     * @param string $feedType
     * @return bool
     */
    private function prepareApiUrl($feedType = FeedConfig::FEED_TYPE_FULL)
    {
        $apiEndpoint = $this->helperData->getFullFeedApiEndpoint();
        if ($feedType == FeedConfig::FEED_TYPE_INCREMENTAL) {
            $apiEndpoint = $this->helperData->getIncrementalFeedApiEndpoint();
        }

        if (!$siteKey = $this->getSiteKey()) {
            return false;
        }

        $this->setApiUrl(sprintf($apiEndpoint, $siteKey));

        return true;
    }

    /**
     * @param $config
     * @return bool
     */
    public function prepareFileConfig($config)
    {
        if (empty($config)) {
            return false;
        }

        $file = isset($config['name']) ? trim($config['name']) : null;
        $path = isset($config['path']) ? trim($config['path']) : null;
        $size = isset($config['size']) ? trim($config['size']) : 0;

        $result = [];
        $isValid = (bool) $file && (bool) $path && (bool) $size;
        if ($isValid) {
            $result = [
                CURLOPT_UPLOAD => true,
                CURLOPT_FILE => $file,
                CURLOPT_INFILE => $path,
                CURLOPT_INFILESIZE => $size
            ];
        }

        $this->setFileConfig($result);

        return true;
    }

    /**
     * Throw error exception
     *
     * @param $string
     * @throws \Exception
     */
    private function doError($string)
    {
        throw new \Exception($string);
    }

    /**
     * @param array $params
     * @param array $headers
     * @param string $method
     * @param string $feedType
     * @return $this
     * @throws \Exception
     */
    public function execute(
        $params = [],
        $headers = [],
        $method = \Zend_Http_Client::POST,
        $feedType = FeedConfig::FEED_TYPE_FULL
    ) {
        $this->buildRequest($params, $headers, $method, $feedType);
        $this->call();

        return $this;
    }

    /**
     * Build API request
     *
     * @param array $params
     * @param array $headers
     * @param string $method
     * @param string $feedType
     * @return $this
     * @throws \Exception
     */
    private function buildRequest(
        $params = [],
        $headers = [],
        $method = \Zend_Http_Client::POST,
        $feedType = FeedConfig::FEED_TYPE_FULL
    ) {
        if (!$this->prepareAuthorizationParams()) {
            $this->doError(__('Please provide API credentials to perform this operation.'));
        }

        if (!$this->prepareApiUrl($feedType)) {
            $this->doError(__('API url must be set up before using API calls.'));
        }

        $this->setParams($params);
        if (!$method || ($method != \Zend_Http_Client::POST) && !empty($this->getFileConfig())) {
            $this->resetParams();
            $this->setIsIncludeFileConfig(true);
        }
        $this->setHeaders($headers);
        $this->setRequestMethod($method);

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function call()
    {
        try {
            /** @var \Magento\Framework\HTTP\Adapter\Curl $curl */
            $curl = $this->curlFactory->create();
            if ($this->getIsIncludeFileConfig()) {
                $curl->setOptions($this->getFileConfig());
            }
            $body = $this->getParams();
            $curl->write(
                $this->getRequestMethod(),
                $this->getApiUrl(),
                '1.1',
                $this->getHeaders(),
                !empty($body) ? $this->serializer->serialize($body) : ''
            );

            $result = $curl->read();
            if ($curl->getErrno()) {
                $this->doError(sprintf(
                    'API service connection error #%s: %s',
                    $curl->getErrno(),
                    $curl->getError()
                ));
            }
            $this->getResponseManager()->apply($result);
            $curl->close();
        } catch (\Exception $e) {
            $this->doError(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * Retrieve response manager instance. Init if needed
     *
     * @return Response
     */
    private function getResponseManager()
    {
        if (null == $this->responseManager) {
            /** @var \Unbxd\ProductFeed\Model\Feed\Api\Response */
            $this->responseManager = $this->responseFactory->create();
        }

        return $this->responseManager;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->getResponseManager();
    }
}