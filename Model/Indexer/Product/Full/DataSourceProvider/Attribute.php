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

use Unbxd\ProductFeed\Model\Eav\Indexer\Full\DataSourceProvider\AbstractAttribute;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProviderInterface;

/**
 * Data source used to append attributes data to product during indexing.
 *
 * Class AttributeData
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider
 */
class AttributeData extends AbstractAttribute implements DataSourceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function appendData($storeId, array $indexData)
    {
		// @TODO - implement
    }
}