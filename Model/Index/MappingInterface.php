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

use Unbxd\ProductFeed\Model\Index\Mapping\FieldInterface;
use Unbxd\ProductFeed\Model\Index\Mapping\FieldFilterInterface;

//@TODO - working

/**
 * Index MappingInterface.
 *
 * Interface MappingInterface
 * @package Unbxd\ProductFeed\Model\Index\Mapping
 */
interface MappingInterface
{
    /**
     * List of the properties of the mapping.
     *
     * @return array
     */
    public function getProperties();

    /**
     * List of the fields used to build the mapping.
     *
     * @return FieldInterface[]
     */
    public function getFields();

    /**
     * Return a field of the mapping by name.
     *
     * @param string $name
     * @return FieldInterface
     */
    public function getField($name);

    /**
     * Return the mapping as an array.
     *
     * @return array
     */
    public function asArray();

    /**
     * Return the mapping as an json.
     *
     * @return array
     */
    public function asJson();
}
