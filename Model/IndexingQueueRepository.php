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

use Unbxd\ProductFeed\Api\IndexingQueueRepositoryInterface;
use Unbxd\ProductFeed\Api\Data;
use Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue as ResourceIndexingQueue;
use Unbxd\ProductFeed\Model\IndexingQueueFactory;
use Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\CollectionFactory as IndexingQueueCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class IndexingQueueRepository
 * @package Unbxd\ProductFeed\Model
 */
class IndexingQueueRepository implements IndexingQueueRepositoryInterface
{
    /**
     * @var ResourceIndexingQueue
     */
    protected $resource;

    /**
     * @var IndexingQueueFactory
     */
    protected $indexingQueueFactory;

    /**
     * @var IndexingQueueCollectionFactory
     */
    protected $indexingQueueCollectionFactory;

    /**
     * @var Data\IndexingQueueSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var Data\IndexingQueueInterface
     */
    protected $dataIndexingQueueFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * IndexingQueueRepository constructor.
     * @param ResourceIndexingQueue $resource
     * @param \Unbxd\ProductFeed\Model\IndexingQueueFactory $indexingQueueFactory
     * @param Data\IndexingQueueInterface $dataIndexingQueueFactory
     * @param IndexingQueueCollectionFactory $indexingQueueCollectionFactory
     * @param Data\IndexingQueueSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface|null $collectionProcessor
     */
    public function __construct(
        ResourceIndexingQueue $resource,
        IndexingQueueFactory $indexingQueueFactory,
        Data\IndexingQueueInterface $dataIndexingQueueFactory,
        IndexingQueueCollectionFactory $indexingQueueCollectionFactory,
        Data\IndexingQueueSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        $this->resource = $resource;
        $this->indexingQueueFactory = $indexingQueueFactory;
        $this->indexingQueueCollectionFactory = $indexingQueueCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataIndexingQueueFactory = $dataIndexingQueueFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor ?: $this->getCollectionProcessor();
    }

    /**
     * Save queue data
     *
     * @param Data\IndexingQueueInterface $queue
     * @return Data\IndexingQueueInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\IndexingQueueInterface $queue)
    {
        try {
            $this->resource->save($queue);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $queue;
    }

    /**
     * Load queue data by given queue Identity
     *
     * @param int $queueId
     * @return Data\IndexingQueueInterface|IndexingQueue
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($queueId)
    {
        /** @var \Unbxd\ProductFeed\Model\IndexingQueue $queue */
        $queue = $this->indexingQueueFactory->create();
        $this->resource->load($queue, $queueId);
        if (!$queue->getId()) {
            throw new NoSuchEntityException(__('Queue with id "%1" does not exist.', $queueId));
        }

        return $queue;
    }

    /**
     * Load queue data collection by given search criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return Data\IndexingQueueSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        /** @var \Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\Collection $collection */
        $collection = $this->indexingQueueCollectionFactory->create();

//        $this->collectionProcessor->process($criteria, $collection);

        /** @var Data\IndexingQueueSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * Delete queue
     *
     * @param Data\IndexingQueueInterface $queue
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\IndexingQueueInterface $queue)
    {
        try {
            $this->resource->delete($queue);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * Delete queue by given queue Identity
     *
     * @param int $queueId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($queueId)
    {
        return $this->delete($this->getById($queueId));
    }

    /**
     * Retrieve collection processor
     *
     * @return CollectionProcessorInterface
     */
    private function getCollectionProcessor()
    {
        if (!$this->collectionProcessor) {
            // @TODO - implement (version compatibility, etc...)
        }

        return $this->collectionProcessor;
    }
}