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
namespace Unbxd\ProductFeed\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Unbxd\ProductFeed\Observer\AbstractObserver;

/**
 * Class IndexingQueueListingNotice
 * @package Unbxd\ProductFeed\Observer
 */
class IndexingQueueListingNotice extends AbstractObserver implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->helperData->isAuthorizationCredentialsSetup()) {
            $this->messageManager->addWarningMessage($this->getAuthorizationCredentialsAreNotSetupMessage());
        }

        if (!$this->helperData->isIndexingQueueEnabled()) {
            $this->messageManager->addWarningMessage($this->getIndexingQueueIsDisabledMessage());
        }

        if (!$this->helperData->isCronConfigured()) {
            $this->messageManager->addWarningMessage($this->getCronIsNotConfiguredMessage());
        }

        return $this;
    }
}
