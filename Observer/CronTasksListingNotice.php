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
 * Class CronTasksListingNotice
 * @package Unbxd\ProductFeed\Observer
 */
class CronTasksListingNotice extends AbstractObserver implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->helperData->isCronConfigured()) {
            $this->messageManager->addWarningMessage($this->getCronIsNotConfiguredMessage());
        }

        return $this;
    }
}
