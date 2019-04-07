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
namespace Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider;

// @TODO - working

use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProviderInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider\Price as ResourceModel;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider\Price\PriceReaderInterface;

/**
 * Data source used to append prices data to product during indexing.
 *
 * Class Price
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider
 */
class Price implements DataSourceProviderInterface
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var PriceReaderInterface[]
     */
    private $priceReaderPool = [];

    /**
     * Price constructor.
     * @param ResourceModel $resourceModel
     * @param array $priceReaderPool
     */
    public function __construct(
        ResourceModel $resourceModel,
        $priceReaderPool = []
    ) {
        $this->resourceModel = $resourceModel;
        $this->priceReaderPool = $priceReaderPool;
    }

    /**
     * Append price data to the product index data.
     *
     * {@inheritdoc}
     */
    public function appendData($storeId, array $indexData)
    {
		// @TODO - implement
    }
}