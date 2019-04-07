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
namespace Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\AbstractIndexer;

/**
 * Class Indexer
 * @package Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer
 */
class Indexer extends AbstractIndexer
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * Indexer constructor.
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool
    ) {
        parent::__construct(
            $resource,
            $storeManager
        );
        $this->metadataPool = $metadataPool;
    }

    /**
     * Retrieve Metadata for an entity by entity type
     *
     * @param $entityType
     * @return EntityMetadataInterface
     * @throws \Exception
     */
    protected function getEntityMetaData($entityType)
    {
        return $this->metadataPool->getMetadata($entityType);
    }
}
