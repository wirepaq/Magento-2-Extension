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
namespace Unbxd\ProductFeed\Model\Indexer;

use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Unbxd\ProductFeed\Model\Cache as CacheHelper;

/**
 * Indexing operation handling
 *
 * Class IndexerHandler
 * @package Unbxd\ProductFeed\Model\Indexer
 */
class IndexerHandler implements IndexerInterface
{
    /**
     * @var \Magento\Framework\Indexer\SaveHandler\Batch
     */
    private $batch;

    /**
     * @var CacheHelper
     */
    private $cacheHelper;

    /**
     * Indexer id
     *
     * @var string
     */
    private $indexName;

    /**
     * Indexer type
     *
     * @var string
     */
    private $typeName;

    /**
     * IndexerHandler constructor.
     * @param CacheHelper $cacheHelper
     * @param Batch $batch
     * @param $indexName
     * @param $typeName
     */
    public function __construct(
        CacheHelper $cacheHelper,
        Batch $batch,
        $indexName,
        $typeName
    ) {
        $this->cacheHelper = $cacheHelper;
        $this->batch = $batch;
        $this->indexName = $indexName;
        $this->typeName = $typeName;
    }

    /**
     * {@inheritDoc}
     */
    public function saveIndex($dimensions, \Traversable $documents)
    {
        foreach ($dimensions as $dimension) {
            $storeId = $dimension->getValue();
            // @TODO - implement
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteIndex($dimensions, \Traversable $documents)
    {
        foreach ($dimensions as $dimension) {
            $storeId = $dimension->getValue();
            // @TODO - implement
        }

        return $this;
    }

    /**
     *
     * {@inheritDoc}
     */
    public function cleanIndex($dimensions)
    {
        foreach ($dimensions as $dimension) {
            $storeId = $dimension->getValue();
            // @TODO - implement
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable($dimensions = [])
    {
        // @TODO - implement
        return true;
    }
}