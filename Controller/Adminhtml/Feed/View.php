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
namespace Unbxd\ProductFeed\Controller\Adminhtml\Feed;

use Unbxd\ProductFeed\Controller\Adminhtml\ViewIndex;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Index
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed\View
 */
class View extends ViewIndex
{
    /**
     * Product feed log view
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Unbxd_ProductFeed::productfeed');
        $resultPage->addBreadcrumb(__('Product Feed'), __('Product Feed'));
        $resultPage->addBreadcrumb(__('Log View'), __('Log View'));
        $resultPage->getConfig()->getTitle()->prepend(__('Unbxd | Feed View'));

        return $resultPage;
    }
}