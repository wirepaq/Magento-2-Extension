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
use Magento\Ui\Component\MassAction\Filter as MassActionFilter;
use Unbxd\ProductFeed\Model\CronManager;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Helper\ProductHelper;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Unbxd\ProductFeed\Model\IndexingQueueFactory;
use Unbxd\ProductFeed\Model\IndexingQueue\Handler as QueueHandler;
use Unbxd\ProductFeed\Api\IndexingQueueRepositoryInterface;
use Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\CollectionFactory as IndexingQueueCollectionFactory;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Model\FeedViewFactory;
use Unbxd\ProductFeed\Api\FeedViewRepositoryInterface;
use Unbxd\ProductFeed\Model\ResourceModel\FeedView\CollectionFactory as FeedViewCollectionFactory;

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
     * @var MassActionFilter
     */
    protected $massActionFilter;

    /**
     * @var QueueHandler
     */
    protected $queueHandler;

    /**
     * @var IndexingQueue
     */
    protected $indexingQueue;

    /**
     * @var IndexingQueueFactory
     */
    protected $indexingQueueFactory;

    /**
     * @var IndexingQueueRepositoryInterface
     */
    protected $indexingQueueRepository;

    /**
     * @var IndexingQueueCollectionFactory
     */
    protected $indexingQueueCollectionFactory;

    /**
     * @var FeedView
     */
    protected $feedView;

    /**
     * @var FeedViewFactory
     */
    protected $feedViewFactory;

    /**
     * @var FeedViewRepositoryInterface
     */
    protected $feedViewRepository;

    /**
     * @var FeedViewCollectionFactory
     */
    protected $feedViewCollectionFactory;

    /**
     * @var CronManager
     */
    protected $cronManager;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

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
     * @param MassActionFilter $massActionFilter
     * @param QueueHandler $queueHandler
     * @param IndexingQueueFactory $indexingQueueFactory
     * @param IndexingQueueRepositoryInterface $indexingQueueRepository
     * @param IndexingQueueCollectionFactory $indexingQueueCollectionFactory
     * @param FeedViewFactory $feedViewFactory
     * @param FeedViewRepositoryInterface $feedViewRepository
     * @param FeedViewCollectionFactory $feedViewCollectionFactory
     * @param CronManager $cronManager
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param HelperData $helperData
     * @param ProductHelper $productHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        MassActionFilter $massActionFilter,
        QueueHandler $queueHandler,
        IndexingQueueFactory $indexingQueueFactory,
        IndexingQueueRepositoryInterface $indexingQueueRepository,
        IndexingQueueCollectionFactory $indexingQueueCollectionFactory,
        FeedViewFactory $feedViewFactory,
        FeedViewRepositoryInterface $feedViewRepository,
        FeedViewCollectionFactory $feedViewCollectionFactory,
        CronManager $cronManager,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        HelperData $helperData,
        ProductHelper $productHelper,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->massActionFilter = $massActionFilter;
        $this->queueHandler = $queueHandler;
        $this->indexingQueueFactory = $indexingQueueFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(IndexingQueueFactory::class);
        $this->indexingQueueRepository = $indexingQueueRepository
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(IndexingQueueRepositoryInterface::class);
        $this->indexingQueueCollectionFactory = $indexingQueueCollectionFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(IndexingQueueCollectionFactory::class);
        $this->feedViewFactory = $feedViewFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(FeedViewFactory::class);
        $this->feedViewRepository = $feedViewRepository
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(FeedViewRepositoryInterface::class);
        $this->feedViewCollectionFactory = $feedViewCollectionFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(FeedViewCollectionFactory::class);
        $this->cronManager = $cronManager;
        $this->fileFactory = $fileFactory;
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