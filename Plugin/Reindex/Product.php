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
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Unbxd\ProductFeed\Model\IndexingQueue\Handler;
use Magento\Framework\Model\AbstractModel;

/**
 * Class provides plugins to force reindex after product action processing
 *
 * Class ProductReindex
 * @package Unbxd\ProductFeed\Plugin
 */
class Product
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
     * @param ProductResource $productResource
     * @param \Closure $proceed
     * @param AbstractModel $product
     * @return mixed
     */
    public function aroundSave(
        ProductResource $productResource,
        \Closure $proceed,
        AbstractModel $product
    ) {
        $productResource->addCommitCallback(function () use ($product) {
            if (!$this->indexer->isScheduled()) {
                /** @var \Magento\Catalog\Model\Product $product */
                $id = $product->getId();
                Handler::$additionalInformation[$id] = $product->isObjectNew()
                    ? __('Product with ID %1 was added.', $id)
                    : __('Product with ID %1 was updated.', $id);
                // if indexer is 'Update on save' mode we need to rebuild related index data
                $this->indexer->reindexRow($id);
            }
        });

        return $proceed($product);
    }

    /**
     * @param ProductResource $productResource
     * @param \Closure $proceed
     * @param AbstractModel $product
     * @return mixed
     */
    public function aroundDelete(
        ProductResource $productResource,
        \Closure $proceed,
        AbstractModel $product
    ) {
        $productResource->addCommitCallback(function () use ($product) {
            if (!$this->indexer->isScheduled()) {
                /** @var \Magento\Catalog\Model\Product $product */
                $id = $product->getId();
                Handler::$additionalInformation[$id] =
                    __('Product with ID %1 was deleted.', $id);
                // if indexer is 'Update on save' mode we need to rebuild related index data
                $this->indexer->reindexRow($id);
            }
        });

        return $proceed($product);
    }
}