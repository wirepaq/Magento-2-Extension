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
namespace Magento\Catalog\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;

/**
 * Class UpgradeData
 * @package Magento\Catalog\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Default configuration data fields
     *
     * @var array
     */
    private $defaultConfigData = [
        'unbxd_catalog/feed/full_state_flag' => 0,
        'unbxd_catalog/feed/incremental_state_flag' => 0, // @TODO - not sure if this needed
        'unbxd_catalog/feed/full_lock_flag' => 0,
        'unbxd_catalog/feed/full_lock_time' => '',
        'unbxd_catalog/feed/full_last_datetime' => '',
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

        // @TODO - implement

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