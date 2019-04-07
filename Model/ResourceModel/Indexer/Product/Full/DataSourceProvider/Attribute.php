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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Full\DataSourceProvider\AbstractAttribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Type as ProductType;

/**
 * Attribute data source resource model.
 *
 * Class Attribute
 * @package Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider
 */
class Attribute extends AbstractAttribute
{
    /**
     * Catalog product type
     *
     * @var \Magento\Catalog\Model\Product\Type
     */
    private $catalogProductType;

    /**
     * @var \Magento\Catalog\Model\Product\Type[]
     */
    private $productTypes = [];

    /**
     * @var array
     */
    private $productEmulators = [];

    /**
     * Attributes constructor.
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     * @param ProductType $catalogProductType
     * @param string $entityType
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        ProductType $catalogProductType,
        $entityType = ProductInterface::class
    ) {
        parent::__construct(
            $resource,
            $storeManager,
            $metadataPool,
            $entityType
        );
        $this->catalogProductType = $catalogProductType;
    }
}