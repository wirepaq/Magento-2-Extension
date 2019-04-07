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

// @TODO working

/**
 * Definition of "special attributes" that can be used for building rules for product collection
 *
 * Interface SpecialAttributeInterface
 * @package Unbxd\ProductFeed\Model\Rule\Condition\Product
 */
interface SpecialAttributeInterface
{
    /**
     * @return string
     */
    public function getAttributeCode();

    /**
     * @return string
     */
    public function getOperatorName();

    /**
     * @return string
     */
    public function getInputType();

    /**
     * @return string
     */
    public function getValueElementType();

    /**
     * @return string
     */
    public function getValueName();

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @return array
     */
    public function getValueOptions();

    /**
     * @return string
     */
    public function getLabel();
}