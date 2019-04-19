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
     * Local cache for category names
     *
     * @var array
     */
    private $categoryNameCache = [];

    /**
     * @var null|CategoryAttributeInterface
     */
    private $categoryNameAttribute = null;

    /**
     * @var null|CategoryAttributeInterface
     */
    private $useNameInSearchAttribute = null;

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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadCategoryData($storeId, $productIds)
    {
        $select = $this->getCategoryProductSelect($productIds, $storeId);
        $categoryData = $this->getConnection()->fetchAll($select);

        $categoryIds = [];
        foreach ($categoryData as $categoryDataRow) {
            $categoryIds[] = $categoryDataRow['category_id'];
        }

//        $storeCategoryName = $this->loadCategoryNames(array_unique($categoryIds), $storeId);
        $storeCategoryName = [];

        foreach ($categoryData as &$categoryDataRow) {
            $categoryDataRow['name'] = '';
            if (isset($storeCategoryName[(int) $categoryDataRow['category_id']])) {
                $categoryDataRow['name'] = $storeCategoryName[(int) $categoryDataRow['category_id']];
            }
        }

        return $categoryData;
    }

    /**
     * Prepare indexed data select.
     *
     * @param array   $productIds Product ids.
     * @param integer $storeId    Store id.
     *
     * @return \Zend_Db_Select
     */
    protected function getCategoryProductSelect($productIds, $storeId)
    {
        $select = $this->getConnection()->select()
            ->from(['cpi' => $this->getTable($this->getCatalogCategoryProductIndexTable($storeId))])
            ->where('cpi.store_id = ?', $storeId)
            ->where('cpi.product_id IN(?)', $productIds);

        return $select;
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
     * Returns category "use name in product search" attribute
     *
     * @return CategoryAttributeInterface|\Magento\Eav\Model\Entity\Attribute\AbstractAttribute|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getUseNameInSearchAttribute()
    {
        $this->useNameInSearchAttribute = $this->eavConfig
            ->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'use_name_in_product_search');

        return $this->useNameInSearchAttribute;
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
     * Add some categories name into the cache of names of categories.
     *
     * @param $categoryIds
     * @param $storeId
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function loadCategoryNames($categoryIds, $storeId)
    {
        $loadCategoryIds = $categoryIds;

        if (isset($this->categoryNameCache[$storeId])) {
            $loadCategoryIds = array_diff($categoryIds, array_keys($this->categoryNameCache[$storeId]));
        }

        $loadCategoryIds = array_map('intval', $loadCategoryIds);
        $useNameAttribute = $this->getUseNameInSearchAttribute();

//        if (!empty($loadCategoryIds) && $useNameAttribute && $useNameAttribute->getId()) {
        if (!empty($loadCategoryIds)) {
            $select = $this->prepareCategoryNameSelect($loadCategoryIds, $storeId);
            $entityIdField = $this->getEntityMetaData(CategoryInterface::class)->getIdentifierField();

            foreach ($this->getConnection()->fetchAll($select) as $row) {
                $categoryId = (int) $row[$entityIdField];
                $this->categoryNameCache[$storeId][$categoryId] = '';
                if ((bool) $row['use_name']) {
                    $this->categoryNameCache[$storeId][$categoryId] = $row['name'];
                }
            }
        }

        return isset($this->categoryNameCache[$storeId]) ? $this->categoryNameCache[$storeId] : [];
    }

    /**
     * Prepare SQL query to retrieve category names
     *
     * @param $loadCategoryIds
     * @param $storeId
     * @return \Magento\Framework\DB\Select
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareCategoryNameSelect($loadCategoryIds, $storeId)
    {
        $rootCategoryId = (int) $this->storeManager->getStore($storeId)->getRootCategoryId();
        $this->categoryNameCache[$storeId][$rootCategoryId] = '';

        $nameAttr = $this->getCategoryNameAttribute();
        $useNameAttr = $this->getUseNameInSearchAttribute();
        $entityIdField = $this->getEntityMetaData(CategoryInterface::class)->getIdentifierField();
        $linkField = $this->getEntityMetaData(CategoryInterface::class)->getLinkField();
        $select = $this->connection->select();

        $conditions = [
            "cat.{$linkField} = default_value.{$linkField}",
            "default_value.store_id=0",
            "default_value.attribute_id = " . (int) $nameAttr->getAttributeId(),
        ];

        $joinCondition = new \Zend_Db_Expr(implode(" AND ", $conditions));
        $select->from(['cat' => $this->getEntityMetaData(CategoryInterface::class)->getEntityTable()], [$entityIdField])
            ->joinLeft(['default_value' => $nameAttr->getBackendTable()], $joinCondition, [])
            ->where("cat.$entityIdField != ?", $rootCategoryId)
            ->where("cat.$entityIdField IN (?)", $loadCategoryIds);

        // Join to check for use_name_in_product_search.
        $joinUseNameCond = sprintf(
            "default_value.$linkField = use_name_default_value.$linkField" .
            " AND use_name_default_value.attribute_id = %d AND use_name_default_value.store_id = %d",
            (int) $useNameAttr->getAttributeId(),
            0
        );
        $select->joinLeft(['use_name_default_value' => $useNameAttr->getBackendTable()], $joinUseNameCond, []);

        if ($this->storeManager->isSingleStoreMode()) {
            $select->columns(['name' => 'default_value.value']);
            $select->columns(['use_name' => 'COALESCE(use_name_default_value.value,1)']);

            return $select;
        }

        // multi store additional join to get scoped name value.
        $joinStoreNameCond = sprintf(
            "cat.$linkField = store_value.$linkField" .
            " AND store_value.attribute_id = %d AND store_value.store_id = %d",
            (int) $nameAttr->getAttributeId(),
            (int) $storeId
        );
        $select->joinLeft(['store_value' => $nameAttr->getBackendTable()], $joinStoreNameCond, [])
            ->columns(['name' => 'COALESCE(store_value.value,default_value.value, "")']);

        // multi store additional join to get scoped "use_name_in_product_search" value.
        $joinUseNameStoreCond = sprintf(
            "cat.$linkField = use_name_store_value.$linkField" .
            " AND use_name_store_value.attribute_id = %d AND use_name_store_value.store_id = %d",
            (int) $useNameAttr->getAttributeId(),
            (int) $storeId
        );
        $select->joinLeft(['use_name_store_value' => $useNameAttr->getBackendTable()], $joinUseNameStoreCond, [])
            ->columns(['use_name' => 'COALESCE(use_name_store_value.value,use_name_default_value.value,1)']);

        return $select;
    }
}