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
        $priceData = $this->resourceModel->loadPriceData($storeId, array_keys($indexData));
        foreach ($priceData as $priceDataRow) {
            $productId = (int) $priceDataRow['entity_id'];
            $productTypeId = $indexData[$productId]['type_id'];
            /** @var PriceReaderInterface $priceModifier */
            $priceReader = $this->getPriceReader($productTypeId);

            $price = $priceReader->getPrice($priceDataRow);
            $originalPrice = $priceReader->getOriginalPrice($priceDataRow);
            $specialPrice = $priceReader->getSpecialPrice($priceDataRow);
            $ifSpecialPriceNeedInclude = (bool) ($specialPrice && ($price != $specialPrice));

            $indexData[$productId]['price'] = $price;
            $indexData[$productId]['original_price'] = $originalPrice;
            if ($ifSpecialPriceNeedInclude) {
                $indexData[$productId]['special_price'] = $specialPrice;
                $indexData[$productId]['is_discount'] = (bool) ($price < $originalPrice);
            }

            if (!isset($indexData[$productId]['indexed_attributes'])) {
                $indexData[$productId]['indexed_attributes'] = ['price'];
                $indexData[$productId]['indexed_attributes'] = ['original_price'];
                if ($ifSpecialPriceNeedInclude) {
                    $indexData[$productId]['indexed_attributes'] = ['special_price'];
                    $indexData[$productId]['indexed_attributes'] = ['is_discount'];
                }
            }
            if (!in_array('price', $indexData[$productId]['indexed_attributes'])) {
                $indexData[$productId]['indexed_attributes'][] = 'price';
            }
            if (!in_array('original_price', $indexData[$productId]['indexed_attributes'])) {
                $indexData[$productId]['indexed_attributes'][] = 'original_price';
            }
            if ($ifSpecialPriceNeedInclude) {
                if (!in_array('special_price', $indexData[$productId]['indexed_attributes'])) {
                    $indexData[$productId]['indexed_attributes'][] = 'special_price';
                }
                if (!in_array('is_discount', $indexData[$productId]['indexed_attributes'])) {
                    $indexData[$productId]['indexed_attributes'][] = 'is_discount';
                }
            }
        }

        return $indexData;
    }

    /**
     * Retrieve price
     *
     * @param $typeId
     * @return mixed|PriceReaderInterface
     */
    private function getPriceReader($typeId)
    {
        $priceModifier = $this->priceReaderPool['default'];
        if (isset($this->priceReaderPool[$typeId])) {
            $priceModifier = $this->priceReaderPool[$typeId];
        }

        return $priceModifier;
    }
}