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
namespace Unbxd\ProductFeed\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class UpgradeSchema
 * @package Unbxd\ProductFeed\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        /**
         * Create table 'unbxd_productfeed_indexing_queue'
         */
        if (!$installer->tableExists('unbxd_productfeed_indexing_queue')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('unbxd_productfeed_indexing_queue')
            )->addColumn(
                'queue_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['identity' => true, 'primary' => true, 'nullable' => false, 'unsigned' => true],
                'Queue Id'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true],
                'Store Id'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Creation Time'
            )->addColumn(
                'started_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => '0000-00-00 00:00:00'],
                'Started Time'
            )->addColumn(
                'finished_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => '0000-00-00 00:00:00'],
                'Finished Time'
            )->addColumn(
                'is_active',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Is Active'
            )->addColumn(
                'execution_time',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Execution Time'
            )->addColumn(
                'affected_entities',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Affected Entities'
            )->addColumn(
                'number_of_entities',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['default' => '0'],
                'Number Of Entities'
            )->addColumn(
                'action_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                [],
                'Action Type'
            )->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                [],
                'Status'
            )->addColumn(
                'additional_information',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Additional Information'
            )->addColumn(
                'system_information',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'System Information'
            )->addIndex(
                $installer->getIdxName('unbxd_productfeed_indexing_queue', ['queue_id']),
                ['queue_id']
            )->addIndex(
                $installer->getIdxName('unbxd_productfeed_indexing_queue', ['status']),
                ['status']
            );

            $installer->getConnection()->createTable($table);
        };

        /**
         * Create table 'unbxd_productfeed_feed_view'
         */
        if (!$installer->tableExists('unbxd_productfeed_feed_view')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('unbxd_productfeed_feed_view')
            )->addColumn(
                'feed_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Feed Id'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true],
                'Store Id'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Creation Time'
            )->addColumn(
                'finished_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => '0000-00-00 00:00:00'],
                'Finished Time'
            )->addColumn(
                'is_active',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Is Active'
            )->addColumn(
                'execution_time',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Execution Time'
            )->addColumn(
                'affected_entities',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Affected Entities'
            )->addColumn(
                'number_of_entities',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['default' => '0'],
                'Number Of Entities'
            )->addColumn(
                'operation_types',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true],
                'Operation Types'
            )->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                [],
                'Status'
            )->addColumn(
                'additional_information',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Additional Information'
            )->addColumn(
                'system_information',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'System Information'
            )->addIndex(
                $installer->getIdxName('unbxd_productfeed_feed_view', ['feed_id']),
                ['feed_id']
            )->addIndex(
                $installer->getIdxName('unbxd_productfeed_feed_view', ['status']),
                ['status']
            )->setComment(
                'Unbxd ProductFeed Synchronization View Table'
            );

            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
