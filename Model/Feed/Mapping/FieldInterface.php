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
namespace Unbxd\ProductFeed\Model\Feed\Mapping;

/**
 * Interface FieldInterface
 * @package Unbxd\ProductFeed\Model\Feed\Mapping
 */
interface FieldInterface
{
    /**
     * Standard field types declaration.
     */
    const FIELD_TYPE_BOOL       = 'bool';
    const FIELD_TYPE_TEXT       = 'text';
    const FIELD_TYPE_LONGTEXT   = 'longText';
    const FIELD_TYPE_LINK       = 'link';
    const FIELD_TYPE_NUMBER     = 'number';
    const FIELD_TYPE_DECIMAL    = 'decimal';
    const FIELD_TYPE_DATE       = 'date';

    /**
     * Feature field types declaration
     * @var string
     */
    const FEATURE_FIELD_ENTITY_ID       = 'unique_id';
    const FEATURE_FIELD_STOCK_STATUS    = 'availability';
    const FEATURE_FIELD_IMAGE_URL       = 'image_url';
    const FEATURE_FIELD_PRODUCT_URL     = 'product_url';
    const FEATURE_FIELD_CATEGORY_PATH   = 'category_path_id';
}
