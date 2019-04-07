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
use Unbxd\ProductFeed\Model\Rule\Condition\Product\SpecialAttributesProvider;

/**
 * Class SpecialAttributes
 * @package Unbxd\ProductFeed\Model\Config\Source
 */
class SpecialAttributes implements ArrayInterface
{
    /**
     * @var SpecialAttributesProvider
     */
    protected $specialAttributesProvider;

    /**
     * Flag whether to add all products without exclude or no
     *
     * @var bool
     */
    protected $addAllProducts = true;

    /**
     * ExcludeProduct constructor.
     * @param SpecialAttributesProvider $specialAttributesProvider
     */
    public function __construct(
        SpecialAttributesProvider $specialAttributesProvider
    ) {
        $this->specialAttributesProvider = $specialAttributesProvider;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = [];
        foreach ($this->specialAttributesProvider->getList() as $attribute) {
            $attributes[$attribute->getAttributeCode()] = $attribute->getLabel();
        }

        if ($this->addAllProducts && !array_key_exists('all', $attributes)) {
            $attributes = ['all' => __('DON\'T EXCLUDE')] + $attributes;
        }

        return $attributes;
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
