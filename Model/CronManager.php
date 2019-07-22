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
use Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\CollectionFactory as IndexingQueueCollectionFactory;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Unbxd\ProductFeed\Model\IndexingQueue\Handler as QueueHandler;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Unbxd\ProductFeed\Model\Feed\Manager as FeedManager;
use Unbxd\ProductFeed\Api\Data\FeedViewInterface;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Model\FeedView\Handler as FeedViewHandler;
use Unbxd\ProductFeed\Model\ResourceModel\FeedView\CollectionFactory as FeedViewCollectionFactory;
use Unbxd\ProductFeed\Model\CacheManager;
use Unbxd\ProductFeed\Model\Feed\Api\Connector as ApiConnector;
use Unbxd\ProductFeed\Model\Feed\Api\ConnectorFactory;
use Unbxd\ProductFeed\Model\Feed\Api\Response as FeedResponse;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Magento\Framework\DB\Helper as DBHelper;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Logger\OptionsListConstants;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class CronManager
 * @package Unbxd\ProductFeed\Model
 */
class CronManager
{
    const FEED_JOB_CODE_PREFIX = 'unbxd_product_feed';

    const FEED_JOB_CODE_UPLOAD = self::FEED_JOB_CODE_PREFIX . '_upload';

    const FEED_JOB_CODE_CHECK_UPLOADED_STATUS = self::FEED_JOB_CODE_PREFIX . '_check_uploaded_status';

    const DEFAULT_COLLECTION_SIZE = 20;

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
     * @var FeedViewHandler
     */
    private $feedViewHandler;

    /**
     * @var FeedViewCollectionFactory
     */
    private $feedViewCollectionFactory;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var ConnectorFactory
     */
    private $connectorFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var DBHelper
     */
    private $resourceHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Cron\Model\ResourceModel\Schedule\Collection
     */
    private $runningSchedules;

    /**
     * Flat to prevent duplicate cron jobs
     *
     * @var bool
     */
    private $lockProcess = false;

    /**
     * Cron jobs cache
     *
     * @var null
     */
    protected $jobs = null;

    /**
     * Local cache for feed API connector manager
     *
     * @var null
     */
    private $connectorManager = null;

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
     * @param FeedViewHandler $feedViewHandler
     * @param FeedViewCollectionFactory $feedViewCollectionFactory
     * @param \Unbxd\ProductFeed\Model\CacheManager $cacheManager
     * @param ConnectorFactory $connectorFactory
     * @param LoggerInterface $logger
     * @param FeedHelper $feedHelper
     * @param HelperData $helperData
     * @param DBHelper $resourceHelper
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
        FeedViewHandler $feedViewHandler,
        FeedViewCollectionFactory $feedViewCollectionFactory,
        CacheManager $cacheManager,
        ConnectorFactory $connectorFactory,
        LoggerInterface $logger,
        FeedHelper $feedHelper,
        HelperData $helperData,
        DBHelper $resourceHelper,
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
        $this->feedViewHandler = $feedViewHandler;
        $this->cacheManager = $cacheManager;
        $this->feedViewCollectionFactory = $feedViewCollectionFactory;
        $this->connectorFactory = $connectorFactory;
        $this->logger = $logger->create(OptionsListConstants::LOGGER_TYPE_INDEXING);
        $this->feedHelper = $feedHelper;
        $this->helperData = $helperData;
        $this->resourceHelper = $resourceHelper;
        $this->storeManager = $storeManager;
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
     * @param \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection
     * @param $jobCode
     * @return $this
     */
    private function filterCollectionByJobCode(
        \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection,
        $jobCode
    ) {
        $jobCodeLike = $this->resourceHelper->addLikeEscape(
            $jobCode,
            ['position' => 'any']
        );
        $collection->addFieldToFilter(
            'job_code',
            ['like' => $jobCodeLike]
        );

        return $this;
    }

    /**
     * @param \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection
     * @param $size
     * @return $this
     */
    private function filterCollection(
        \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection,
        $size
    ) {
        // try to retrieve data from last 24hrs
        $this->filterCollectionByTimeOffset($collection, 86400, $size); // 60*60*24
        // retrieve only jobs affected by unxbd feed process
        $this->filterCollectionByJobCode($collection, self::FEED_JOB_CODE_PREFIX);

        return $this;
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
            $this->filterCollection($scheduleCollection, $size);
            if (count($scheduleCollection) > 0) {
                foreach ($scheduleCollection as $jobRow) {
                    $result[] = [
                        'schedule_id' => isset($jobRow['schedule_id']) ? $jobRow['schedule_id'] : '',
                        'code' => isset($jobRow['job_code']) ? $jobRow['job_code'] : '',
                        'status' => isset($jobRow['status']) ? $jobRow['status'] : '',
                        'created_at' => isset($jobRow['created_at']) ? $jobRow['created_at'] : '',
                        'messages' => isset($jobRow['messages']) ? $jobRow['messages'] : ''
                    ];
                }
                $this->jobs = $result;
            }
        }

        return $this->jobs;
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
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function uploadFeed()
    {
        // prevent duplicate jobs
        if ($this->lockProcess) {
            $this->logger->info('Lock reindex by another process.');
            return false;
        }

        // check if cron is configured
        if (!$this->helperData->isCronConfigured()) {
            $this->logger->error('Cron is not configured. Please configure related cron job to perform this operation.');
            return false;
        }

        // check authorization keys
        if (!$this->helperData->isAuthorizationCredentialsSetup()) {
            $this->logger->error('Please check authorization credentials to perform this operation.');
            return false;
        }

        $this->lockProcess = true;

        $this->logger->info('Run cron job by schedule. Collect tasks.');

        /** @var \Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\Collection $jobs */
        $jobs = $this->indexingQueueCollectionFactory->create();
        $jobs->addFieldToFilter(
            IndexingQueueInterface::STATUS,
                ['eq' => IndexingQueue::STATUS_PENDING]
        )->setPageSize(
            self::DEFAULT_JOBS_LIMIT_PER_RUN
        )->setOrder(
            IndexingQueueInterface::QUEUE_ID
        );

        if (!$jobs->getSize()) {
            $this->logger->info('There are no jobs for processing.');
            return false;
        }

        $indexData = [];
        $isFullReindex = false;
        foreach ($jobs as $job) {
            /** @var \Unbxd\ProductFeed\Model\IndexingQueue $job */
            $jobId = $job->getId();
            if ($job->getActionType() == IndexingQueue::TYPE_REINDEX_FULL) {
                $isFullReindex = true;
            }

            $this->logger->info(sprintf('Prepare job with #%d for reindex.', $jobId));
            // marked job as running
            $this->queueHandler->update($jobId,
                [
                    IndexingQueueInterface::STATUS => IndexingQueue::STATUS_RUNNING,
                    IndexingQueueInterface::STARTED_AT => date('Y-m-d H:i:s')
                ]
            );

            // retrieve entities id, empty array on full reindex
            $jobData = !$isFullReindex ? $this->queueHandler->convertStringToIds($job->getAffectedEntities()) : [];

            $this->logger->info(sprintf('Start reindex for job with #%d', $jobId))->startTimer();

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
                    sprintf('Reindex failed for job with #%d. Error: %s', $jobId, $error)
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

            $this->logger->info(sprintf('Update job record #%d', $jobId));

            $this->queueHandler->update($jobId, $updateData);

            if ($isReindexSuccess && !empty($jobIndexData)) {
                $indexData += $jobIndexData;
            }
        }

        if (empty($indexData)) {
            $this->logger->error('Can\'t execute feed. Empty index data.');
            return false;
        }

        $type = $isFullReindex ? FeedConfig::FEED_TYPE_FULL : FeedConfig::FEED_TYPE_INCREMENTAL;
        $this->feedManager->execute($indexData, $type);

        $this->lockProcess = false;

        return true;
    }

    /**
     * Runs jobs to check uploaded feed status
     *
     * @return $this
     */
    public function checkUploadedFeedStatus()
    {
        /** @var \Unbxd\ProductFeed\Model\ResourceModel\FeedView\Collection $jobs */
        $jobs = $this->feedViewCollectionFactory->create();
        $jobs->addFieldToFilter(
            FeedViewInterface::STATUS,
            ['eq' => FeedView::STATUS_INDEXING]
        )->addFieldToFilter(
            FeedViewInterface::UPLOAD_ID,
            ['neq' => null]
        )->setPageSize(
            self::DEFAULT_JOBS_LIMIT_PER_RUN
        )->setOrder(
            FeedViewInterface::FEED_ID
        );

        if (!$jobs->getSize()) {
            return $this;
        }

        foreach ($jobs as $job) {
            /** @var \Unbxd\ProductFeed\Model\FeedView $job */
            $jobId = $job->getId();
            $uploadId = trim($job->getUploadId());
            $jobType = trim($job->getOperationTypes());
            if (!$jobId || !$uploadId) {
                continue;
            }

            $apiEndpointType = ($jobType == FeedConfig::FEED_TYPE_FULL)
                ? FeedConfig::FEED_TYPE_FULL_UPLOADED_STATUS
                : FeedConfig::FEED_TYPE_INCREMENTAL_UPLOADED_STATUS;

            /** @var \Unbxd\ProductFeed\Model\Feed\Api\Connector $connectorManager */
            $connectorManager = $this->getConnectorManager();

            try {
                $connectorManager->resetHeaders()
                    ->resetParams()
                    ->setExtraParams([FeedViewInterface::UPLOAD_ID => $uploadId])
                    ->execute($apiEndpointType, \Zend_Http_Client::GET);
            } catch (\Exception $e) {
                // catch and log exception
                return $this;
            }

            /** @var FeedResponse $response */
            $response = $connectorManager->getResponse();
            if ($response instanceof FeedResponse) {
                $responseBodyData = $response->getResponseBodyAsArray();
                if (!empty($responseBodyData)) {
                    $status = array_key_exists(FeedResponse::RESPONSE_FIELD_STATUS, $responseBodyData)
                        ? $responseBodyData[FeedResponse::RESPONSE_FIELD_STATUS]
                        : null;
                    if ($status && ($status != FeedResponse::RESPONSE_FIELD_STATUS_VALUE_INDEXING)) {
                        $status = ($status == FeedResponse::RESPONSE_FIELD_STATUS_VALUE_INDEXED)
                            ? FeedView::STATUS_COMPLETE
                            : FeedView::STATUS_ERROR;
                        $updateData = [
                            FeedViewInterface::STATUS => $status
                        ];
                        if ($response->getIsError()) {
                            $updateData[FeedViewInterface::ADDITIONAL_INFORMATION] = $response->getErrorsAsString();
                        } else if ($response->getIsSuccess()) {
                            $updateData[FeedViewInterface::ADDITIONAL_INFORMATION] =
                                __(FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_COMPLETE);
                        }

                        $this->updateFeedInformation($jobId, $jobType, $updateData, $status);
                    }
                }
            }

            $connectorManager->resetExtraParams()
                ->resetResponse();
        }

        // in some cases related config info doesn't refreshing on backend frontend
        $this->flushSystemConfigCache();

        return $this;
    }

    /**
     * Update related feed view information and feed configuration data based on API response
     *
     * @param $jobId
     * @param $jobType
     * @param $updateData
     * @param $status
     */
    private function updateFeedInformation($jobId, $jobType, $updateData, $status)
    {
        $this->feedViewHandler->update($jobId, $updateData);
        $this->feedHelper->setLastSynchronizationStatus($status);

        $isSuccess = (bool) ($status == FeedView::STATUS_COMPLETE);
        if ($jobType == FeedConfig::FEED_TYPE_FULL) {
            $this->feedHelper->setFullCatalogSynchronizedStatus($isSuccess);
        } else if ($jobType == FeedConfig::FEED_TYPE_INCREMENTAL) {
            $this->feedHelper->setIncrementalProductSynchronizedStatus($isSuccess);
        }
    }

    /**
     * Clean configuration cache.
     * In some cases related config info doesn't refreshing on backend frontend
     *
     * @return $this
     */
    private function flushSystemConfigCache()
    {
        try {
            $this->cacheManager->flushCacheByType(CacheManager::SYSTEM_CONFIGURATION_CACHE_TYPE);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * Retrieve connector manager instance. Init if needed
     *
     * @return ApiConnector|null
     */
    private function getConnectorManager()
    {
        if (null == $this->connectorManager) {
            /** @var ApiConnector */
            $this->connectorManager = $this->connectorFactory->create();
        }

        return $this->connectorManager;
    }
}