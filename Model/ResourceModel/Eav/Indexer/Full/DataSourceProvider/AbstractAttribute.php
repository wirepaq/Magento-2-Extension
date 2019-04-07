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
namespace Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Full\DataSourceProvider;

// @TODO - working

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Indexer;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;

/**
 * Abstract data source to retrieve attributes of EAV entities.
 *
 * Class AbstractAttribute
 * @package Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Full\DataSource
 */
class AbstractAttribute extends Indexer
{
    /**
     * @var null|string
     */
    private $entityTypeId = null;

    /**
     * AbstractAttributeData constructor.
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     * @param null $entityType
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        $entityType = null
    ) {
        $this->entityTypeId = $entityType;
        parent::__construct(
            $resource,
            $storeManager,
            $metadataPool
        );
    }

    /**
     * Get Entity Type Id.
     *
     * @return string
     */
    protected function getEntityTypeId()
    {
        return $this->entityTypeId;
    }
}