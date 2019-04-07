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
namespace Unbxd\ProductFeed\Model\Rule\Condition\Product;

use Unbxd\ProductFeed\Model\Rule\Condition\Product\SpecialAttributeInterface;

/**
 * Class SpecialAttributesProvider
 * @package Unbxd\ProductFeed\Model\Rule\Condition\Product
 */
class SpecialAttributesProvider
{
    /**
     * @var SpecialAttributeInterface[]
     */
    private $attributes = [];

    /**
     * SpecialAttributesProvider constructor.
     *
     * @param SpecialAttributeInterface[] $attributes Attributes
     */
    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Retrieve Special Attributes list.
     *
     * @return SpecialAttributeInterface[]
     */
    public function getList()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a special attribute by code.
     *
     * @param string $attributeCode The attribute code to retrieve
     *
     * @return SpecialAttributeInterface|null
     */
    public function getAttribute($attributeCode)
    {
        return isset($this->attributes[$attributeCode]) ? $this->attributes[$attributeCode] : null;
    }
}