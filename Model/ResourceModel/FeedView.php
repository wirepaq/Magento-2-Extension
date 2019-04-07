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
namespace Unbxd\ProductFeed\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\AbstractModel;

/**
 * Feed view resource model
 *
 * Class FeedView
 * @package Unbxd\ProductFeed\Model\ResourceModel
 */
class FeedView extends AbstractDb
{
    /**
     * Synchronization constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param Snapshot $entitySnapshot
     * @param RelationComposite $entityRelationComposite
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        $connectionName = null
    ) {
        parent::__construct(
            $context,
            $entitySnapshot,
            $entityRelationComposite,
            $connectionName
        );
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('unbxd_productfeed_feed_view', 'id');
    }

    /**
     * Perform actions before object delete
     *
     * @param AbstractModel $model
     * @return AbstractDb
     */
    protected function _beforeDelete(AbstractModel $model)
    {
        return parent::_beforeDelete($model);
    }

    /**
     * Perform actions after object delete
     *
     * @param AbstractModel $model
     * @return AbstractDb
     */
    protected function _afterDelete(AbstractModel $model)
    {
        return parent::_afterDelete($model);
    }

    /**
     * Perform actions before object save
     *
     * @param AbstractModel $model
     * @return AbstractDb
     */
    protected function _beforeSave(AbstractModel $model)
    {
        return parent::_beforeSave($model);
    }

    /**
     * Perform actions after object save
     *
     * @param AbstractModel $model
     * @return AbstractDb
     */
    protected function _afterSave(AbstractModel $model)
    {
        return parent::_afterSave($model);
    }
}