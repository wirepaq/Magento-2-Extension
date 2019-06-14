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
use Unbxd\ProductFeed\Model\CronManager;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\Action\Full as ReindexAction;
use Unbxd\ProductFeed\Model\Feed\Manager as FeedManager;
use Unbxd\ProductFeed\Model\Feed\Api\ConnectorFactory;
use Unbxd\ProductFeed\Model\Feed\Api\Connector as ApiConnector;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var CronManager
     */
    protected $cronManager;

    /**
     * @var ReindexAction
     */
    protected $reindexAction;

    /**
     * @var FeedManager
     */
    protected $feedManager;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ConnectorFactory
     */
    private $connectorFactory;

    /**
     * Local cache for feed API connector manager
     *
     * @var null
     */
    private $connectorManager = null;

    /**
     * AbstractFeed constructor.
     * @param AppState $state
     * @param FeedHelper $feedHelper
     * @param ProductHelper $productHelper
     * @param CronManager $cronManager
     * @param ReindexAction $reindexAction
     * @param FeedManager $feedManager
     * @param StoreManagerInterface $storeManager
     * @param ConnectorFactory $connectorFactory
     */
    public function __construct(
        AppState $state,
        FeedHelper $feedHelper,
        ProductHelper $productHelper,
        CronManager $cronManager,
        ReindexAction $reindexAction,
        FeedManager $feedManager,
        StoreManagerInterface $storeManager,
        ConnectorFactory $connectorFactory
    ) {
        parent::__construct();
        $this->appState = $state;
        $this->feedHelper = $feedHelper;
        $this->productHelper = $productHelper;
        $this->cronManager = $cronManager;
        $this->reindexAction = $reindexAction;
        $this->feedManager = $feedManager;
        $this->storeManager = $storeManager;
        $this->connectorFactory = $connectorFactory;
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
     * Retrieve connector manager instance. Init if needed
     *
     * @return ApiConnector|null
     */
    public function getConnectorManager()
    {
        if (null == $this->connectorManager) {
            /** @var ApiConnector */
            $this->connectorManager = $this->connectorFactory->create();
        }

        return $this->connectorManager;
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
    protected function getStoreNameById($storeId = '')
    {
        return $this->storeManager->getStore($storeId)->getName();
    }
}