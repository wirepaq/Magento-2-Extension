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
namespace Unbxd\ProductFeed\Controller\Adminhtml\Feed;

use Unbxd\ProductFeed\Controller\Adminhtml\Index;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Full
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed
 */
class Full extends Index
{
    /**
     * @return array|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $responseContent = [];
        $responseContent = $this->_isValidPostRequest();
        if (!empty($responseContent)) {
            $resultJson->setData($responseContent);
            return $resultJson;
        }

        // @TODO - implement


        return $responseContent;
    }
}