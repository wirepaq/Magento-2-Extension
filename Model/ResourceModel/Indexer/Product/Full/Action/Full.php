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
namespace Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\Action;

// @TODO - working

use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Indexer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Unbxd product full indexer resource model.
 *
 * Class Full
 * @package Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\Action
 */
class Full extends Indexer
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Full constructor.
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct(
            $resource,
            $storeManager,
            $metadataPool
        );
        $this->objectManager = $objectManager;
    }

}