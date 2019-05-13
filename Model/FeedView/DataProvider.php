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

namespace Unbxd\ProductFeed\Model\FeedView;

use Unbxd\ProductFeed\Model\ResourceModel\FeedView\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Class DataProvider
 * @package Unbxd\ProductFeed\Model\IndexingQueue
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Unbxd\ProductFeed\Model\ResourceModel\FeedView\Collection
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * Loaded data local cache
     *
     * @var array
     */
    protected $loadedData;

    /**
     * DataProvider constructor.
     * @param $name
     * @param $primaryFieldName
     * @param $requestFieldName
     * @param CollectionFactory $feedViewCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $feedViewCollectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $feedViewCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->meta = $this->prepareMeta($this->meta);
    }

    /**
     * Prepares Meta
     *
     * @param array $meta
     * @return array
     */
    public function prepareMeta(array $meta)
    {
        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        /** @var \Unbxd\ProductFeed\Model\ResourceModel\FeedView\Collection $items */
        $items = $this->collection->getItems();
        /** @var \Unbxd\ProductFeed\Model\FeedView $item */
        foreach ($items as $item) {
            $this->loadedData[$item->getId()] = $item->getData();
        }

        $data = $this->dataPersistor->get('feed_view_item');
        if (!empty($data)) {
            $item = $this->collection->getNewEmptyItem();
            $item->setData($data);
            $this->loadedData[$item->getId()] = $item->getData();
            $this->dataPersistor->clear('feed_view_item');
        }

        return $this->loadedData;
    }
}
