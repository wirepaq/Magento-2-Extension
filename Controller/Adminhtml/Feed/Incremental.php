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

use Unbxd\ProductFeed\Controller\Adminhtml\FeedActionIndex;
use Magento\Framework\Controller\ResultFactory;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;

/**
 * Class Incremental
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed
 */
class Incremental extends FeedActionIndex
{
    /**
     * @return array|bool|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $responseContent = [];
        $isValid = $this->isValidPostRequest();
        if (!$isValid) {
            $responseContent = [
                'errors' => true,
                'message' => __('Invalid request.')
            ];
            $resultJson->setData($responseContent);
            return $resultJson;
        }

        $productIds = trim($this->getRequest()->getParam('ids'));
        if ($productIds) {
            $productIds = explode(',', $productIds);
            if (!empty($productIds)) {
                // try to collect child product ids if any
                $this->collectAllProductIds($productIds);
                $storeId = $this->getStore()->getId();
                try {
                    $index = $this->reindexAction->rebuildProductStoreIndex($storeId, $productIds);
                } catch (\Exception $e) {
                    $responseContent = [
                        'errors' => true,
                        'message' => __('Indexing error: %s', $e->getMessage())
                    ];
                    $resultJson->setData($responseContent);
                    return $resultJson;
                }

                try {
                    $this->feedManager->execute($index, FeedConfig::FEED_TYPE_INCREMENTAL);
                } catch (\Exception $e) {
                    $responseContent = [
                        'errors' => true,
                        'message' => __(__('Feed execution error: %s', $e->getMessage()))
                    ];
                    $resultJson->setData($responseContent);
                    return $resultJson;
                }
            }
        }

        if ($this->feedHelper->isLastSynchronizationSuccess()) {
            $this->messageManager->addErrorMessage(__('Related product(s) were synchronized successfully.'));
        } else {
            $this->messageManager->addErrorMessage(__('Synchronization failed. See feed view log for additional information.'));
        }

        $resultJson->setData($responseContent);
        return $resultJson;
    }
}