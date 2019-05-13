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
use Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider\Category as ResourceModel;

/**
 * Data source used to append categories data to product during indexing.
 *
 * Class Category
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider
 */
class Category implements DataSourceProviderInterface
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * CategoryData constructor.
     * @param ResourceModel $resourceModel
     */
    public function __construct(
        ResourceModel $resourceModel
    ) {
        $this->resourceModel = $resourceModel;
    }

    /**
     * Append categories data to the product index data.
     *
     * {@inheritdoc}
     */
    public function appendData($storeId, array $indexData)
    {
        $categoryData = $this->resourceModel->loadCategoryData($storeId, array_keys($indexData));
        foreach ($categoryData as $categoryDataRow) {
            $productId = (int) $categoryDataRow['product_id'];
            unset($categoryDataRow['product_id']);

            $categoryDataRow = array_merge(
                $categoryDataRow,
                [
                    'category_id' => (int) $categoryDataRow['category_id'],
//                    'is_parent' => (bool) $categoryDataRow['is_parent'],
//                    'name' => (string) $categoryDataRow['name'],
                ]
            );

            if (isset($categoryDataRow['position']) && $categoryDataRow['position'] !== null) {
                $categoryDataRow['position'] = (int) $categoryDataRow['position'];
            }

            $indexData[$productId]['category'][] = array_filter($categoryDataRow);
        }

        return $indexData;
    }
}
