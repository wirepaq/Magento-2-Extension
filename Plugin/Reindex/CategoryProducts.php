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
use Magento\Catalog\Model\ResourceModel\Category as CategoryResourceModel;
use Magento\Framework\Model\AbstractModel;
use Unbxd\ProductFeed\Helper\ProductHelper;

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
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * Indexer instance
     *
     * @var object
     */
    private $indexer = null;

    /**
     * CategoryProducts constructor.
     * @param IndexerRegistry $indexerRegistry
     * @param ProductHelper $productHelper
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        ProductHelper $productHelper
    ) {
        $this->indexerRegistry = $indexerRegistry;
        if (!$this->indexer) {
            $this->indexer = $indexerRegistry->get(UnbxdProductIndexer::INDEXER_ID);
        }
        $this->productHelper = $productHelper;
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
                $validProductIds = [];
                foreach ($productIds as $id) {
                    /** @var \Magento\Catalog\Model\Product $product */
                    $product = $this->productHelper->getProduct($id);
                    if ($product && $this->productHelper->isProductTypeSupported($product->getTypeId())) {
                        $validProductIds[] = $id;
                        Handler::$additionalInformation[$id] = __('Product with ID %1 was updated.', $id);
                    }
                }
                if (!empty($validProductIds)) {
                    // if indexer is 'Update on save' mode we need to rebuild related index data
                    $this->indexer->reindexList($validProductIds);
                }
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
                $validProductIds = [];
                foreach ($productIds as $id) {
                    /** @var \Magento\Catalog\Model\Product $product */
                    $product = $this->productHelper->getProduct($id);
                    if ($product && $this->productHelper->isProductTypeSupported($product->getTypeId())) {
                        Handler::$additionalInformation[$id] = __('Product with ID %1 was updated.', $id);
                        $validProductIds[] = $id;
                    }
                }
                if (!empty($validProductIds)) {
                    // if indexer is 'Update on save' mode we need to rebuild related index data
                    $this->indexer->reindexList($validProductIds);
                }
            }
        });

        return $proceed($category);
    }
}