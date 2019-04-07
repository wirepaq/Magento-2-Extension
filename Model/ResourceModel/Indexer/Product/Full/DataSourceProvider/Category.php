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
namespace Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider;

// @TODO - working

use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Indexer;
use Magento\Framework\ObjectManagerInterface;

/**
 * Categories data source resource model.
 *
 * Class Category
 * @package Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider
 */
class Category extends Indexer
{
    /**
     * Local cache for category names
     *
     * @var array
     */
    private $categoryNameCache = [];

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig = null;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * CategoryData constructor.
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     * @param Config $eavConfig
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        Config $eavConfig,
        ObjectManagerInterface $objectManager
    ) {
        $this->eavConfig = $eavConfig;
        $this->objectManager = $objectManager;
        parent::__construct($resource, $storeManager, $metadataPool);
    }
}