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
namespace Unbxd\ProductFeed\Console\Command\Feed;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Helper\ProductHelper;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\Action\Full as ReindexAction;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\CronManager;
use Unbxd\ProductFeed\Model\CronManagerFactory;
use Unbxd\ProductFeed\Model\CacheManager;
use Unbxd\ProductFeed\Model\CacheManagerFactory;
use Unbxd\ProductFeed\Model\Feed\Manager as FeedManager;
use Unbxd\ProductFeed\Model\Feed\ManagerFactory as FeedManagerFactory;
use Unbxd\ProductFeed\Model\Feed\Api\ConnectorFactory;
use Unbxd\ProductFeed\Model\Feed\Api\Connector as ApiConnector;

/**
 * Class AbstractCommand
 * @package Unbxd\ProductFeed\Console\Command\Feed
 */
abstract class AbstractCommand extends Command
{
    /**
     * Store input option key
     */
    const STORE_INPUT_OPTION_KEY = 'store';

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var FeedHelper
     */
    protected $feedHelper;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var ReindexAction
     */
    protected $reindexAction;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CacheManagerFactory
     */
    protected $cacheManagerFactory;

    /**
     * Local cache for cache manager
     *
     * @var null|CacheManager
     */
    private $cacheManager = null;

    /**
     * @var CronManagerFactory
     */
    protected $cronManagerFactory;

    /**
     * Local cache for cron manager
     *
     * @var null|CronManager
     */
    private $cronManager = null;

    /**
     * @var FeedManagerFactory
     */
    protected $feedManagerFactory;

    /**
     * Local cache for feed manager
     *
     * @var null|FeedManager
     */
    protected $feedManager = null;

    /**
     * @var ConnectorFactory
     */
    private $connectorFactory;

    /**
     * Local cache for feed API connector manager
     *
     * @var null|ApiConnector
     */
    private $connectorManager = null;

    /**
     * AbstractCommand constructor.
     * @param AppState $state
     * @param FeedHelper $feedHelper
     * @param ProductHelper $productHelper
     * @param ReindexAction $reindexAction
     * @param StoreManagerInterface $storeManager
     * @param CacheManagerFactory $cacheManagerFactory
     * @param CronManagerFactory $cronManagerFactory
     * @param FeedManagerFactory $feedManagerFactory
     * @param ConnectorFactory $connectorFactory
     */
    public function __construct(
        AppState $state,
        FeedHelper $feedHelper,
        ProductHelper $productHelper,
        ReindexAction $reindexAction,
        StoreManagerInterface $storeManager,
        CacheManagerFactory $cacheManagerFactory,
        CronManagerFactory $cronManagerFactory,
        FeedManagerFactory $feedManagerFactory,
        ConnectorFactory $connectorFactory
    ) {
        $this->appState = $state;
        $this->feedHelper = $feedHelper;
        $this->productHelper = $productHelper;
        $this->reindexAction = $reindexAction;
        $this->storeManager = $storeManager;
        $this->cacheManagerFactory = $cacheManagerFactory;
        $this->cronManagerFactory = $cronManagerFactory;
        $this->feedManagerFactory = $feedManagerFactory;
        $this->connectorFactory = $connectorFactory;
        parent::__construct();
    }

    /**
     * @param OutputInterface $output
     * @return $this
     */
    abstract protected function preProcessActions($output);

    /**
     * @param OutputInterface $output
     * @return $this
     */
    abstract protected function postProcessActions($output);

    /**
     * Retrieve cache manager instance. Init if needed
     *
     * @return CacheManager|null
     */
    public function getCacheManager()
    {
        if (null === $this->cacheManager) {
            /** @var CacheManager */
            $this->cacheManager = $this->cacheManagerFactory->create();
        }

        return $this->cacheManager;
    }

    /**
     * Retrieve cron manager instance. Init if needed
     *
     * @return CronManager|null
     */
    public function getCronManager()
    {
        if (null === $this->cronManager) {
            /** @var CronManager */
            $this->cronManager = $this->cronManagerFactory->create();
        }

        return $this->cronManager;
    }

    /**
     * Retrieve feed manager instance. Init if needed
     *
     * @return FeedManager|null
     */
    public function getFeedManager()
    {
        if (null === $this->feedManager) {
            /** @var FeedManager */
            $this->feedManager = $this->feedManagerFactory->create();
        }

        return $this->feedManager;
    }

    /**
     * Retrieve connector manager instance. Init if needed
     *
     * @return ApiConnector|null
     */
    public function getConnectorManager()
    {
        if (null === $this->connectorManager) {
            /** @var ApiConnector */
            $this->connectorManager = $this->connectorFactory->create();
        }

        return $this->connectorManager;
    }

    /**
     * Clean configuration cache.
     * In some cases related config info doesn't refreshing on backend frontend
     *
     * @return $this
     */
    protected function flushSystemConfigCache()
    {
        try {
            $this->getCacheManager()->flushSystemConfigCache();
        } catch (\Exception $e) {
            return $this;
        }

        return $this;
    }

    /**
     * @param $storeCode
     * @param \Magento\Store\Api\Data\StoreInterface[] $stores
     * @return int
     */
    protected function getStoreIdByCode($storeCode, $stores)
    {
        foreach ($stores as $store) {
            if ($store->getCode() == $storeCode) {
                return $store->getId();
            }
        }

        return Store::DEFAULT_STORE_ID;
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getDefaultStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @param string $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStoreNameById($storeId = null)
    {
        return $this->storeManager->getStore($storeId)->getName();
    }
}