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

/**
 * Class ViewIndex
 * @package Unbxd\ProductFeed\Controller\Adminhtml
 */
abstract class ViewIndex extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Index constructor.
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param CronManager $cronManager
     * @param HelperData $helperData
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->storeManager = $storeManager;
    }
}