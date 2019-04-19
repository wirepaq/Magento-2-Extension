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
namespace Unbxd\ProductFeed\Block\Adminhtml\LogViewer;

/**
 * Class Toolbar
 * @package Unbxd\ProductFeed\Block\Adminhtml\LogViewer
 */
class Toolbar extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Backend\Block\Widget\Container
     */
    protected function _prepareLayout()
    {
        $this->addButtons();
        return parent::_prepareLayout();
    }

    /**
     * Add button to manage Cloudways service
     *
     * @param $label
     */
    private function addButtons()
    {
        $message = __('Are you sure do you want to clear indexing queue?');
        $this->buttonList->add(
            'clear',
            [
                'label' => __('Clear Queue'),
                'class' => 'primary',
                'onclick' => "confirmSetLocation('{$message}', '{$this->getActionUrl()}')",
            ]
        );
    }

    /**
     * @return string
     */
    public function getActionUrl()
    {
        return $this->getUrl('*/indexing_queue/deleteAll');
    }
}