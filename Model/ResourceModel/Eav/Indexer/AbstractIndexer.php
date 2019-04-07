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
namespace Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class provides util methods used by Eav indexer related resource models.
 *
 * Class AbstractIndexer
 * @package Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer
 */
abstract class AbstractIndexer
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * AbstractIndexer constructor.
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->storeManager = $storeManager;
    }

    /**
     * Get table name using the adapter.
     *
     * @param $tableName
     * @return string
     */
    protected function getTable($tableName)
    {
        return $this->resource->getTableName($tableName);
    }

    /**
     * Return database connection.
     *
     * @return AdapterInterface
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get store by id.
     *
     * @param $storeId
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStore($storeId)
    {
        return $this->storeManager->getStore($storeId);
    }

    /**
     * Retrieve store root category id.
     *
     * @param $store
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getRootCategoryId($store)
    {
        if (is_numeric($store) || is_string($store)) {
            $store = $this->getStore($store);
        }

        $storeGroupId = $store->getStoreGroupId();

        return $this->storeManager->getGroup($storeGroupId)->getRootCategoryId();
    }
}