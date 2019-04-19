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

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class Delete
 * @package Unbxd\ProductFeed\Block\Adminhtml\Indexing\Queue\Item\Buttons
 */
class Delete extends Generic implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Delete'),
            'class' => 'primary',
            'on_click' => 'deleteConfirm(\'' . __('Delete item?') . '\', \'' . $this->getDeleteUrl() . '\')',
            'sort_order' => 40
        ];
    }

    /**
     * Get URL for button action
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl(
            '*/*/delete',
            [
                'id' => $this->getQueueItemId()
            ]
        );
    }
}
