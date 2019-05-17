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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Helper\ProductHelper;
use Unbxd\ProductFeed\Model\CronManager;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\Action\Full as ReindexAction;
use Unbxd\ProductFeed\Model\Feed\Manager as FeedManager;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Incremental
 * @package Unbxd\ProductFeed\Console\Command\Feed
 */
class Incremental extends Command
{
    const PRODUCTS_ID_ARGUMENT_KEY = 'products_id';
    const STORE_INPUT_OPTION_KEY = 'store';

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @var CronManager
     */
    private $cronManager;

    /**
     * @var ReindexAction
     */
    private $reindexAction;

    /**
     * @var FeedManager
     */
    private $feedManager;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Incremental constructor.
     * @param AppState $state
     * @param FeedHelper $feedHelper
     * @param ProductHelper $productHelper
     * @param CronManager $cronManager
     * @param ReindexAction $reindexAction
     * @param FeedManager $feedManager
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AppState $state,
        FeedHelper $feedHelper,
        ProductHelper $productHelper,
        CronManager $cronManager,
        ReindexAction $reindexAction,
        FeedManager $feedManager,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct();
        $this->appState = $state;
        $this->feedHelper = $feedHelper;
        $this->productHelper = $productHelper;
        $this->cronManager = $cronManager;
        $this->reindexAction = $reindexAction;
        $this->feedManager = $feedManager;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('unbxd:product-feed:incremental')
            ->setDescription('Incremental catalog product(s) synchronization with Unbxd service.')
            ->addArgument(
                self::PRODUCTS_ID_ARGUMENT_KEY,
                InputArgument::IS_ARRAY,
                'Product IDs for synchronization'
            )
            ->addOption(
                self::STORE_INPUT_OPTION_KEY,
                's',
                InputOption::VALUE_REQUIRED,
                'Use the specific Store View',
                Store::DEFAULT_STORE_ID
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        // check authorization credentials
        if (!$this->feedHelper->isAuthorizationCredentialsSetup()) {
            $output->writeln("<error>Please check authorization credentials to perform this operation.</error>");
            return false;
        }

        // check if related cron process doesn't occur to this process to prevent duplicate execution
        $jobs = $this->cronManager->getRunningSchedules(CronManager::FEED_JOB_CODE);
        if ($jobs->getSize()) {
            $message = 'At the moment, the cron job is already executing this process. 
                To prevent duplicate process, which will increase the load on the server, please try it later.';
            $output->writeln("<error>{$message}</error>");
            return false;
        }

        // check if product ids was setup
        $productIds = $input->getArgument(self::PRODUCTS_ID_ARGUMENT_KEY);
        if (!count($productIds)) {
            $output->writeln("<error>Product ID(s) are required. Please provide at least one product ID to perform this operation.</error>");
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        }

        $stores = [$this->getDefaultStoreId()];
        $storeId = $input->getOption(self::STORE_INPUT_OPTION_KEY);
        if ($storeId) {
            // in case if store code was passed instead of store id
            if (!is_numeric($storeId)) {
                $storeId = $this->getStoreIdByCode($storeId, $stores);
            }
            $stores = [$storeId];
        }

        // try to collect child product ids if any
        $this->collectAllProductIds($productIds);

        // pre process actions
        $this->preProcessActions($output);

        $errors = [];
        $start = microtime(true);
        if (!empty($stores)) {
            foreach ($stores as $storeId) {
                $storeName = $this->getStoreNameById($storeId);
                $output->writeln("<info>Performing operations for store with ID {$storeId} ({$storeName}):</info>");
                /** @var \Magento\Store\Model\Store $store */
                try {
                    $output->writeln("<info>Rebuild index...</info>");
                    $index = $this->reindexAction->rebuildProductStoreIndex($storeId, $productIds);
                } catch (\Exception $e) {
                    $output->writeln("<error>Indexing error: {$e->getMessage()}</error>");
                    $errors[$storeId] = $e->getMessage();
                    break;
                }

                try {
                    $output->writeln("<info>Execute feed...</info>");
                    $this->feedManager->execute($index, FeedConfig::FEED_TYPE_INCREMENTAL);
                } catch (\Exception $e) {
                    $output->writeln("<error>Feed execution error: {$e->getMessage()}</error>");
                    $errors[$storeId] = $e->getMessage();
                    break;
                }
            }
        }

        $errorMessage = 'Synchronization failed for store(s) with ID(s): %s. See feed view logs for additional information';
        $isSuccess = $this->feedHelper->isLastSynchronizationSuccess();

        if (!empty($errors)) {
            $affectedIds = implode(',', array_keys($errors));
            $errorMessage = sprintf($errorMessage, $affectedIds);
            $output->writeln("<error>{$errorMessage}</error>");
        } else if (!$isSuccess) {
            $affectedIds = implode(',', array_values($stores));
            $errorMessage = sprintf($errorMessage, $affectedIds);
            $output->writeln("<error>{$errorMessage}</error>");
        } else {
            $output->writeln("<info>Synchronization success</info>");
        }

        $end = microtime(true);
        $workingTime = round($end - $start, 2);
        $output->writeln("<info>Working time: {$workingTime}</info>");

        // post process actions
        $this->postProcessActions($output);

        return true;
    }

    /**
     * @param array $ids
     */
    private function collectAllProductIds(array &$ids)
    {
        foreach ($ids as $id) {
            $childIds = array_unique(array_filter($this->productHelper->getChildProductIds($id)));
            if (!empty($childIds)) {
                foreach ($childIds as $groupKey => $child) {
                    $ids = array_unique(array_merge_recursive($ids, array_values($child)));
                }
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @return $this
     */
    private function preProcessActions($output)
    {
        return $this;
    }

    /**
     * @param OutputInterface $output
     * @return $this
     */
    private function postProcessActions($output)
    {
        return $this;
    }

    /**
     * @param $storeCode
     * @param \Magento\Store\Api\Data\StoreInterface[] $stores
     * @return int
     */
    private function getStoreIdByCode($storeCode, $stores)
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
    private function getDefaultStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @param string $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStoreNameById($storeId = '')
    {
        return $this->storeManager->getStore($storeId)->getName();
    }
}