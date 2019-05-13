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
use Unbxd\ProductFeed\Helper\AttributeHelper as AttributeHelper;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;

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
     * @var AttributeHelper
     */
    protected $attributeHelper;

    /**
     * @var ResourceModel
     */
    protected $resourceModel;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $indexedFields = [];

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

    /**
     * AbstractAttribute constructor.
     * @param ResourceModel $resourceModel
     * @param AttributeHelper $attributeHelper
     * @param array $indexedBackendModels
     */
    public function __construct(
        ResourceModel $resourceModel,
        AttributeHelper $attributeHelper,
        array $indexedBackendModels = []
    ) {
        $this->resourceModel = $resourceModel;
        $this->attributeHelper = $attributeHelper;

        if (is_array($indexedBackendModels) && !empty($indexedBackendModels)) {
            $indexedBackendModels = array_values($indexedBackendModels);
            $this->indexedBackendModels = array_merge($indexedBackendModels, $this->indexedBackendModels);
        }

        $this->initAttributes();
    }

    /**
     * List of fields generated from the attributes list.
     *
     * {@inheritdoc}
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * List of indexed fields generated from the attributes list.
     *
     * {@inheritdoc}
     */
    public function getIndexedFields()
    {
        return $this->indexedFields;
    }

    /**
     * Load default attribute codes from the database.
     *
     * @return array
     */
    protected function loadDefaultAttributeFields()
    {
        return $this->resourceModel->getDefaultAttributeFields();
    }

    /**
     * Load attribute data from the database.
     *
     * @param $storeId
     * @param array $entityIds
     * @param $tableName
     * @param array $attributeIds
     * @return array
     * @throws \Exception
     */
    protected function loadAttributesRawData($storeId, array $entityIds, $tableName, array $attributeIds)
    {
        return $this->resourceModel->getAttributesRawData($storeId, $entityIds, $tableName, $attributeIds);
    }

    /**
     * Init attributes.
     *
     * @return $this
     */
    private function initAttributes()
    {
        $attributeCollection = $this->attributeHelper->getAttributeCollection();
        foreach ($attributeCollection as $attribute) {
            if ($this->canIndexAttribute($attribute)) {
                $attributeId = (int) $attribute->getId();
                $this->attributesById[$attributeId] = $attribute;
                $this->attributeIdsByTable[$attribute->getBackendTable()][] = $attributeId;
                // collect attributes fields, maybe useful in feed operation
                $this->initFields($attribute);
            }
        }

        return $this;
    }

    /**
     * Check if an attribute can be indexed.
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    private function canIndexAttribute(AttributeInterface $attribute)
    {
        $canIndex = ($attribute->getBackendType() != 'static') && ($attribute->getAttributeCode() !== 'price');
        if ($canIndex && $attribute->getBackendModel()) {
            $canIndex = in_array($attribute->getBackendModel(), $this->indexedBackendModels);
        }

        return $canIndex;
    }

    /**
     * Create a mapping field from an attribute.
     *
     * @param AttributeInterface $attribute
     * @return $this
     */
    private function initFields(AttributeInterface $attribute)
    {
        $fieldName = $attribute->getAttributeCode();
        $fieldConfig = $this->attributeHelper->getMappingFieldOptions($attribute);
        $isFieldMultivalued = $this->attributeHelper->isFieldMultivalued($attribute);

        if ($attribute->usesSource()) {
            $optionFieldName = $this->attributeHelper->getOptionTextFieldName($fieldName);
            $fieldType = FeedConfig::FIELD_TYPE_TEXT;
            $fieldOptions = [
                'fieldName' => (string) $fieldName,
                'dataType' => (string) $fieldType,
                'multiValued' => (boolean) $isFieldMultivalued,
            ];
            $this->fields[$optionFieldName] = $fieldOptions;
        }

        $fieldType = $this->attributeHelper->getFieldType($attribute);
        $fieldOptions = [
            'fieldName' => (string) $fieldName,
            'dataType' => (string) $fieldType,
            'multiValued' => (boolean) $isFieldMultivalued,
        ];

        $this->fields[$fieldName] = $fieldOptions;

        return $this;
    }
}
