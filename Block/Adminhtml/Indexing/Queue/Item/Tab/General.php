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
namespace Unbxd\ProductFeed\Block\Adminhtml\Indexing\Queue\Item\Tab;

use Unbxd\ProductFeed\Model\IndexingQueue;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class General
 * @package Unbxd\ProductFeed\Block\Adminhtml\Indexing\Queue\Item\Tab
 */
class General extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'Unbxd_ProductFeed::indexing/queue/item/general.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var IndexingQueue
     */
    private $model;

    /**
     * General constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param IndexingQueue $model
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        IndexingQueue $model,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->model = $model;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @param $date
     * @return string
     */
    public function getFormattedDate($date)
    {
        return $this->formatDate(
            $date,
            \IntlDateFormatter::MEDIUM,
            true
        );
    }

    /**
     * Retrieve current queue item instance
     *
     * @return IndexingQueue
     */
    public function getQueueItem()
    {
        return $this->registry->registry('indexing_queue_item');
    }

    /**
     * @param $id
     * @return \Magento\Framework\Phrase
     */
    public function getStatusLabelById($id)
    {
        $availableStatuses = $this->model->getAvailableStatuses();

        return array_key_exists($id, $availableStatuses) ? $availableStatuses[$id] : __('Undefined');
    }

    /**
     * @param $id
     * @return \Magento\Framework\Phrase
     */
    public function getActionLabelById($id)
    {
        $availableActionType = $this->model->getAvailableActionTypes();

        return array_key_exists($id, $availableActionType) ? $availableActionType[$id] : __('Undefined');
    }
}