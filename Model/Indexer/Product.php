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
use Magento\Framework\Search\Request\DimensionFactory;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\Action\Full as FullAction;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Unbxd\ProductFeed\Model\IndexingQueue\Handler as QueueHandler;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Logger\OptionsListConstants;
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
     * @var DimensionFactory
     */
    private $dimensionFactory;

    /**
     * @var FullAction
     */
    private $fullAction;

    /**
     * @var QueueHandler
     */
    private $queueHandler;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * @param DimensionFactory $dimensionFactory
     * @param FullAction $fullAction
     * @param QueueHandler $queueHandler
     * @param HelperData $helperData
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param ManagerInterface $messageManager
     * @param ConsoleOutput $consoleOutput
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        DimensionFactory $dimensionFactory,
        FullAction $fullAction,
        QueueHandler $queueHandler,
        HelperData $helperData,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        ManagerInterface $messageManager,
        ConsoleOutput $consoleOutput
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->dimensionFactory = $dimensionFactory;
        $this->fullAction = $fullAction;
        $this->queueHandler = $queueHandler;
        $this->helperData = $helperData;
        $this->logger = $logger->create(OptionsListConstants::LOGGER_TYPE_INDEXING);
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->consoleOutput = $consoleOutput;
    }

    /**
     * Execute materialization on ids entities
     * Used by mview, allows process indexer in the "Update on schedule" mode
     *
     * @param array $ids
     * @return bool
     * @throws \Exception
     */
    public function execute($ids = [])
    {
        // check if authorization credentials were provided
        if (!$this->helperData->isAuthorizationCredentialsSetup()) {
            $message = 'Please check authorization credentials to perform this operation.';
            $this->logger->error($message);
            // for console action(s)
            if (php_sapi_name() === 'cli') {
                $this->consoleOutput->writeln("<error>{$message}</error>");
                return false;
            }
            // for frontend action(s)
            $this->messageManager->addWarningMessage(__($message));
            return false;
        }

        $storeIds = array_keys($this->storeManager->getStores());
        foreach ($storeIds as $storeId) {
            // check if in indexing queue enabled
            if (!$this->helperData->isIndexingQueueEnabled($storeId)) {
                $this->logger->error('Indexing queue is disabled. Start reindex.')->startTimer();

                try {
                    $index = $this->fullAction->rebuildProductStoreIndex($storeId, $ids);
                    $this->logger->info('Finished reindex. Stats:')->logStats();
                } catch (\Exception $e) {
                    if (php_sapi_name() === 'cli') {
                        $this->consoleOutput->writeln("<error>{$e->getMessage()}</error>");
                        return false;
                    }
                    $this->logger->error(sprintf('Reindex failed. Error: %s', $e->getMessage()));
                }

                if (!empty($index)) {
                    // @TODO - implement feed operation(s) based on index data

                }

                continue;
            }

            // detect reindex action type
            $reindexType = IndexingQueue::TYPE_REINDEX_ROW;
            if (empty($ids)) {
                // full reindex (clean index, save new index)
                $reindexType = IndexingQueue::TYPE_REINDEX_FULL;
            }
            if (count($ids) > 1) {
                // list reindex (delete index record(s), save index record(s))
                $reindexType = IndexingQueue::TYPE_REINDEX_LIST;
            }

            $this->queueHandler->add($ids, $reindexType, $storeId);
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
        $this->execute();
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
        if (!is_array($id)) {
            $id = [$id];
        }
        $this->execute($id);
    }
}