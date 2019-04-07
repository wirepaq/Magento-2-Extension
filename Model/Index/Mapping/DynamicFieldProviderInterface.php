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
namespace Unbxd\ProductFeed\Model\Index\Mapping;

//@TODO - working

use Unbxd\ProductFeed\Model\Index\Mapping\FieldInterface;

/**
 * Class implementing this interface can provides dynamic field to mapping.
 *
 * Interface DynamicFieldProviderInterface
 * @package Unbxd\ProductFeed\Model\Index\Mapping
 */
interface DynamicFieldProviderInterface
{
    /**
     * Return a list of mapping fields.
     *
     * @return FieldInterface[]
     */
    public function getFields();
}
