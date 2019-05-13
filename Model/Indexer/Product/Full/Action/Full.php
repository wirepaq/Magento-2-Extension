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
namespace Unbxd\ProductFeed\Model\Indexer\Product\Full\Action;

use Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\Action\Full as ResourceModel;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider;
use Unbxd\ProductFeed\Logger\LoggerInterface;

/**
 * Unbxd product feed full indexer.
 *
 * Class Full
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\Action
 */
class Full
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var DataSourceProvider
     */
    private $dataSourceProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var integer
     */
    private $batchRowsCount;

    /**
     * Full constructor.
     * @param ResourceModel $resourceModel
     * @param DataSourceProvider $dataSourceProvider
     * @param LoggerInterface $logger
     * @param $batchRowsCount
     */
    public function __construct(
        ResourceModel $resourceModel,
        DataSourceProvider $dataSourceProvider,
        LoggerInterface $logger,
        $batchRowsCount
    ) {
        $this->resourceModel = $resourceModel;
        $this->dataSourceProvider = $dataSourceProvider;
        $this->logger = $logger;
        $this->batchRowsCount = $batchRowsCount;
    }

    /**
     * Load a bulk of product data.
     *
     * @param $storeId
     * @param array $productIds
     * @param int $fromId
     * @param null $limit
     * @return array
     */
    private function getProducts($storeId, $productIds = [], $fromId = 0, $limit = null)
    {
        return $this->resourceModel->getProducts($storeId, $productIds, $fromId, $limit);
    }

    /**
     * Get data for a list of product in a store id.
     * If the product list ids is null, all products data will be loaded.
     *
     * @param $storeId
     * @param array $productIds
     * @return array
     * @throws \Exception
     */
    private function initProductStoreIndex($storeId, $productIds = [])
    {
        $productId = 0;
        // magento is only sending children ids here.
        // ensure to reindex also the parents product ids, if any.
        if (!empty($productIds)) {
            $productIds = array_unique(
                array_merge($productIds, $this->resourceModel->getRelationsByChild($productIds))
            );
        }

        $products = $this->getProducts($storeId, $productIds, $productId);
        $result = [];
        foreach ($products as $productData) {
            $productId = (int) $productData['entity_id'];
            $productData['has_options'] = (bool) $productData['has_options'];
            $productData['required_options'] = (bool) $productData['required_options'];
            $result[$productId] = $productData;
        }

        return $result;
    }

    /**
     * @param $data
     * @param $size
     * @return \Generator
     */
    private function getBatchItems($data, $size)
    {
        $i = 0;
        $batch = [];

        foreach ($data as $key => $value) {
            $batch[$key] = $value;
            if (++$i == $size) {
                yield $batch;
                $i = 0;
                $batch = [];
            }
        }
        if (count($batch) > 0) {
            yield $batch;
        }
    }

    /**
     * Append product index data on the basis of which feed operation will be performed
     *
     * @param $storeId
     * @param $initIndexData
     * @return array|mixed
     */
    private function appendIndexData($storeId, $initIndexData)
    {
        $index = [];
        $batchSize = $this->batchRowsCount;
        foreach ($this->getBatchItems($initIndexData, $batchSize) as $index) {
            /** Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProviderInterface $dataSource */
            foreach ($this->dataSourceProvider->getList() as $dataSource) {
                if (!empty($index)) {
                    $index = $dataSource->appendData($storeId, $index);
                }
            }
        }

        return $index;
    }

    /**
     * Reindex all products data and return reindex result
     *
     * @param $storeId
     * @param array $productIds
     * @return array|mixed
     * @throws \Exception
     */
    public function rebuildProductStoreIndex($storeId, $productIds = [])
    {
        $initIndexData = $this->initProductStoreIndex($storeId, $productIds);
        $fullIndex = [];
        if (!empty($initIndexData)) {
            $fullIndex = $this->appendIndexData($storeId, $initIndexData);
        }

        // try to detect deleted product(s)
        if (!empty($productIds)) {
            $deletedCandidateIds = array_diff($productIds, array_keys($initIndexData));
            if (!empty($deletedCandidateIds)) {
                // mark deleted product in index data
                foreach ($deletedCandidateIds as $id) {
                    $fullIndex[$id]['action'] = 'delete';
                }
            }
        }

        return $fullIndex;
    }
}