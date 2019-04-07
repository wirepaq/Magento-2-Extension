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
namespace Unbxd\ProductFeed\Model\Rule\Condition\Product\SpecialAttribute;

use Unbxd\ProductFeed\Model\Rule\Condition\Product\SpecialAttributeInterface;

// @TODO working

/**
 * Special "visibility" attribute class.
 *
 * Class Visibility
 * @package Unbxd\ProductFeed\Model\Rule\Condition\Product\SpecialAttribute
 */
class Visibility implements SpecialAttributeInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    private $booleanSource;

    /**
     * IsInStock constructor.
     * @param \Magento\Config\Model\Config\Source\Yesno $booleanSource
     */
    public function __construct(
        \Magento\Config\Model\Config\Source\Yesno $booleanSource
    ) {
        $this->booleanSource = $booleanSource;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeCode()
    {
        return 'visibility';
    }

    /**
     * {@inheritdoc}
     */
    public function getOperatorName()
    {
        return ' ';
    }

    /**
     * {@inheritdoc}
     */
    public function getInputType()
    {
        return 'select';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueElementType()
    {
        return 'hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueName()
    {
        return ' ';
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueOptions()
    {
        return $this->booleanSource->toOptionArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Not Visible Individually');
    }
}