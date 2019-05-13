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
namespace Unbxd\ProductFeed\Helper;

use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Setup\UpgradeData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Feed
 * @package Unbxd\ProductFeed\Helper
 */
class Feed extends HelperData
{
    /**
     * Synchronization status
     */
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    /**
     * @return bool
     */
    public function isFullCatalogSynchronized()
    {
        return (bool) $this->getConfigValue(UpgradeData::FEED_PATH_FULL_STATE_FLAG);
    }

    /**
     * @return bool
     */
    public function isIncrementalProductSynchronized()
    {
        return (bool) $this->getConfigValue(UpgradeData::FEED_PATH_INCREMENTAL_STATE_FLAG);
    }

    /**
     * @return bool
     */
    public function isFullSynchronizationLocked()
    {
        return (bool) $this->getConfigValue(UpgradeData::FEED_PATH_FULL_LOCK_FLAG);
    }

    /**
     * @return string
     */
    public function getFullSynchronizationLockedTime()
    {
        return $this->getConfigValue(UpgradeData::FEED_PATH_FULL_LOCK_TIME);
    }

    /**
     * @return string
     */
    public function getLastSynchronizationOperationType()
    {
        return $this->getConfigValue(UpgradeData::FEED_PATH_LAST_OPERATION_TYPE);
    }

    /**
     * @return string
     */
    public function getLastSynchronizationDatetime()
    {
        return $this->getConfigValue(UpgradeData::FEED_PATH_LAST_DATETIME);
    }

    /**
     * @return string
     */
    public function getLastSynchronizationStatus()
    {
        return $this->getConfigValue(UpgradeData::FEED_PATH_LAST_STATUS);
    }

    /**
     * @param $status
     * @return $this
     */
    public function setFullCatalogSynchronizedStatus($status)
    {
        $this->updateConfigValue(UpgradeData::FEED_PATH_FULL_STATE_FLAG, $status);

        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setIncrementalProductSynchronizedStatus($status)
    {
        $this->updateConfigValue(UpgradeData::FEED_PATH_INCREMENTAL_STATE_FLAG, $status);

        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setFullSynchronizationLocked($status)
    {
        $this->updateConfigValue(UpgradeData::FEED_PATH_FULL_LOCK_FLAG, $status);

        return $this;
    }

    /**
     * @param $time
     * @return $this
     */
    public function setFullSynchronizationLockedTime($time)
    {
        $this->updateConfigValue(UpgradeData::FEED_PATH_FULL_LOCK_TIME, $time);

        return $this;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setLastSynchronizationOperationType($type)
    {
        $this->updateConfigValue(UpgradeData::FEED_PATH_LAST_OPERATION_TYPE, $type);

        return $this;
    }

    /**
     * @param $datetime
     * @return $this
     */
    public function setLastSynchronizationDatetime($datetime)
    {
        $this->updateConfigValue(UpgradeData::FEED_PATH_LAST_DATETIME, $datetime);

        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setLastSynchronizationStatus($status)
    {
        $this->updateConfigValue(UpgradeData::FEED_PATH_LAST_STATUS, $status);

        return $this;
    }
}