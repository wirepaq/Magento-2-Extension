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
namespace Unbxd\ProductFeed\Model;

use Magento\Framework\Model\AbstractModel;
use Unbxd\ProductFeed\Api\Data\IndexingQueueInterface;
use Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue as IndexingQueueResourceModel;

/**
 * Indexing queue model
 *
 * Class IndexingQueue
 * @package Unbxd\ProductFeed\Model
 */
class IndexingQueue extends AbstractModel implements IndexingQueueInterface
{
    /**#@+
     * Queue's Statuses
     */
    const STATUS_PENDING = 1;
    const STATUS_RUNNING = 2;
    const STATUS_COMPLETE = 3;
    const STATUS_ERROR = 4;
    const STATUS_HOLD = 5;
    /**#@-*/

    /**#@+
     * Queue's action types
     */
    const TYPE_REINDEX_ROW = 1; // product update/delete (e.q. - add new attribute, change attribute value)
    const TYPE_REINDEX_LIST = 2; // product mass update
    const TYPE_REINDEX_FULL = 3; // full catalog
    /**#@-*/

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(IndexingQueueResourceModel::class);
    }

    /**
     * Prepare queue's statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_RUNNING => __('Running'),
            self::STATUS_COMPLETE => __('Complete'),
            self::STATUS_ERROR => __('Error'),
            self::STATUS_HOLD => __('Hold')
        ];
    }

    /**
     * Prepare queue's action types.
     *
     * @return array
     */
    public function getAvailableActionTypes()
    {
        return [
            self::TYPE_REINDEX_ROW => __('Row Reindex'),
            self::TYPE_REINDEX_LIST => __('List Reindex'),
            self::TYPE_REINDEX_FULL => __('Full Reindex'),
        ];
    }

    /**
     * Retrieve queue id
     *
     * @return int
     */
    public function getId()
    {
        return $this->getData(self::QUEUE_ID);
    }

    /**
     * Retrieve store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * Retrieve created at time
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Retrieve started at time
     *
     * @return string
     */
    public function getStartedAt()
    {
        return $this->getData(self::STARTED_AT);
    }

    /**
     * Retrieve finished at time
     *
     * @return string
     */
    public function getFinishedAt()
    {
        return $this->getData(self::FINISHED_AT);
    }

    /**
     * Is active
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * Retrieve execution time
     *
     * @return string
     */
    public function getExecutionTime()
    {
        return $this->getData(self::EXECUTION_TIME);
    }

    /**
     * Retrieve data for processing
     *
     * @return string
     */
    public function getDataForProcessing()
    {
        return $this->getData(self::DATA_FOR_PROCESSING);
    }

    /**
     * Retrieve number of entities
     *
     * @return int
     */
    public function getNumberOfEntities()
    {
        return $this->getData(self::NUMBER_OF_ENTITIES);
    }

    /**
     * Retrieve action type
     *
     * @return string
     */
    public function getActionType()
    {
        return $this->getData(self::ACTION_TYPE);
    }

    /**
     * Retrieve status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Retrieve additional information
     *
     * @return string
     */
    public function getAdditionalInformation()
    {
        return $this->getData(self::ADDITIONAL_INFORMATION);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return IndexingQueueInterface
     */
    public function setId($id)
    {
        return $this->setData(self::QUEUE_ID, $id);
    }

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return IndexingQueueInterface
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return IndexingQueueInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Set started at
     *
     * @param string $startedAt
     * @return IndexingQueueInterface
     */
    public function setStartedAt($startedAt)
    {
        return $this->setData(self::FINISHED_AT, $startedAt);
    }

    /**
     * Set finished at
     *
     * @param string $finishedAt
     * @return IndexingQueueInterface
     */
    public function setFinishedAt($finishedAt)
    {
        return $this->setData(self::FINISHED_AT, $finishedAt);
    }

    /**
     * Set is active
     *
     * @param bool|int $isActive
     * @return IndexingQueueInterface
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * Set execution time
     *
     * @param string $executionTime
     * @return IndexingQueueInterface
     */
    public function setExecutionTime($executionTime)
    {
        return $this->setData(self::EXECUTION_TIME, $executionTime);
    }

    /**
     * Set data for processing
     *
     * @param string $data
     * @return IndexingQueueInterface
     */
    public function setDataForProcessing($data)
    {
        return $this->setData(self::DATA_FOR_PROCESSING, $data);
    }

    /**
     * Set number of entities
     *
     * @param int $numberOfEntities
     * @return IndexingQueueInterface
     */
    public function setNumberOfEntities($numberOfEntities)
    {
        return $this->setData(self::NUMBER_OF_ENTITIES, $numberOfEntities);
    }

    /**
     * Set action type
     *
     * @param string $actionType
     * @return IndexingQueueInterface
     */
    public function setActionType($actionType)
    {
        return $this->setData(self::ACTION_TYPE, $actionType);
    }

    /**
     * Set status
     *
     * @param string $status
     * @return IndexingQueueInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Set additional information
     *
     * @param string $additionalInformation
     * @return IndexingQueueInterface
     */
    public function setAdditionalInformation($additionalInformation)
    {
        return $this->setData(self::ADDITIONAL_INFORMATION, $additionalInformation);
    }
}