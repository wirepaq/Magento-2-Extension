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
namespace Unbxd\ProductFeed\Plugin\Reindex;

use Magento\Framework\Indexer\IndexerRegistry;
use Unbxd\ProductFeed\Model\Indexer\Product as UnbxdProductIndexer;
use Unbxd\ProductFeed\Model\IndexingQueue\Handler;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResourceModel;
use Magento\Framework\Model\AbstractModel;

/**
 * Class provides plugins to force reindex products after related category action processing
 *
 * Class CategoryProducts
 * @package Unbxd\ProductFeed\Plugin\Reindex
 */
class CategoryProducts
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
     * ProductReindexObserver constructor.
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
     * @param CategoryResourceModel $stockItemModel
     * @param \Closure $proceed
     * @param AbstractModel $category
     * @return mixed
     */
    public function aroundSave(
        CategoryResourceModel $stockItemModel,
        \Closure $proceed,
        AbstractModel $category
    ) {
        $stockItemModel->addCommitCallback(function () use ($category) {
            if (!$this->indexer->isScheduled()) {
                /** @var \Magento\Catalog\Model\Category $category */
                $productIds = array_unique($category->getAffectedProductIds());
                foreach ($productIds as $id) {
                    Handler::$additionalInformation[$id] = __('Product with ID %1 was updated.', $id);
                }
                // if indexer is 'Update on save' mode we need to rebuild related index data
                $this->indexer->reindexList($productIds);
            }
        });

        return $proceed($category);
    }

    /**
     * @param CategoryResourceModel $stockItemModel
     * @param \Closure $proceed
     * @param AbstractModel $category
     * @return mixed
     */
    public function aroundDelete(
        CategoryResourceModel $stockItemModel,
        \Closure $proceed,
        AbstractModel $category
    ) {
        $stockItemModel->addCommitCallback(function () use ($category) {
            if (!$this->indexer->isScheduled()) {
                /** @var \Magento\Catalog\Model\Category $category */
                $productIds = array_unique($category->getAffectedProductIds());
                foreach ($productIds as $id) {
                    Handler::$additionalInformation[$id] = __('Product with ID %1 was updated.', $id);
                }
                // if indexer is 'Update on save' mode we need to rebuild related index data
                $this->indexer->reindexList($productIds);
            }
        });

        return $proceed($category);
    }
}