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
use Unbxd\ProductFeed\Helper\ProductHelper;

/**
 * Class provides plugins to force reindex after product action processing
 *
 * Class ProductReindex
 * @package Unbxd\ProductFeed\Plugin\Reindex
 */
class Product
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
     * Product constructor.
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
            if (
                !$this->indexer->isScheduled()
                && $this->productHelper->isProductTypeSupported($product->getTypeId())
            ) {
                /** @var \Magento\Catalog\Model\Product $product */
                $id = $product->getId();
                Handler::$additionalInformation[$id] = $product->isObjectNew()
                    ? __('Product with ID %1 was added.', $id)
                    : __('Product with ID %1 was updated.', $id);
                // if indexer is 'Update on save' mode we need to rebuild related index data
                $this->indexer->reindexRow($id);
            }
        });

//        if ($product->hasDataChanges()) {
//            $diff = $this->compareArrayAssocRecursive2($product);

//            $newValues = $this->compareArrayAssocRecursive($product->getData(), $product->getOrigData());
//            $oldValues = $this->compareArrayAssocRecursive($product->getOrigData(), $product->getData());

//            $newValues = array_diff_assoc($product->getData(), $product->getOrigData());
//            $oldValues = array_diff_assoc($product->getOrigData(), $product->getData());
//            $added     = array_diff_key($product->getData(), $product->getOrigData());
//            $unset     = array_diff_key($product->getOrigData(), $product->getData());

//            $attributes = $this->getAttributes($product);
//            $diff = array_diff_key($attributes, $product->getData());

//            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
//            $logger = new \Zend\Log\Logger();
//            $logger->addWriter($writer);
//            $logger->info(json_encode($diff, JSON_PRETTY_PRINT));
//        }

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
            if (
                !$this->indexer->isScheduled()
                && $this->productHelper->isProductTypeSupported($product->getTypeId())
            ) {
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

    public function compareArrayAssocRecursive2($product)
    {
        $diff = [];
        $attributes = $product->getTypeInstance(true)->getEditableAttributes($product);

        foreach ($product->getOrigData() as $key => $value) {
            if ($product->dataHasChangedFor($key)) {
                $diff[$key] = $product->getData($key);
            }
        }

        return $diff;
    }


    public function compareArrayAssocRecursive($array1, $array2)
    {
        $diff = array();
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $diff[$key] = $value;
                } else {
                    $newDiff = $this->compareArrayAssocRecursive($value, $array2[$key]);
                    if (!empty($newDiff)) {
                        $diff[$key] = $newDiff;
                    }
                }
            } elseif (!array_key_exists($key,$array2) || $array2[$key] !== $value) {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * Retrieve product attributes
     * if $groupId is null - retrieve all product attributes
     *
     * @param int  $groupId   Retrieve attributes of the specified group
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAttributes($product, $groupId = null)
    {
        $productAttributes = $product->getTypeInstance()->getSetAttributes($product);
        if ($groupId) {
            $attributes = [];
            foreach ($productAttributes as $attribute) {
                if ($attribute->isInGroup($product->getAttributeSetId(), $groupId)) {
                    $attributes[] = $attribute;
                }
            }
        } else {
            $attributes = $productAttributes;
        }

        return $attributes;
    }
}