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
namespace Unbxd\ProductFeed\Block\Adminhtml\Indexing\Queue\Item\Buttons;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Generic
 * @package Unbxd\ProductFeed\Block\Adminhtml\Indexing\Queue\Item\Buttons
 */
class Generic
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Local cache for queue item id
     *
     * @var int|null
     */
    private $id = null;

    /**
     * Generic constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry
    ) {
        $this->context = $context;
        $this->registry = $registry;
    }

    /**
     * @return int|string|null
     */
    public function getQueueItemId()
    {
        if (!$this->id) {
            $model = $this->registry->registry('indexing_queue_item');
            if ($id = $model->getId()) {
                $this->id = $id;
            }
        }

        return $this->id;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
