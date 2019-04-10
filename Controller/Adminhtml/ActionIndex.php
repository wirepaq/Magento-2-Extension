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
namespace Unbxd\ProductFeed\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\CronManager;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Helper\ProductHelper;
use Unbxd\ProductFeed\Model\IndexingQueueFactory;
use Unbxd\ProductFeed\Api\IndexingQueueRepositoryInterface;

/**
 * Class ActionIndex
 * @package Unbxd\ProductFeed\Controller\Adminhtml
 */
abstract class ActionIndex extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var IndexingQueueFactory
     */
    protected $indexingQueueFactory;

    /**
     * @var IndexingQueueRepositoryInterface
     */
    protected $indexingQueueRepository;

    /**
     * @var CronManager
     */
    protected $cronManager;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * ActionIndex constructor.
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param IndexingQueueFactory $indexingQueueFactory
     * @param IndexingQueueRepositoryInterface $indexingQueueRepository
     * @param CronManager $cronManager
     * @param HelperData $helperData
     * @param ProductHelper $productHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        IndexingQueueFactory $indexingQueueFactory,
        IndexingQueueRepositoryInterface $indexingQueueRepository,
        CronManager $cronManager,
        HelperData $helperData,
        ProductHelper $productHelper,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->indexingQueueFactory = $indexingQueueFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(IndexingQueueFactory::class);
        $this->indexingQueueRepository = $indexingQueueRepository
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(IndexingQueueRepositoryInterface::class);
        $this->cronManager = $cronManager;
        $this->helperData = $helperData;
        $this->productHelper = $productHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * @return bool
     */
    protected function _isValidPostRequest()
    {
        $formKeyIsValid = $this->_formKeyValidator->validate($this->getRequest());
        $isAjax = $this->getRequest()->isAjax();
        $isPost = $this->getRequest()->isPost();

        return (bool) ($formKeyIsValid && $isAjax && $isPost);
    }

    /**
     * @param string $store
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStore($store = '')
    {
        return $this->storeManager->getStore($store);
    }
}