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
namespace Unbxd\ProductFeed\Model\Feed;

/**
 * Class Config
 * @package Unbxd\ProductFeed\Model\Feed
 */
class Config
{
    /**
     * Feed API Synchronization default endpoints (will be used if related endpoints are not specified in config fields)
     */
    const FEED_FULL_API_ENDPOINT_DEFAULT = 'http://feed.unbxd.io/api/%s/upload/catalog/full';
    const FEED_INCREMENTAL_API_ENDPOINT_DEFAULT = 'http://feed.unbxd.io/api/%s/upload/catalog/delta';

    /**
     * Feed types:
     *
     *  full - full catalog product synchronization
     *  incremental - separate product(s) synchronization
     */
    const FEED_TYPE_FULL = 'full';
    const FEED_TYPE_INCREMENTAL = 'incremental';

    /**
     * Flag to check whether or not include catalog fields to feed data
     */
    const INCLUDE_CATALOG = true;

    /**
     * Flag to check whether or not include schema fields to feed data
     */
    const INCLUDE_SCHEMA = true;

    /**
     *
     */
    const DEFAULT_SCHEMA_AUTO_SUGGEST_FIELD_VALUE = false;

    /**
     * Index fields related to child products (variants)
     */
    const CHILD_PRODUCT_SKUS_FIELD_KEY = 'sku';
    const CHILD_PRODUCT_IDS_FIELD_KEY = 'children_ids';
    const CHILD_PRODUCT_ATTRIBUTES_FIELD_KEY = 'children_attributes';
    const CHILD_PRODUCT_CONFIGURABLE_ATTRIBUTES_FIELD_KEY = 'configurable_attributes';

    /**
     * Field key for feed data
     */
    const FEED_FIELD_KEY = 'feed';

    /**
     * Field key for catalog data
     */
    const CATALOG_FIELD_KEY = 'catalog';

    /**
     * Field key for catalog items
     */
    const CATALOG_ITEMS_FIELD_KEY = 'items';

    /**
     * Field key for schema fields
     */
    const SCHEMA_FIELD_KEY = 'schema';

    /**
     * Child products (variants) field key
     */
    const CHILD_PRODUCTS_FIELD_KEY = 'variants';

    /**
     * Child product (variant) field prefix
     */
    const CHILD_PRODUCT_FIELD_PREFIX = 'v';

    /**
     * Default batch size for prepare feed data
     */
    const DEFAULT_BATCH_SIZE_PREPARE_FEED_DATA = 1000;

    /**
     * Default batch size for write feed data
     */
    const DEFAULT_BATCH_SIZE_WRITE_FEED_DATA = 1000;

    /**
     * Feed operation types (e.g. add new product, update product data, delete product)
     */
    const OPERATION_TYPE_ADD       = 'add';
    const OPERATION_TYPE_UPDATE    = 'update';
    const OPERATION_TYPE_DELETE    = 'delete';
    const OPERATION_TYPE_FULL      = 'full';

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
     * Default fields declaration use for map
     */
    const FIELD_KEY_ENTITY_ID       = 'entity_id';
    const FIELD_KEY_PRODUCT_NAME    = 'name';
    const FIELD_KEY_IMAGE_PATH      = 'image';
    const FIELD_KEY_PRODUCT_URL_KEY = 'url_key';
    const FIELD_KEY_STOCK_STATUS    = 'stock_status';
    const FIELD_KEY_CATEGORY_DATA   = 'category';

    /**
     * Specific fields declaration
     */
    const SPECIFIC_FIELD_KEY_UNIQUE_ID          = 'unique_id';
    const SPECIFIC_FIELD_KEY_TITLE              = 'title';
    const SPECIFIC_FIELD_KEY_IMAGE_URL          = 'image_url';
    const SPECIFIC_FIELD_KEY_PRODUCT_URL        = 'product_url';
    const SPECIFIC_FIELD_KEY_AVAILABILITY       = 'availability';
    const SPECIFIC_FIELD_KEY_CATEGORY_PATH_ID   = 'category_path_id';

    /**
     * @var array
     */
    private $mapSpecificFields = [
        self::FIELD_KEY_ENTITY_ID => self::SPECIFIC_FIELD_KEY_UNIQUE_ID,
        self::FIELD_KEY_PRODUCT_NAME => self::SPECIFIC_FIELD_KEY_TITLE,
        self::FIELD_KEY_IMAGE_PATH => self::SPECIFIC_FIELD_KEY_IMAGE_URL,
        self::FIELD_KEY_PRODUCT_URL_KEY => self::SPECIFIC_FIELD_KEY_PRODUCT_URL,
        self::FIELD_KEY_STOCK_STATUS => self::SPECIFIC_FIELD_KEY_AVAILABILITY,
        self::FIELD_KEY_CATEGORY_DATA => self::SPECIFIC_FIELD_KEY_CATEGORY_PATH_ID
    ];

    /**
     * @return array
     */
    public function getMapSpecificFields()
    {
        return $this->mapSpecificFields;
    }

    /**
     * @return array
     */
    public function getAvailableOperationTypes()
    {
        return [
            self::OPERATION_TYPE_FULL => __('Full'),
            self::OPERATION_TYPE_ADD => __('Add'),
            self::OPERATION_TYPE_UPDATE => __('Update'),
            self::OPERATION_TYPE_DELETE => __('Delete')
        ];
    }
}