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
namespace Unbxd\ProductFeed\Model\Eav\Indexer\Full\DataSourceProvider;

use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Full\DataSourceProvider\AbstractAttribute as ResourceModel;
use Unbxd\ProductFeed\Model\Index\Mapping\FieldFactory;
use Unbxd\ProductFeed\Helper\AbstractAttribute as AttributeHelper;
use Unbxd\ProductFeed\Model\Index\Mapping\FieldInterface;

// @TODO - working

/**
 * Class AbstractAttribute
 * @package Unbxd\ProductFeed\Model\Eav\Indexer\Full\DataSourceProvider
 */
abstract class AbstractAttribute
{
    /**
     * Local cache for attributes
     *
     * @var array
     */
    protected $attributesById = [];

    /**
     * Local cache for attribute ids by table
     *
     * @var array
     */
    protected $attributeIdsByTable = [];

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $indexedBackendModels = [
        \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
        \Magento\Eav\Model\Entity\Attribute\Backend\Datetime::class,
        \Magento\Catalog\Model\Attribute\Backend\Startdate::class,
        \Magento\Catalog\Model\Product\Attribute\Backend\Boolean::class,
        \Magento\Eav\Model\Entity\Attribute\Backend\DefaultBackend::class,
        \Magento\Catalog\Model\Product\Attribute\Backend\Weight::class,
        \Magento\Catalog\Model\Product\Attribute\Backend\Price::class,
    ];
	
}
