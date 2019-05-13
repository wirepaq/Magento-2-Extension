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
namespace Unbxd\ProductFeed\Model;

use Magento\Store\Model\Store;
use Unbxd\ProductFeed\Api\Data\IndexingQueueInterface;
use Magento\Framework\Crontab\CrontabManagerInterface;
use Magento\Framework\Crontab\TasksProviderInterface;
use Magento\Cron\Model\ConfigInterface;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\Action\Full as FullReindexAction;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Unbxd\ProductFeed\Model\Feed\Manager as FeedManager;
use Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\CollectionFactory as IndexingQueueCollectionFactory;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Unbxd\ProductFeed\Model\IndexingQueue\Handler as QueueHandler;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Logger\OptionsListConstants;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class CronManager
 * @package Unbxd\ProductFeed\Model
 */
class CronManager
{
    const FEED_JOB_CODE = 'unbxd_feed';

    const DEFAULT_COLLECTION_SIZE = 10;

    const DEFAULT_JOBS_LIMIT_PER_RUN = 5;

    /**
     * @var CrontabManagerInterface
     */
    private $crontabManager;

    /**
     * @var TasksProviderInterface
     */
    private $tasksProvider;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var CollectionFactory
     */
    protected $cronFactory;

    /**
     * @var ScheduleFactory
     */
    protected $scheduleFactory;

    /**
     * @var FullReindexAction
     */
    private $fullReindexAction;

    /**
     * @var FeedManager
     */
    private $feedManager;

    /**
     * @var IndexingQueueCollectionFactory
     */
    protected $indexingQueueCollectionFactory;

    /**
     * @var QueueHandler
     */
    private $queueHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\Collection
     */
    private $collection = null;

    /**
     * @var \Magento\Cron\Model\ResourceModel\Schedule\Collection
     */
    private $runningSchedules;

    /**
     * Flat to prevent duplicate full catalog product reindex
     *
     * @var bool
     */
    private $lockFullReindex = false;

    /**
     * Flat to prevent duplicate cron run
     *
     * @var bool
     */
    private $lockFullProcess = false;

    /**
     * Cron jobs cache
     *
     * @var null
     */
    protected $jobs = null;

    /**
     * CronManager constructor.
     * @param CrontabManagerInterface $crontabManager
     * @param TasksProviderInterface $tasksProvider
     * @param ConfigInterface $config
     * @param CollectionFactory $cronFactory
     * @param ScheduleFactory $scheduleFactory
     * @param FullReindexAction $fullReindexAction
     * @param FeedManager $feedManager
     * @param IndexingQueueCollectionFactory $indexingQueueCollectionFactory
     * @param QueueHandler $queueHandler
     * @param LoggerInterface $logger
     * @param HelperData $helperData
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CrontabManagerInterface $crontabManager,
        TasksProviderInterface $tasksProvider,
        ConfigInterface $config,
        CollectionFactory $cronFactory,
        ScheduleFactory $scheduleFactory,
        FullReindexAction $fullReindexAction,
        FeedManager $feedManager,
        IndexingQueueCollectionFactory $indexingQueueCollectionFactory,
        QueueHandler $queueHandler,
        LoggerInterface $logger,
        HelperData $helperData,
        StoreManagerInterface $storeManager
    ) {
        $this->crontabManager = $crontabManager;
        $this->tasksProvider = $tasksProvider;
        $this->config = $config;
        $this->cronFactory = $cronFactory;
        $this->scheduleFactory = $scheduleFactory;
        $this->fullReindexAction = $fullReindexAction;
        $this->feedManager = $feedManager;
        $this->indexingQueueCollectionFactory = $indexingQueueCollectionFactory;
        $this->queueHandler = $queueHandler;
        $this->logger = $logger->create(OptionsListConstants::LOGGER_TYPE_INDEXING);
        $this->helperData = $helperData;
        $this->storeManager = $storeManager;
    }

    /**
     * @param int $size
     * @return null
     */
    public function getCronJobs($size)
    {
        if ($this->jobs == null) {
            $result = [];
            /** @var \Magento\Cron\Model\ResourceModel\Schedule\Collection $scheduleCollection */
            $scheduleCollection = $this->cronFactory->create();
            $this->filterCollectionByTimeOffset($scheduleCollection, 86400, $size); // 60*60*24
            if (count($scheduleCollection) > 0) {
                foreach ($scheduleCollection as $jobRow) {
                    $result[] = [
                        'schedule_id' => $jobRow['schedule_id'],
                        'code' => $jobRow['job_code'],
                        'status' => $jobRow['status'],
                        'created_at' => $jobRow['created_at']
                    ];
                }
                $this->jobs = $result;
            }
        }

        return $this->jobs;
    }

    /**
     * @param \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection
     * @param $timeOffset
     * @param string $size
     * @return $this
     */
    private function filterCollectionByTimeOffset(
        \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection,
        $timeOffset,
        $size = ''
    ) {
        // try to retrieve data from last 24hrs
        $time = time();
        $to = date('Y-m-d H:i:s', $time);
        $lastTime = $time - $timeOffset;
        $from = date('Y-m-d H:i:s', $lastTime);
        $collection->addFieldToFilter(
            'created_at',
            ['from' => $from, 'to' => $to]
        )->setOrder('schedule_id')->setPageSize($size ?: self::DEFAULT_COLLECTION_SIZE);

        return $this;
    }

    /**
     * @param $jobCode
     * @param $status
     * @return \Magento\Cron\Model\ResourceModel\Schedule\Collection
     */
    public function getRunningSchedules($jobCode, $status = Schedule::STATUS_RUNNING)
    {
        if (!$this->runningSchedules) {
            /** @var \Magento\Cron\Model\ResourceModel\Schedule\Collection $scheduleCollection */
            $scheduleCollection = $this->cronFactory->create();
            $this->runningSchedules = $scheduleCollection->addFieldToFilter(
                    'status',
                    $status
                )->addFieldToFilter(
                    'job_code',
                    $jobCode
                )->addFieldToFilter(
                    'finished_at',
                    ['null' => true]
                )->load();
        }

        return $this->runningSchedules;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function runJobs()
    {
        // check authorization keys
        if (!$this->helperData->isAuthorizationCredentialsSetup()) {
            $this->logger->error('Please check authorization credentials to perform this operation.');
            return false;
        }

        // prevent duplicate full reindex
        if ($this->lockFullProcess || $this->lockFullReindex) {
            $this->logger->info('Lock full reindex by another process.');
            return false;
        }

        $this->logger->info('Run cron job by schedule. Collect tasks.');

        /** @var \Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\Collection $jobs */
        $jobs = $this->getJobCollection();
        $jobs->addFieldToFilter('status', ['eq' => IndexingQueue::STATUS_PENDING])
            ->setPageSize(self::DEFAULT_JOBS_LIMIT_PER_RUN)
            ->setOrder('queue_id');

        if (!$jobs->getSize()) {
            $this->logger->info('There are no jobs for processing.');
            return false;
        }

        $this->lockFullProcess = true;

        $indexData = [];
        foreach ($jobs as $job) {
            /** @var \Unbxd\ProductFeed\Model\IndexingQueue $job */
            $jobId = $job->getId();
            $isFullReindex = false;
            if ($job->getActionType() == IndexingQueue::TYPE_REINDEX_FULL) {
                $isFullReindex = true;
                $this->lockFullReindex = true;
            }

            $this->logger->info(sprintf('Prepare job with #%1 for reindex.', $jobId));
            // marked job as running
            $this->queueHandler->update($jobId,
                [
                    IndexingQueueInterface::STATUS => IndexingQueue::STATUS_RUNNING,
                    IndexingQueueInterface::STARTED_AT => date('Y-m-d H:i:s')
                ]
            );

            // retrieve entities id, empty array on full reindex
            $jobData = !$isFullReindex ? $this->queueHandler->convertStringToIds($job->getAffectedEntities()) : [];

            $this->logger->info(sprintf('Start reindex for job with #%s', $jobId))->startTimer();

            $isReindexSuccess = false;
            $jobIndexData = [];
            $error = false;
            // @TODO - need to figure out with stores
            $storeId = (!$job->getStoreId() || ($job->getStoreId() == Store::DEFAULT_STORE_ID))
                ? $this->storeManager->getStore()->getId()
                : $job->getStoreId();

            try {
                $jobIndexData = $this->fullReindexAction->rebuildProductStoreIndex($storeId, $jobData);
                $this->logger->info(sprintf('Finished reindex for job with #%s. Stats:', $jobId))->logStats();
                $isReindexSuccess = true;
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $this->logger->error(
                    sprintf('Reindex failed for job with #%s. Error: %s', $jobId, $error)
                );
            }

            $updateData = [
                IndexingQueueInterface::STATUS => $isReindexSuccess
                    ? IndexingQueue::STATUS_COMPLETE
                    : IndexingQueue::STATUS_ERROR,
                IndexingQueueInterface::FINISHED_AT => date('Y-m-d H:i:s'),
                IndexingQueueInterface::EXECUTION_TIME => $this->logger->getTime()
            ];
            if ($error) {
                $updateData[IndexingQueueInterface::ADDITIONAL_INFORMATION] = $error;
            }

            $this->logger->info(sprintf('Update job record #%s', $jobId));

            $this->queueHandler->update($jobId, $updateData);

            if ($isReindexSuccess && !empty($jobIndexData)) {
                $indexData += $jobIndexData;
            }
        }

        if (!empty($indexData)) {
            $type = $this->lockFullReindex ? FeedConfig::FEED_TYPE_FULL : FeedConfig::FEED_TYPE_INCREMENTAL;
            $this->feedManager->execute($indexData, $type);
        }

        $this->reset();

        return true;
    }

    /**
     * @return ResourceModel\IndexingQueue\Collection
     */
    private function getJobCollection()
    {
        if ($this->collection == null) {
            /** @var \Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\Collection collection */
            $this->collection = $this->indexingQueueCollectionFactory->create();
        }

        return $this->collection;
    }

    /**
     * Reset cache actions
     */
    private function reset()
    {
        $this->lockFullProcess = false;
        $this->lockFullReindex = false;
    }
}