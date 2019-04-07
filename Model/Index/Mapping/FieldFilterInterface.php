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
 * Class implementing this interface allowed to specify a field filter.
 *
 * Interface FieldFilterInterface
 * @package Unbxd\ProductFeed\Model\Index\Mapping
 */
interface FieldFilterInterface
{
    /**
     * Indicates if the field has to be added to the list or not.
     *
     * @param FieldInterface $field
     * @return boolean
     */
    public function filterField(FieldInterface $field);
}
