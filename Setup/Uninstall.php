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

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;

/**
 * Class Uninstall
 * @package Unbxd\ProductFeed\Setup
 */
class Uninstall implements UninstallInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Uninstall constructor.
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        // remove related configuration fields
        $select = $setup->getConnection()->select()->from(
            $setup->getTable('core_config_data'),
            ['path']
        )->where(
            'path LIKE ?',
            '%unbxd%'
        );

        $configRecords = $setup->getConnection()->fetchAll($select);
        foreach ($configRecords as $path => $value) {
            $this->config->deleteConfig($path);
        }

        // collect related tables
        $tables = [
            $setup->getTable('unbxd_productfeed_indexing_queue'),
            $setup->getTable('unbxd_productfeed_feed_view')
        ];

        // disable checks foreign key before delete action
        $installer->getConnection()->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            if ($setup->tableExists($table)) {
                $setup->getConnection()->dropTable($table);
            }
        }
        // enable checks foreign key before delete action
        $installer->getConnection()->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}