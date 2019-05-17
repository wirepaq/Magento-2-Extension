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
namespace Unbxd\ProductFeed\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ProductTypes
 * @package Unbxd\ProductFeed\Model\Config\Source
 */
class ProductTypes implements ArrayInterface
{
    /**
     * Constant for all supported product types
     */
    const ALL_KEY = 'all';

    /**
     * Product type model
     *
     * @var \Magento\Catalog\Model\Product\TypeFactory
     */
    protected $typeFactory;

    /**
     * Flag whether to add product all types or no
     *
     * @var bool
     */
    protected $addAllTypes = true;

    /**
     * Supported product types
     *
     * @var array
     */
    private $supportedTypes = [
        \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
        \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE
    ];

    /**
     * DebugDisplay constructor.
     * @param \Magento\Catalog\Model\Product\TypeFactory $typeFactory
     */
    public function __construct(
        \Magento\Catalog\Model\Product\TypeFactory $typeFactory
    ) {
        $this->typeFactory = $typeFactory;
    }

    /**
     * Retrieve product types
     *
     * @return array
     */
    protected function getProductTypes()
    {
        $result = [];
        /** @var  $types */
        $types = $this->typeFactory->create()->getTypes();
        uasort(
            $types,
            function ($elementOne, $elementTwo) {
                return ($elementOne['sort_order'] < $elementTwo['sort_order']) ? -1 : 1;
            }
        );

        // @TODO - temporary solution, need to show all product types, not supported must be disabled
        foreach ($types as $typeId => $type) {
            if (in_array($typeId, $this->supportedTypes)) {
                $result[$typeId] = __($type['label']);
            }
        }

        if ($this->addAllTypes && !array_key_exists(self::ALL_KEY, $result)) {
            $result = [self::ALL_KEY => __('All AVAILABLE TYPES')] + $result;
        }

        return $result;
    }

    /**
     * Get all supported product types
     *
     * @return array
     */
    public function getAllSupportedProductTypes()
    {
        return $this->supportedTypes;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getProductTypes();
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $optionArray = [];
        $arr = $this->toArray();
        foreach ($arr as $value => $label) {
            $optionArray[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $optionArray;
    }
}
