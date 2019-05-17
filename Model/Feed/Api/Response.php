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

use Magento\Framework\DataObject;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Model\Serializer;

/**
 * Class Response
 * @package Unbxd\ProductFeed\Model\Feed\Api
 */
class Response extends DataObject
{
    /**
     * HTTP RESPONSE CODES
     *
     * default success code
     */
    const HTTP_RESPONSE_CODE_SUCCESS_DEFAULT = '200';
    /**
     * on successful feed upload
     */
    const HTTP_RESPONSE_CODE_SUCCESS_FEED_UPLOAD = '201';
    /**
     * on bad request
     */
    const HTTP_RESPONSE_CODE_BAD_REQUEST = '400';
    /**
     * on authentication failures
     */
    const HTTP_RESPONSE_CODE_AUTHENTICATION_FAILURE = '401';
    /**
     * on internal server errors
     */
    const HTTP_RESPONSE_CODE_INTERNAL_SERVER_ERROR = '500';

    /**
     * API Response fields
     */
    const RESPONSE_FIELD_TIMESTAMP = 'timeStamp';
    const RESPONSE_FIELD_FILENAME = 'fileName';
    const RESPONSE_FIELD_UPLOAD_ID = 'uploadId';

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * Response from API request
     *
     * @var null
     */
    private $response = null;

    /**
     * Required response params on success API call
     *
     * @var array
     */
    private $requiredResponseParams = [
        self::RESPONSE_FIELD_TIMESTAMP,
        self::RESPONSE_FIELD_FILENAME,
        self::RESPONSE_FIELD_UPLOAD_ID
    ];

    /**
     * Response HTTP code form API request
     *
     * @var string
     */
    private $code = '';

    /**
     * Response body from API request
     *
     * @var string
     */
    private $body = '';

    /**
     * Response body from API request as array
     *
     * @var array
     */
    private $bodyAsArray = [];

    /**
     * Response message from API request
     *
     * @var string
     */
    private $message = '';

    /**
     * @var bool
     */
    private $isSuccess = false;

    /**
     * @var bool
     */
    private $isError = false;

    /**
     * Error recollected after each API call
     *
     * @var array
     */
    private $errors = [];

    /**
     * Response constructor.
     * @param HelperData $helperData
     * @param Serializer $serializer
     * @param array $data
     */
    public function __construct(
        HelperData $helperData,
        Serializer $serializer,
        array $data = []
    ) {
        parent::__construct($data);
        $this->helperData = $helperData;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Serializer::class);
    }

    /**
     * Creates a new \Zend_Http_Response object from a string.
     *
     * @param string $response
     * @return \Zend_Http_Response
     */
    public function createResponse($response)
    {
        return \Zend_Http_Response::fromString($response);
    }

    /**
     * Apply response data
     *
     * @param $response
     * @return $this
     */
    public function apply($response)
    {
        /** @var \Zend_Http_Response $resultResponse */
        $resultResponse = $this->createResponse($response);
        if ($resultResponse instanceof \Zend_Http_Response) {
            if ($code = $resultResponse->getStatus()) {
                $this->setResponseCode($code);
            }
            if ($body = $resultResponse->getBody()) {
                $this->setResponseBody($body);
            }
            if ($message = $resultResponse->getMessage()) {
                $this->setResponseMessage($message);
            }

            $this->setIsSuccess();
            $this->setIsError();

            if ($this->getIsSuccess()) {
                if (!$this->validateSuccessResponse()) {
                    // @TODO - throw exception or just log information about this?

                }
            }

            if ($this->getIsError()) {
                // error message maybe come from body?
                $this->setErrorMessageByCode();
            }
        }

        $this->setResponseData();

        return $this;
    }

    /**
     * Validate success response body data.
     *
     * @return bool
     */
    public function validateSuccessResponse()
    {
        $bodyData = $this->getResponseBodyAsArray();
        if (!empty($bodyData) && !empty($this->requiredResponseParams)) {
            foreach ($this->requiredResponseParams as $key => $value) {
                if (!array_key_exists($key, $bodyData)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return DataObject|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $code
     * @return void
     */
    public function setResponseCode($code)
    {
        $this->code = (string) $code;
    }

    /**
     * @return string
     */
    public function getResponseCode()
    {
        return $this->code;
    }

    /**
     * @param string $body
     * @return void
     */
    public function setResponseBody($body)
    {
        $this->body = (string) $body;
    }

    /**
     * @return string
     */
    public function getResponseBody()
    {
        return $this->body;
    }

    /**
     * @return array|bool|float|int|mixed|string|null
     */
    public function getResponseBodyAsArray()
    {
        if (empty($this->bodyAsArray)) {
            $body = $this->getResponseBody();
            if ($body && is_string($body) && (strlen($body) > 0)) {
                $this->bodyAsArray = $this->serializer->unserialize($body);
            }
        }

        return $this->bodyAsArray;
    }

    /**
     * @param $message
     * @return $this
     */
    public function setResponseMessage($message)
    {
        $this->message = (string) $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getResponseMessage()
    {
        return $this->message;
    }

    /**
     * @param null $flag
     * @return $this
     */
    public function setIsSuccess($flag = null)
    {
        if (null == $flag) {
            $flag = in_array($this->getResponseCode(), [
                self::HTTP_RESPONSE_CODE_SUCCESS_DEFAULT,
                self::HTTP_RESPONSE_CODE_SUCCESS_FEED_UPLOAD
            ]);
        }

        $this->isSuccess = (bool) $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsSuccess()
    {
        return $this->isSuccess;
    }

    /**
     * @param null $flag
     * @return $this
     */
    public function setIsError($flag = null)
    {
        if (null == $flag) {
            $flag = in_array($this->getResponseCode(), [
                self::HTTP_RESPONSE_CODE_BAD_REQUEST,
                self::HTTP_RESPONSE_CODE_AUTHENTICATION_FAILURE,
                self::HTTP_RESPONSE_CODE_INTERNAL_SERVER_ERROR,
            ]);
        }

        $this->isError = (bool) $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsError()
    {
        return $this->isError;
    }

    /**
     * @return $this
     */
    public function setErrorMessageByCode()
    {
        $code = $this->getResponseCode();
        $message = $this->getErrorMessageByCode($code);
        if ($message) {
            $this->setErrorMessage($code, $message);

            return $this;
        }

        $responseBody = $this->getResponseBodyAsArray();
        $errorMessage = array_key_exists('message', $responseBody) ? $responseBody['message'] : 'N/A';
        switch ($code) {
            case self::HTTP_RESPONSE_CODE_AUTHENTICATION_FAILURE:
                $message = __(
                    sprintf(
                        'API Response Error. Invalid Authorization Credentials. <br/> Code - %s. <br/> Message - %s.',
                        $code,
                        $errorMessage
                    )
                );
                break;
            case self::HTTP_RESPONSE_CODE_BAD_REQUEST:
                $message = __(
                    sprintf(
                        'API Response Error. Bad Request.<br/> Code - %s.<br/> Message - %s.',
                        $code,
                        $errorMessage
                    )
                );
                break;
            case self::HTTP_RESPONSE_CODE_INTERNAL_SERVER_ERROR:
                $message = __(
                    sprintf(
                        'API Response Error. Internal Server Error.<br/> Code - %s.<br/> Message - %s.',
                        $code,
                        $errorMessage
                    )
                );
                break;
            default:
                $message = __(
                    sprintf(
                        'API Response Error. Unexpected Error.<br/> Code - %s.<br/> Message - %s.',
                        $code,
                        $errorMessage
                    )
                );
                break;
        }

        $this->setErrorMessage($code, $message);

        return $this;
    }

    /**
     * @param array $errors
     * @return $this
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param $code
     * @param $message
     * @return $this
     */
    public function setErrorMessage($code, $message)
    {
        $this->errors[$code] = (string) $message;

        return $this;
    }

    /**
     * @param $code
     * @return mixed|null
     */
    public function getErrorMessageByCode($code)
    {
        return isset($this->errors[$code]) ? $this->errors[$code] : null;
    }

    /**
     * @return string
     */
    public function getErrorsAsString()
    {
        $errors = $this->getErrors();
        $errorsResult = [];
        if (!empty($errors)) {
            foreach ($errors as $code => $message) {
                $errorsResult[] = $message;
            }
        }

        return implode("\n", $errorsResult);
    }

    /**
     * @return $this
     */
    public function setResponseData()
    {
        $this->setData([
            'code' => $this->getResponseCode(),
            'body' => $this->getResponseBody(),
            'message' => $this->getResponseMessage(),
            'is_success' => $this->getIsSuccess(),
            'is_error' => $this->getIsError(),
            'errors' => $this->getErrors()
        ]);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getResponseData()
    {
        return $this->getData();
    }
}
