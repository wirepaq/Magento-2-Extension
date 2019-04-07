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
namespace Unbxd\ProductFeed\Model\Indexer\Product\Full;

/**
 * Interface DataSourceProviderInterface
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full
 */
interface DataSourceProviderInterface
{
    /**
     * Append data to a list for indexation
     *
     * @param $storeId
     * @param array $indexData
     * @return mixed
     */
    public function appendData($storeId, array $indexData);
}
