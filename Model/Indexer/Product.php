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
namespace Unbxd\ProductFeed\Model\Indexer;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Search\Request\DimensionFactory;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\Action\Full as FullAction;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Message\ManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Product
 * @package Unbxd\ProductFeed\Model\Indexer
 */
class Product implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * Indexer ID in configuration
     */
    const INDEXER_ID = 'unbxd_products';

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var IndexerInterface
     */
    private $indexerHandler;

    /**
     * @var DimensionFactory
     */
    private $dimensionFactory;

    /**
     * @var FullAction
     */
    private $fullAction;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var ConsoleOutput
     */
    private $consoleOutput;

    /**
     * Product constructor.
     * @param IndexerRegistry $indexerRegistry
     * @param IndexerInterface $indexerHandler
     * @param DimensionFactory $dimensionFactory
     * @param FullAction $fullAction
     * @param StoreManagerInterface $storeManager
     * @param ManagerInterface $messageManager
     * @param ConsoleOutput $consoleOutput
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        IndexerInterface $indexerHandler,
        DimensionFactory $dimensionFactory,
        FullAction $fullAction,
        StoreManagerInterface $storeManager,
        ManagerInterface $messageManager,
        ConsoleOutput $consoleOutput
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->indexerHandler = $indexerHandler;
        $this->dimensionFactory = $dimensionFactory;
        $this->fullAction = $fullAction;
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->consoleOutput = $consoleOutput;
    }

    /**
     * Execute materialization on ids entities
     * Used by mview, allows process indexer in the "Update on schedule" mode
     *
     * @param int[] $ids
     * @throws \Exception
     */
    public function execute($ids)
    {
        $storeIds = array_keys($this->storeManager->getStores());

        foreach ($storeIds as $storeId) {
            $dimension = $this->dimensionFactory->create(['name' => 'scope', 'value' => $storeId]);
            $this->indexerHandler->deleteIndex([$dimension], new \ArrayObject($ids));
            $this->indexerHandler->saveIndex([$dimension], $this->fullAction->rebuildStoreIndex($storeId, $ids));
        }
    }

    /**
     * Execute full indexation
     * Will take all of the data and reindex
     * Will run when reindex via command line
     *
     * @throws \Exception
     */
    public function executeFull()
    {
        $this->execute(null);

        $storeIds = array_keys($this->storeManager->getStores());

        foreach ($storeIds as $storeId) {
            $dimension = $this->dimensionFactory->create(['name' => 'scope', 'value' => $storeId]);
            $this->indexerHandler->cleanIndex([$dimension]);
            $this->indexerHandler->saveIndex([$dimension], $this->fullAction->rebuildStoreIndex($storeId));
        }
    }

    /**
     * Execute partial indexation by ID list
     * Works with a set of entity changed (may be massaction)
     *
     * @param array $ids
     * @throws \Exception
     */
    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    /**
     * Execute partial indexation by ID
     * Works in runtime for a single entity using plugins
     *
     * @param int $id
     * @throws \Exception
     */
    public function executeRow($id)
    {
        $this->execute([$id]);
    }
}