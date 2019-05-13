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
namespace Unbxd\ProductFeed\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory as EavAttributeFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;

/**
 * Product feed attributes helper.
 *
 * Class AttributeHelper
 * @package Unbxd\ProductFeed\Helper
 */
class AttributeHelper extends AbstractHelper
{
    /**
     * @var string
     */
    const OPTION_TEXT_PREFIX = 'option_text';

    /**
     * @var EavAttributeFactory
     */
    private $attributeFactory;

    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var array
     */
    private $storeAttributes = [];

    /**
     * @var array
     */
    private $attributeOptionTextCache = [];

    /**
     * @var array
     */
    private $attributeMappers = [];

    /**
     * AttributeHelper constructor.
     * @param Context $context
     * @param EavAttributeFactory $attributeFactory
     * @param AttributeCollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        EavAttributeFactory $attributeFactory,
        AttributeCollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->attributeFactory = $attributeFactory;
        $this->attributeCollectionFactory = $collectionFactory;
    }

    /**
     * Retrieve a new product attribute collection.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function getAttributeCollection()
    {
        return $this->attributeCollectionFactory->create();
    }

    /**
     * Parse attribute to get mapping field creation parameters.
     *
     * @param AttributeInterface $attribute Product attribute.
     * @return array
     */
    public function getMappingFieldOptions(AttributeInterface $attribute)
    {
        $options = [
            'is_searchable' => $attribute->getIsSearchable(),
            'is_filterable' => $attribute->getIsFilterable() || $attribute->getIsFilterableInSearch(),
            'search_weight' => $attribute->getSearchWeight(),
            'is_used_for_sort_by' => $attribute->getUsedForSortBy(),
        ];

        if ($attribute->getIsUsedInSpellcheck()) {
            $options['is_used_in_spellcheck'] = true;
        }

        if ($attribute->getIsDisplayedInAutocomplete()) {
            $options['is_filterable'] = true;
        }

        return $options;
    }

    /**
     * Get mapping field type for an attribute.
     *
     * @param AttributeInterface $attribute Product attribute.
     * @return string
     */
    public function getFieldType(AttributeInterface $attribute)
    {
        $type = FeedConfig::FIELD_TYPE_TEXT;

        if ($attribute->getSourceModel() == 'Magento\Eav\Model\Entity\Attribute\Source\Boolean') {
            $type = FeedConfig::FIELD_TYPE_BOOL;
        } elseif ($attribute->getBackendType() == 'int') {
            $type = FeedConfig::FIELD_TYPE_NUMBER;
        } elseif ($attribute->getBackendType() == 'varchar') {
            $type = FeedConfig::FIELD_TYPE_LONGTEXT;
        } elseif (
            $attribute->getBackendType() == 'decimal'
            || $attribute->getFrontendClass() == 'validate-digits'
            || $attribute->getFrontendClass() == 'validate-number'
        ) {
            $type = FeedConfig::FIELD_TYPE_DECIMAL;
        } elseif ($attribute->getBackendType() == 'datetime') {
            $type = FeedConfig::FIELD_TYPE_DATE;
        } elseif ($attribute->usesSource()) {
            $type = $attribute->getSourceModel()
                ? FeedConfig::FIELD_TYPE_NUMBER
                : FeedConfig::FIELD_TYPE_DECIMAL;
        }

        return $type;
    }

    /**
     * Check if field is multivalued.
     *
     * @param AttributeInterface $attribute Product attribute.
     * @return bool
     */
    public function isFieldMultivalued(AttributeInterface $attribute)
    {
        return (bool) ($attribute->getBackendType() == 'varchar') && ($attribute->getFrontendInput() == 'multiselect');
    }

    /**
     * Parse attribute raw value (as saved in the database) to prepare the indexed value.
     * For attribute using options the option value is also added to the result which contains two keys :
     *   - one is "attribute_code" and contained the option id(s)
     *   - the other one is "option_text_attribute_code" and contained option value(s)
     * All value are transformed into arrays to have a more simple management of
     * multivalued attributes merging on composite products).
     *
     * @param AttributeInterface $attribute
     * @param $storeId
     * @param $value
     * @return array
     */
    public function prepareIndexValue(AttributeInterface $attribute, $storeId, $value)
    {
        $attributeCode = $attribute->getAttributeCode();
        $values = [];

        $mapperKey = 'simple_' . $attribute->getId();

        if (!isset($this->attributeMappers[$mapperKey])) {
            $this->attributeMappers[$mapperKey] = function ($value) use ($attribute) {
                return $this->prepareSimpleIndexAttributeValue($attribute, $value);
            };
        }

        if ($attribute->usesSource() && !is_array($value)) {
            $value = explode(',', $value);
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $value = array_map($this->attributeMappers[$mapperKey], $value);
        $value = array_filter($value);
        $value = array_values($value);
        $values[$attributeCode] = $value;

        if ($attribute->usesSource()) {
            $optionTextFieldName = $this->getOptionTextFieldName($attributeCode);
            $optionTextValues = $this->getIndexOptionsText($attribute, $storeId, $value);
            // filter empty values, not using array_filter here because it could remove "0" string from values.
            $optionTextValues = array_diff(array_map('trim', $optionTextValues), ['', null, false]);
            $optionTextValues = array_values($optionTextValues);
            $values[$optionTextFieldName] = $optionTextValues;
        }

        return array_filter($values);
    }

    /**
     * Transform an array of options ids into an arrays of option values for attribute that uses a source.
     * Values are localized for a store id.
     *
     * @param AttributeInterface $attribute
     * @param $storeId
     * @param array $optionIds
     * @return array
     */
    public function getIndexOptionsText(AttributeInterface $attribute, $storeId, array $optionIds)
    {
        $mapperKey = sprintf("options_%s_%s", $attribute->getId(), $storeId);

        if (!isset($this->attributeMappers[$mapperKey])) {
            $this->attributeMappers[$mapperKey] = function ($optionId) use ($attribute, $storeId) {
                return $this->getIndexOptionText($attribute, $storeId, $optionId);
            };
        }

        $optionValues = array_map($this->attributeMappers[$mapperKey], $optionIds);

        return $optionValues;
    }

    /**
     * Transform a field name into it's option value field form.
     *
     * @param $fieldName
     * @return string
     */
    public function getOptionTextFieldName($fieldName)
    {
        return sprintf("%s_%s", self::OPTION_TEXT_PREFIX, $fieldName);
    }

    /**
     * Transform an options id into an array of option value for attribute that uses a source.
     * Value is localized for a store id.
     *
     * @param AttributeInterface $attribute
     * @param $storeId
     * @param $optionId
     * @return mixed
     */
    public function getIndexOptionText(AttributeInterface $attribute, $storeId, $optionId)
    {
        $attribute = $this->getAttributeByStore($attribute, $storeId);
        $attributeId = $attribute->getAttributeId();

        if (
            !isset($this->attributeOptionTextCache[$storeId])
            || !isset($this->attributeOptionTextCache[$storeId][$attributeId])
        ) {
            $this->attributeOptionTextCache[$storeId][$attributeId] = [];
        }

        if (!isset($this->attributeOptionTextCache[$storeId][$attributeId][$optionId])) {
            $optionValue = $attribute->getSource()->getIndexOptionText($optionId);
            if ($this->getFieldType($attribute) == FeedConfig::FIELD_TYPE_BOOL) {
                $optionValue = $attribute->getStoreLabel($storeId);
            }
            $this->attributeOptionTextCache[$storeId][$attributeId][$optionId] = $optionValue;
        }

        return $this->attributeOptionTextCache[$storeId][$attributeId][$optionId];
    }

    /**
     * Returns field use for filtering for an attribute.
     *
     * @param AttributeInterface $attribute.
     * @return string
     */
    public function getFilterField(AttributeInterface $attribute)
    {
        $field = $attribute->getAttributeCode();

        if ($attribute->usesSource()) {
            $field = $this->getOptionTextFieldName($field);
        }

        return $field;
    }

    /**
     * Ensure types of numerical values is correct before indexing.
     *
     * @param AttributeInterface $attribute
     * @param $value
     * @return bool|float|int
     */
    private function prepareSimpleIndexAttributeValue(AttributeInterface $attribute, $value)
    {
        if ($this->getFieldType($attribute) == FeedConfig::FIELD_TYPE_BOOL) {
            $value = boolval($value);
        } elseif ($attribute->getBackendType() == 'decimal') {
            $value = floatval($value);
        } elseif ($attribute->getBackendType() == 'int') {
            $value = intval($value);
        }

        return $value;
    }

    /**
     * Load the localized version of an attribute.
     * This code uses a local cache to ensure correct performance during indexing.
     *
     * @param $attribute
     * @param $storeId
     * @return mixed
     */
    private function getAttributeByStore($attribute, $storeId)
    {
        $attributeId = $this->getAttributeId($attribute);

        if (!isset($this->storeAttributes[$storeId]) || !isset($this->storeAttributes[$storeId][$attributeId])) {
            /**
             * @var EavAttributeInterface
             */
            $storeAttribute = $this->attributeFactory->create();
            $storeAttribute->load($attributeId)->setStoreId($storeId);
            $this->storeAttributes[$storeId][$attributeId] = $storeAttribute;
        }

        return $this->storeAttributes[$storeId][$attributeId];
    }

    /**
     * This util method is used to ensure the attribute is an integer and uses it's id if it is an object.
     *
     * @param $attribute
     * @return mixed
     */
    private function getAttributeId($attribute)
    {
        $attributeId = $attribute;

        if (is_object($attribute)) {
            $attributeId = $attribute->getAttributeId();
        }

        return $attributeId;
    }
}
