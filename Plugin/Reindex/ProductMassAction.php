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
namespace Unbxd\ProductFeed\Plugin\Product;

use Magento\Framework\Indexer\IndexerRegistry;
use Unbxd\ProductFeed\Model\Indexer\Product as UnbxdProductIndexer;
use Unbxd\ProductFeed\Model\IndexingQueue\Handler;
use Magento\Catalog\Model\Product\Action;

/**
 * Class provides plugins to force reindex after product mass action processing
 *
 * Class ProductMassAction
 * @package Unbxd\ProductFeed\Plugin\Product
 */
class ProductMassAction
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * Indexer instance
     *
     * @var object
     */
    private $indexer = null;

    /**
     * ProductMassActionReindex constructor.
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        IndexerRegistry $indexerRegistry
    ) {
        $this->indexerRegistry = $indexerRegistry;
        if (!$this->indexer) {
            $this->indexer = $indexerRegistry->get(UnbxdProductIndexer::INDEXER_ID);
        }
    }

    /**
     * @param Action $subject
     * @param \Closure $closure
     * @param array $productIds
     * @param array $attrData
     * @param $storeId
     * @return mixed
     */
    public function aroundUpdateAttributes(
        Action $subject,
        \Closure $closure,
        array $productIds,
        array $attrData,
        $storeId
    ) {
        $result = $closure($productIds, $attrData, $storeId);
        if (!$this->indexer->isScheduled()) {
            $productIds = array_unique($productIds);
            foreach ($productIds as $id) {
                Handler::$additionalInformation[$id] = __('Product with ID %1 was updated.', $id);
            }
            // if indexer is 'Update on save' mode we need to rebuild related index data
            $this->indexer->reindexList($productIds);
        }

        return $result;
    }

    /**
     * @param Action $subject
     * @param \Closure $closure
     * @param array $productIds
     * @param array $websiteIds
     * @param $type
     * @return mixed
     */
    public function aroundUpdateWebsites(
        Action $subject,
        \Closure $closure,
        array $productIds,
        array $websiteIds,
        $type
    ) {
        $result = $closure($productIds, $websiteIds, $type);
        if (!$this->indexer->isScheduled()) {
            $productIds = array_unique($productIds);
            foreach ($productIds as $id) {
                Handler::$additionalInformation[$id] = __('Product with ID %1 was updated.', $id);
            }
            // if indexer is 'Update on save' mode we need to rebuild related index data
            $this->indexer->reindexList($productIds);
        }

        return $result;
    }
}