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
namespace Unbxd\ProductFeed\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;

/**
 * Class UpgradeData
 * @package Unbxd\ProductFeed\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * Core config path/value pairs related to feed process
     */
    const FEED_PATH_FULL_STATE_FLAG = 'unbxd_catalog/feed/full_state_flag'; // is full catalog was sync or not
    const FEED_PATH_INCREMENTAL_STATE_FLAG = 'unbxd_catalog/feed/incremental_state_flag'; // is separate product was sync or not
    const FEED_PATH_FULL_LOCK_FLAG = 'unbxd_catalog/feed/full_lock_flag'; // flag to prevent duplicate full catalog sync process
    const FEED_PATH_FULL_LOCK_TIME = 'unbxd_catalog/feed/full_lock_time'; // full catalog sync lock time
    const FEED_PATH_LAST_OPERATION_TYPE = 'unbxd_catalog/feed/last_operation_type'; // full or incremental
    const FEED_PATH_LAST_DATETIME = 'unbxd_catalog/feed/last_datetime'; // last sync datetime
    const FEED_PATH_LAST_STATUS = 'unbxd_catalog/feed/last_status'; // last sync status

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Default configuration core config data fields
     *
     * @var array
     */
    private $defaultConfigData = [
        self::FEED_PATH_FULL_STATE_FLAG => 0,
        self::FEED_PATH_INCREMENTAL_STATE_FLAG => 0,
        self::FEED_PATH_FULL_STATE_FLAG => 0,
        self::FEED_PATH_FULL_LOCK_TIME => 0,
        self::FEED_PATH_LAST_OPERATION_TYPE => null,
        self::FEED_PATH_LAST_DATETIME => null,
        self::FEED_PATH_LAST_STATUS => null,
    ];

    /**
     * UpgradeData constructor.
     * @param StoreManagerInterface $storeManager
     * @param ConfigInterface $config
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigInterface $config
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getDefaultConfigData()
    {
        return $this->defaultConfigData;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $select = $setup->getConnection()->select()->from(
            $setup->getTable('core_config_data'),
            ['path']
        )->where(
            'path LIKE ?',
            '%unbxd_catalog/feed/%'
        );

        $alreadyInserted = $setup->getConnection()->fetchAll($select);

        foreach ($this->defaultConfigData as $path => $value) {
            if (isset($alreadyInserted[$path])) {
                continue;
            }

            $this->config->saveConfig($path, $value);
        }

        $setup->endSetup();
    }

    /**
     * @return array
     */
    private function getWebsiteIds()
    {
        /** @var \Magento\Store\Api\Data\WebsiteInterface[] $websites */
        $websites = $this->storeManager->getWebsites();

        $websiteIds = [];
        foreach ($websites as $website) {
            array_push($websiteIds, $website->getId());
        }

        return $websiteIds;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface[]
     */
    private function getStores()
    {
        return $this->storeManager->getStores();
    }
}