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
namespace Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\Action;

use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Indexer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\Table\StrategyInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * Unbxd product full indexer resource model.
 *
 * Class Full
 * @package Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\Action
 */
class Full extends Indexer
{
    /**
     * Supported product types
     *
     * @var array
     */
    private $supportedTypes = [
        \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
        \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE
    ];

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Full constructor.
     * @param ResourceConnection $resource
     * @param StrategyInterface $tableStrategy
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ResourceConnection $resource,
        StrategyInterface $tableStrategy,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct(
            $resource,
            $tableStrategy,
            $storeManager,
            $metadataPool
        );
        $this->objectManager = $objectManager;
    }

    /**
     * Load a bulk of product data.
     *
     * @param $storeId
     * @param array $productIds
     * @param int $fromId
     * @param null $limit
     * @return array
     */
    public function getProducts($storeId, $productIds = [], $fromId = 0, $limit = null)
    {
        $select = $this->getConnection()->select()
            ->from(['e' => $this->getTable('catalog_product_entity')]);

//        $this->addCollectionFilters($select, $storeId);

        if (!empty($productIds)) {
            $select->where('e.entity_id IN (?)', $productIds);
        }

        if ($limit) {
            $select->limit($limit);
        }

        $select->where('e.entity_id > ?', $fromId);
        $select->where('e.type_id IN (?)', $this->supportedTypes);
        $select->order('e.entity_id');

        return $this->connection->fetchAll($select);
    }

    /**
     * Retrieve products relations by childrens
     *
     * @param $childrenIds
     * @return array
     * @throws \Exception
     */
    public function getRelationsByChild($childrenIds)
    {
        $metadata = $this->getEntityMetaData(\Magento\Catalog\Api\Data\ProductInterface::class);
        $entityTable = $this->getTable($metadata->getEntityTable());
        $relationTable = $this->getTable('catalog_product_relation');
        $joinCondition = sprintf('relation.parent_id = entity.%s', $metadata->getLinkField());

        $select = $this->getConnection()->select()
            ->from(['relation' => $relationTable], [])
            ->join(['entity' => $entityTable], $joinCondition, [$metadata->getIdentifierField()])
            ->where('child_id IN (?)', array_map('intval', $childrenIds));

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Filter collection by different conditions (eq.: visibility, status)
     *
     * @param $select
     * @param $storeId
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function addCollectionFilters($select, $storeId)
    {
        $this->addIsVisibleInStoreFilter($select, $storeId);
        $this->addStatusFilter($select, $storeId);

        return $this;
    }

    /**
     * Filter the select to append only product visible into the catalog or search into the index.
     *
     * @param $select
     * @param $storeId
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function addIsVisibleInStoreFilter($select, $storeId)
    {
        $rootCategoryId = $this->getRootCategoryId($storeId);
        $indexTable = $this->getCatalogCategoryProductIndexTable($storeId);

        $visibilityJoinCond = $this->getConnection()->quoteInto(
            'visibility.product_id = e.entity_id AND visibility.store_id = ?',
            $storeId
        );

        $select->useStraightJoin(true)
            ->join(['visibility' => $indexTable], $visibilityJoinCond, ['visibility'])
            ->where('visibility.category_id = ?', (int) $rootCategoryId);

        return $this;
    }

    /**
     * Filter the select to append only enabled product into the index.
     *
     * @param $select
     * @param $storeId
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function addStatusFilter($select, $storeId)
    {
        $relatedTable = $this->getTable('catalog_product_entity_int');

        $bind = [];
        $bind = ['status' => 'status'];
        $statusAttributeIdSelect = $this->getConnection()->select()
            ->from(['attribute' => $this->getTable('eav_attribute')], ['attribute_id'])
            ->where('attribute.entity_type_id = 4') // 4 - catalog_product
            ->where('attribute.attribute_code = :status');

        $statusAttributeId = $this->getConnection()->fetchOne($statusAttributeIdSelect, $bind);

        $statusJoinCond = $this->getConnection()->quoteInto(
            'status.entity_id = e.entity_id AND status.store_id = ?',
            $storeId
        );

        $select->useStraightJoin(true)
            ->join(['status' => $relatedTable], $statusJoinCond, ['value AS status'])
            ->where('status.attribute_id = ?', (int) $statusAttributeId)
            ->where('status.value = ?', Status::STATUS_ENABLED);

        return $this;
    }

    /**
     * Retrieve product index table
     *
     * @param $storeId
     * @return string
     */
    private function getCatalogCategoryProductIndexTable($storeId)
    {
        // init table name as legacy table name
        $indexTable = $this->getTable('catalog_category_product_index');

        try {
            // try to retrieve table name for the current store Id from the TableMaintainer.
            // class TableMaintainer encapsulate logic of work with tables per store in related indexer
            $tableMaintainer = $this->objectManager->get(
                \Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer::class
            );
            $indexTable = $tableMaintainer->getMainTable($storeId);
        } catch (\Exception $exception) {
            // occurs in magento version where TableMaintainer is not implemented. Will default to legacy table.
        }

        return $indexTable;
    }
}