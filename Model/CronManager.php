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

use Magento\Framework\Crontab\CrontabManagerInterface;
use Magento\Framework\Crontab\TasksProviderInterface;
use Magento\Cron\Model\ConfigInterface;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\ScheduleFactory;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\Action\Full as FullReindexAction;
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\Collection
     */
    private $collection = null;

    /**
     * @var bool
     */
    private $lockFullReindex = false;

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
     * @param IndexingQueueCollectionFactory $indexingQueueCollectionFactory
     * @param QueueHandler $queueHandler
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CrontabManagerInterface $crontabManager,
        TasksProviderInterface $tasksProvider,
        ConfigInterface $config,
        CollectionFactory $cronFactory,
        ScheduleFactory $scheduleFactory,
        FullReindexAction $fullReindexAction,
        IndexingQueueCollectionFactory $indexingQueueCollectionFactory,
        QueueHandler $queueHandler,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->crontabManager = $crontabManager;
        $this->tasksProvider = $tasksProvider;
        $this->config = $config;
        $this->cronFactory = $cronFactory;
        $this->scheduleFactory = $scheduleFactory;
        $this->fullReindexAction = $fullReindexAction;
        $this->indexingQueueCollectionFactory = $indexingQueueCollectionFactory;
        $this->queueHandler = $queueHandler;
        $this->logger = $logger->create(OptionsListConstants::LOGGER_TYPE_INDEXING);
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
     * @return bool
     * @throws \Exception
     */
    public function runJobs()
    {
        // prevent duplicate full reindex
        if ($this->lockFullReindex) {
            $this->logger->info('Lock full reindex by another process.');
            return false;
        }

        $this->logger->info('Run cron job by schedule. Collect jobs.');

        /** @var \Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\Collection $jobs */
        $jobs = $this->getJobCollection();
        $jobs->addFieldToFilter('status', ['eq' => IndexingQueue::STATUS_PENDING])
            ->setPageSize(self::DEFAULT_JOBS_LIMIT_PER_RUN)
            ->setOrder('queue_id');

        if (!$jobs->getSize()) {
            $this->logger->info('There are no jobs for processing.');
            return false;
        }

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
                    'status' => IndexingQueue::STATUS_RUNNING,
                    'started_at' => date('Y-m-d H:i:s')
                ]
            );

            // retrieve entities id, empty array on full reindex
            $jobData = !$isFullReindex ? $this->queueHandler->convertStringToIds($job->getDataForProcessing()) : [];

            $this->logger->info(sprintf('Start reindex for job with #%s', $jobId))->startTimer();

            $isReindexSuccess = false;
            $jobIndexData = [];
            try {
                $jobIndexData = $this->fullReindexAction->rebuildProductStoreIndex($job->getStoreId(), $jobData);
                $this->logger->info(sprintf('Finished reindex for job with #%s. Stats:', $jobId))->logStats();
                $isReindexSuccess = true;
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf('Reindex failed for job with #%s. Error: %s', $jobId, $e->getMessage())
                );
            }

            $updateData = [
                'status' => $isReindexSuccess ? IndexingQueue::STATUS_COMPLETE : IndexingQueue::STATUS_ERROR,
                'finished_at' => date('Y-m-d H:i:s'),
                'execution_time' => $this->logger->getTime()
            ];

            $this->logger->info(sprintf('Update job record #%s', $jobId));

            $this->queueHandler->update($jobId, $updateData);

            if ($isReindexSuccess && !empty($jobIndexData)) {
                $indexData = $indexData + $jobIndexData;
            }
        }

        $this->lockFullReindex = false;

        if (!empty($indexData)) {
            // @TODO - implement feed operation(s) based on index data
        }

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
}