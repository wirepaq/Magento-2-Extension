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

use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProviderInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider\Inventory as ResourceModel;

/**
 * Data source used to append inventory data to product during indexing.
 *
 * Class Inventory
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider
 */
class Inventory implements DataSourceProviderInterface
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * InventoryData constructor.
     * @param ResourceModel $resourceModel
     */
    public function __construct(
        ResourceModel $resourceModel
    ) {
        $this->resourceModel = $resourceModel;
    }

    /**
     * Append inventory data to the product index data
     *
     * {@inheritdoc}
     */
    public function appendData($storeId, array $indexData)
    {
        $inventoryData = $this->resourceModel->loadInventoryData($storeId, array_keys($indexData));
        foreach ($inventoryData as $inventoryDataRow) {
            $productId = (int) $inventoryDataRow['product_id'];
            $isInStock = (bool) $inventoryDataRow['stock_status'];
            // for compatibility with unbxd service
            $indexData[$productId]['availability'] = $isInStock;
            $indexData[$productId]['stock'] = [
                'is_in_stock' => $isInStock,
                'qty' => (int) $inventoryDataRow['qty'],
            ];
        }

        return $indexData;
    }
}