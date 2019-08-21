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
namespace Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider;

use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Indexer;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Indexer\Table\StrategyInterface;

/**
 * Categories data data source resource model.
 *
 * Class Category
 * @package Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider
 */
class Category extends Indexer
{
    /**
     * Local cache for category data
     *
     * @var array
     */
    private $categoryDataCache = [];

    /**
     * @var null|CategoryAttributeInterface
     */
    private $categoryNameAttribute = null;

    /**
     * @var null|CategoryAttributeInterface
     */
    private $categoryUrlKeyAttribute = null;

    /**
     * @var null|CategoryAttributeInterface
     */
    private $categoryUrlPathAttribute = null;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig = null;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Category constructor.
     * @param ResourceConnection $resource
     * @param StrategyInterface $tableStrategy
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     * @param Config $eavConfig
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ResourceConnection $resource,
        StrategyInterface $tableStrategy,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        Config $eavConfig,
        ObjectManagerInterface $objectManager
    ) {
        $this->eavConfig = $eavConfig;
        $this->objectManager = $objectManager;
        parent::__construct(
            $resource,
            $tableStrategy,
            $storeManager,
            $metadataPool
        );
    }

    /**
     * Load categories data for a list of product ids and a given store.
     *
     * @param $storeId
     * @param $productIds
     * @return array
     * @throws \Exception
     */
    public function loadCategoryData($storeId, $productIds)
    {
        $select = $this->getCategoryProductSelect($productIds, $storeId);
        $categoryData = $this->getConnection()->fetchAll($select);

        $categoryIds = [];
        foreach ($categoryData as $categoryDataRow) {
            $categoryId = isset($categoryDataRow['category_id']) ? (int) $categoryDataRow['category_id'] : '';
            if ($categoryId) {
                $categoryIds[] = $categoryId;
            }
        }

        $storeCategoryData = $this->buildCategoryData(array_unique($categoryIds), $storeId);

        foreach ($categoryData as &$categoryDataRow) {
            if (isset($storeCategoryData[$categoryDataRow['category_id']])) {
                $key = (int) $categoryDataRow['category_id'];
                if (empty($storeCategoryData[$key])) {
                    continue;
                }

                $categoryDataRow['name'] = $storeCategoryData[$key]['name'];
                $categoryDataRow['url_key'] = $storeCategoryData[$key]['url_key'];
                $categoryDataRow['url_path'] = $storeCategoryData[$key]['url_path'];
            }
        }

        return $categoryData;
    }

    /**
     * Prepare indexed data select.
     *
     * @param array $productIds
     * @param integer $storeId
     * @return \Zend_Db_Select
     */
    protected function getCategoryProductSelect($productIds, $storeId)
    {
        $select = $this->getConnection()->select()
            ->from(['cpi' => $this->getTable($this->getCatalogCategoryProductIndexTable($storeId))])
            ->where('cpi.store_id = ?', $storeId)
            ->where('cpi.product_id IN(?)', $productIds)
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(['category_id', 'product_id', 'is_parent']);

        return $select;
    }

    /**
     * Retrieve attribute id by entity type code and attribute code
     *
     * @param string $entityType
     * @param string $code
     * @return int
     * @deprecated since 1.0.20
     */
    public function getAttributeId($entityType, $code)
    {
        $connection = $this->getConnection();
        $bind = [':entity_type_code' => $entityType, ':attribute_code' => $code];
        $select = $connection->select()->from(
            ['a' => $this->getTable('eav_attribute')],
            ['a.attribute_id']
        )->join(
            ['t' => $this->getTable('eav_entity_type')],
            'a.entity_type_id = t.entity_type_id',
            []
        )->where(
            't.entity_type_code = :entity_type_code'
        )->where(
            'a.attribute_code = :attribute_code'
        );

        return $connection->fetchOne($select, $bind);
    }

    /**
     * Prepare category indexed data.
     *
     * @param $productIds
     * @param $storeId
     * @return array
     * @throws \Exception
     * @deprecated since 1.0.20
     */
    protected function getProductCategories($productIds, $storeId)
    {
        $select = $this->getConnection()->select()
            ->from(['cpi' => $this->getTable($this->getCatalogCategoryProductIndexTable($storeId))])
            ->where('cpi.store_id = ?', $storeId)
            ->where('cpi.product_id IN(?)', $productIds)
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(['category_id', 'product_id'], 'cpi');

        $categoryIds = $this->getConnection()->fetchCol($select);

        $select->useStraightJoin(true)
            ->join([
                'cce' => $this->getTable('catalog_category_entity')],
                'cce.entity_id = cpi.category_id',
                ['path']
            )->where('cce.level NOT IN (0,1)');

        $relatedData = [];
        $allIds = [];
        foreach ($this->getConnection()->fetchAll($select) as $row) {
            $categoryId = isset($row['category_id']) ? (int) $row['category_id'] : null;
            $productId = isset($row['product_id']) ? (int) $row['product_id'] : null;
            $path = isset($row['path']) ? $row['path'] : null;
            if (!$categoryId || !$productId || !$path) {
                continue;
            }

            // remove root categories from path
            $rootPartPath = sprintf(
                '%s/%s',
                \Magento\Catalog\Model\Category::TREE_ROOT_ID,
                $this->getRootCategoryId($storeId)
            );
            $path = str_replace($rootPartPath, '', $path);
            if ($path) {
                $ids = explode('/', $path);
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        if ($id) {
                            $relatedData[] = [
                                'category_id' => $id,
                                'product_id' => $productId,
                                'related' => in_array($id, $categoryIds)
                            ];

                            if (!in_array($id, $allIds)) {
                                array_push($allIds, $id);
                            }
                        }
                    }
                }
            }
        }

        // data with related product id
        $helperData = array_values(array_unique($relatedData, SORT_REGULAR));

        $allIds = array_unique($allIds);
        sort($allIds);

        $entityType = $this->getEntityMetaData(CategoryInterface::class)->getEavEntityType();
        $displayModeAttributeId = $this->getAttributeId($entityType,'display_mode');
        $nameAttributeId = $this->getAttributeId($entityType,'name');
        $urlKeyAttributeId = $this->getAttributeId($entityType,'url_key');
        $urlPathAttributeId = $this->getAttributeId($entityType,'url_path');

        $select = $this->getConnection()->select()
            ->from(['ccev' => $this->getTable('catalog_category_entity_varchar')])
            ->where('ccev.entity_id IN (?)', $allIds)
            ->where('ccev.attribute_id <> ?', $displayModeAttributeId)
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns('attribute_id', 'ccev')
            ->columns('ccev.entity_id AS category_id', 'ccev')
            ->columns('value', 'ccev');

        $result = [];
        foreach ($this->getConnection()->fetchAll($select) as $key => $row) {
            $attributeId = isset($row['attribute_id']) ? (int) $row['attribute_id'] : null;
            $categoryId = isset($row['category_id']) ? (int) $row['category_id'] : null;
            $value = isset($row['value']) ? (string) $row['value'] : null;
            if (!$attributeId || !$categoryId || !$value) {
                continue;
            }

            foreach ($helperData as $data) {
                if (isset($data['category_id']) && ($data['category_id'] == $categoryId)) {
                    if ($attributeId == $nameAttributeId) {
                        $key = 'name';
                    }
                    if ($attributeId == $urlKeyAttributeId) {
                        $key = 'url_key';
                    }
                    if ($attributeId == $urlPathAttributeId) {
                        $key = 'url_path';
                    }
                    $result[] = [
                        'category_id' => $categoryId,
                        'product_id' => $data['product_id'],
                        'related' => $data['related'],
                        $key => $value
                    ];
                }
            }
        }

        $output = [];
        foreach ($result as $key => $data) {
            $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
            $productId = isset($data['product_id']) ? (int) $data['product_id'] : null;

            $filteredArray = array_filter($result, function($item) use ($categoryId, $productId) {
                return ($item['category_id'] == $categoryId) && ($item['product_id'] == $productId);
            });

            $output[$key] = $data;
            if (!empty($filteredArray)) {
                foreach ($filteredArray as $item) {
                    unset($item['category_id']);
                    unset($item['product_id']);
                    unset($item['related']);
                    $output[$key] = array_merge($output[$key], $item);
                }
            }
        }

        return array_unique($output, SORT_REGULAR);
    }

    /**
     * Access to EAV configuration.
     *
     * @return \Magento\Eav\Model\Config
     */
    protected function getEavConfig()
    {
        return $this->eavConfig;
    }

    /**
     * Returns category name attribute
     *
     * @return CategoryAttributeInterface|\Magento\Eav\Model\Entity\Attribute\AbstractAttribute|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCategoryNameAttribute()
    {
        $this->categoryNameAttribute = $this->eavConfig->getAttribute(
            \Magento\Catalog\Model\Category::ENTITY, 'name'
        );

        return $this->categoryNameAttribute;
    }

    /**
     * Returns category url key attribute
     *
     * @return CategoryAttributeInterface|\Magento\Eav\Model\Entity\Attribute\AbstractAttribute|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCategoryUrlKeyAttribute()
    {
        $this->categoryUrlKeyAttribute = $this->eavConfig->getAttribute(
            \Magento\Catalog\Model\Category::ENTITY, 'url_key'
        );

        return $this->categoryUrlKeyAttribute;
    }

    /**
     * Returns category url path attribute
     *
     * @return CategoryAttributeInterface|\Magento\Eav\Model\Entity\Attribute\AbstractAttribute|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCategoryUrlPathAttribute()
    {
        $this->categoryUrlPathAttribute = $this->eavConfig->getAttribute(
            \Magento\Catalog\Model\Category::ENTITY, 'url_path'
        );

        return $this->categoryUrlPathAttribute;
    }

    /**
     * Get category product index table name.
     *
     * @param $storeId
     * @return string
     */
    protected function getCatalogCategoryProductIndexTable($storeId)
    {
        // init table name as legacy table name.
        $indexTable = $this->getTable('catalog_category_product_index');
        try {
            // retrieve table name for the current store Id from the TableMaintainer.
            // class TableMaintainer encapsulate logic of work with tables per store in related indexer
            $tableMaintainer = $this->objectManager->get(
                \Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer::class
            );
            $indexTable = $tableMaintainer->getMainTable($storeId);
        } catch (\Exception $exception) {
            // occurs in Magento version where TableMaintainer is not implemented, will default to legacy table.
        }

        return $indexTable;
    }

    /**
     * Add some categories data into the cache of categories.
     *
     * @param $categoryIds
     * @param $storeId
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function buildCategoryData($categoryIds, $storeId)
    {
        $loadCategoryIds = $categoryIds;

        if (isset($this->categoryDataCache[$storeId])) {
            $loadCategoryIds = array_diff($categoryIds, array_keys($this->categoryDataCache[$storeId]));
        }

        $loadCategoryIds = array_map('intval', $loadCategoryIds);
        if (!empty($loadCategoryIds)) {
            $select = $this->prepareCategoryDataSelect($loadCategoryIds, $storeId);
            $entityIdField = $this->getEntityMetaData(CategoryInterface::class)->getIdentifierField();

            foreach ($this->getConnection()->fetchAll($select) as $row) {
                $categoryId = isset($row[$entityIdField]) ? (int) $row[$entityIdField] : false;
                if (!$categoryId) {
                    continue;
                }

                $this->categoryDataCache[$storeId][$categoryId] = $row;
            }
        }

        return isset($this->categoryDataCache[$storeId]) ? $this->categoryDataCache[$storeId] : [];
    }

    /**
     * Prepare SQL query to retrieve category data
     *
     * @param $loadCategoryIds
     * @param $storeId
     * @return \Magento\Framework\DB\Select
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareCategoryDataSelect($loadCategoryIds, $storeId)
    {
        $rootCategoryId = (int) $this->storeManager->getStore($storeId)->getRootCategoryId();
        $this->categoryDataCache[$storeId][$rootCategoryId] = '';

        $entityIdField = $this->getEntityMetaData(CategoryInterface::class)->getIdentifierField();
        $linkField = $this->getEntityMetaData(CategoryInterface::class)->getLinkField();
        $nameAttr = $this->getCategoryNameAttribute();
        $urlKeyAttr = $this->getCategoryUrlKeyAttribute();
        $urlPathAttr = $this->getCategoryUrlPathAttribute();

        $select = $this->connection->select();

        $conditionsName = [
            "cat.{$linkField} = name.{$linkField}",
            "name.attribute_id = " . (int) $nameAttr->getAttributeId()
        ];
        $joinConditionsName = new \Zend_Db_Expr(implode(" AND ", $conditionsName));

        $conditionsUrlKey = [
            "cat.{$linkField} = url_key.{$linkField}",
            "url_key.attribute_id = " . (int) $urlKeyAttr->getAttributeId()
        ];
        $joinConditionsUrlKey = new \Zend_Db_Expr(implode(" AND ", $conditionsUrlKey));

        $conditionsUrlPath = [
            "cat.{$linkField} = url_path.{$linkField}",
            "url_path.attribute_id = " . (int) $urlPathAttr->getAttributeId()
        ];
        $joinConditionsUrlPath = new \Zend_Db_Expr(implode(" AND ", $conditionsUrlPath));

        $select->distinct(
            true
        )->from(
            ['cat' => $this->getEntityMetaData(CategoryInterface::class)->getEntityTable()],
            [
                $entityIdField,
                'name.value AS name',
                'url_key.value AS url_key',
                'url_path.value AS url_path'
            ]
        )->joinLeft(['name' => $nameAttr->getBackendTable()], $joinConditionsName, [])
            ->joinLeft(['url_key' => $urlKeyAttr->getBackendTable()], $joinConditionsUrlKey, [])
            ->joinLeft(['url_path' => $urlPathAttr->getBackendTable()], $joinConditionsUrlPath, [])
            ->where("cat.$entityIdField != ?", $rootCategoryId)
            ->where("cat.$entityIdField IN (?)", $loadCategoryIds);

        return $select;
    }
}