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
namespace Unbxd\ProductFeed\Controller\Adminhtml\Feed\View;

use Unbxd\ProductFeed\Controller\Adminhtml\FeedView;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Index
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed\View
 */
class Index extends FeedView
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
        $resultPage->getConfig()->getTitle()->prepend(__('Product Feed Log View'));
        return $resultPage;
    }
}