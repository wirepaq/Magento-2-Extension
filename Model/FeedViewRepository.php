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

use Unbxd\ProductFeed\Api\FeedViewRepositoryInterface;
use Unbxd\ProductFeed\Api\Data;
use Unbxd\ProductFeed\Model\ResourceModel\FeedView as ResourceFeedView;
use Unbxd\ProductFeed\Model\FeedViewFactory;
use Unbxd\ProductFeed\Model\ResourceModel\FeedView\CollectionFactory as FeedViewCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class FeedViewRepository
 * @package Unbxd\ProductFeed\Model
 */
class FeedViewRepository implements FeedViewRepositoryInterface
{
    /**
     * @var ResourceFeedView
     */
    protected $resource;

    /**
     * @var FeedViewFactory
     */
    protected $feedViewFactory;

    /**
     * @var FeedViewCollectionFactory
     */
    protected $feedViewCollectionFactory;

    /**
     * @var Data\FeedViewSearchResultsInterfaceFactory
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
     * @var Data\FeedViewInterface
     */
    protected $dataFeedViewFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * FeedViewRepository constructor.
     * @param ResourceFeedView $resource
     * @param \Unbxd\ProductFeed\Model\FeedViewFactory $feedViewFactory
     * @param Data\FeedViewInterface $dataFeedViewFactory
     * @param FeedViewCollectionFactory $feedViewCollectionFactory
     * @param Data\FeedViewSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface|null $collectionProcessor
     */
    public function __construct(
        ResourceFeedView $resource,
        FeedViewFactory $feedViewFactory,
        Data\FeedViewInterface $dataFeedViewFactory,
        FeedViewCollectionFactory $feedViewCollectionFactory,
        Data\FeedViewSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        $this->resource = $resource;
        $this->feedViewFactory = $feedViewFactory;
        $this->feedViewCollectionFactory = $feedViewCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataFeedViewFactory = $dataFeedViewFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor ?: $this->getCollectionProcessor();
    }

    /**
     * Save feed view data
     *
     * @param Data\FeedViewInterface $feedView
     * @return Data\FeedViewInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\FeedViewInterface $feedView)
    {
        try {
            $this->resource->save($feedView);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $feedView;
    }

    /**
     * Load feed view data by given identity
     *
     * @param int $feedViewId
     * @return Data\FeedViewInterface|FeedView
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($feedViewId)
    {
        /** @var \Unbxd\ProductFeed\Model\FeedView $feedView */
        $feedView = $this->feedViewFactory->create();
        $this->resource->load($feedView, $feedViewId);
        if (!$feedView->getId()) {
            throw new NoSuchEntityException(__('Feed view with id "%1" does not exist.', $feedViewId));
        }

        return $feedView;
    }

    /**
     * Load feed view collection by given search criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return Data\FeedViewSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        /** @var \Unbxd\ProductFeed\Model\ResourceModel\FeedView\Collection $collection */
        $collection = $this->feedViewCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        /** @var Data\FeedViewSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * Delete feed view
     *
     * @param Data\FeedViewInterface $feedView
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\FeedViewInterface $feedView)
    {
        try {
            $this->resource->delete($feedView);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * Delete feed view by given identity
     *
     * @param int $feedViewId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($feedViewId)
    {
        return $this->delete($this->getById($feedViewId));
    }

    /**
     * Retrieve collection processor
     *
     * @return CollectionProcessorInterface
     */
    private function getCollectionProcessor()
    {
        if (!$this->collectionProcessor) {
            $this->collectionProcessor = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Unbxd\ProductFeed\Model\Api\SearchCriteria\FeedViewCollectionProcessor'
            );
        }

        return $this->collectionProcessor;
    }
}