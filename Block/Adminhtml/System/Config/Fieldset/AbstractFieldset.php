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
namespace Unbxd\ProductFeed\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Backend\Block\Template\Context;
use Unbxd\ProductFeed\Helper\Module as ModuleHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Model\FeedView;

/**
 * Class AbstractFieldset
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Fieldset
 */
abstract class AbstractFieldset extends Template implements RendererInterface
{
    /**
     * Static resources
     *
     * @var array
     */
    protected static $unbxdReferenceUrls = [
        'base' => 'https://unbxd.com',
        'feed_doc' => 'https://unbxd.com/documentation/site-search/v2-search-product-feed/'
    ];

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepo;

    /**
     * @var \Magento\Config\Model\Config\Structure
     */
    private $configStructure;

    /**
     * @var ModuleHelper
     */
    private $moduleHelper;

    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * @var FeedView
     */
    protected $feedView;

    /**
     * @var TimezoneInterface
     */
    protected $dateTime;

    /**
     * AbstractFieldset constructor.
     * @param Context $context
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param ModuleHelper $moduleHelper
     * @param FeedHelper $feedHelper
     * @param FeedView $feedView
     * @param TimezoneInterface $dateTime
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Config\Model\Config\Structure $configStructure,
        ModuleHelper $moduleHelper,
        FeedHelper $feedHelper,
        FeedView $feedView,
        TimezoneInterface $dateTime,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->assetRepo = $assetRepo;
        $this->configStructure = $configStructure;
        $this->moduleHelper = $moduleHelper;
        $this->feedHelper = $feedHelper;
        $this->feedView = $feedView;
        $this->dateTime = $dateTime;
    }

    /**
     * @return string
     */
    public function getLogoSrc()
    {
        return $this->assetRepo->getUrl("Unbxd_ProductFeed::images/unbxd_logo.png");
    }

    /**
     * @return mixed
     */
    public function getModuleVersion()
    {
        return $this->moduleHelper->getModuleInfo()->getVersion();
    }

    /**
     * @return string
     */
    public function getLastUploadId()
    {
        return $this->feedHelper->getLastUploadId();
    }

    /**
     * @return string
     */
    public function getUploadedSize()
    {
        return $this->feedHelper->getUploadedSize();
    }

    /**
     * @return bool
     */
    public function isSynchronizationAttempt()
    {
        return (bool) ($this->getLastUploadId());
    }

    /**
     * @return bool
     */
    public function isFullCatalogSynchronized()
    {
        return (bool) $this->feedHelper->isFullCatalogSynchronized();
    }

    /**
     * @return bool
     */
    public function isIncrementalProductSynchronized()
    {
        return (bool) $this->feedHelper->isIncrementalProductSynchronized();
    }

    /**
     * @return string|null
     */
    public function getLastCatalogSyncDatetime()
    {
        $date = $this->feedHelper->getLastSynchronizationDatetime();
        if (!$this->isSynchronizationAttempt() || !$date) {
            return null;
        }

        return $this->dateTime->formatDate($date,\IntlDateFormatter::MEDIUM,true);
    }

    /**
     * @return string|null
     */
    public function getLastSynchronizationOperationType()
    {
        $type = $this->feedHelper->getLastSynchronizationOperationType();
        if (!$this->isSynchronizationAttempt() || !$type) {
            return null;
        }

        return ucfirst($type);
    }

    /**
     * @return bool
     */
    public function getIsSuccess()
    {
        return (bool) (($this->getLastSynchronizationStatus() == FeedView::STATUS_COMPLETE)
            && $this->isSynchronizationAttempt());
    }

    /**
     * @return bool
     */
    public function getLastSynchronizationStatus()
    {
        return (int) $this->feedHelper->getLastSynchronizationStatus();
    }

    /**
     * @return string|null
     */
    public function getLastSynchronizationStatusHtml()
    {
        $status = $this->getLastSynchronizationStatus();
        if (!$this->isSynchronizationAttempt() || !$status) {
            return null;
        }

        $availableStatuses = $this->feedView->getAvailableStatuses();
        $decoratorClassPath = 'undefined';
        $title = 'Undefined';
        $statusHtml = '';
        if (array_key_exists($status, $availableStatuses)) {
            $title = $availableStatuses[$status];
            if ($status == FeedView::STATUS_RUNNING) {
                $decoratorClassPath = 'minor';
            } elseif ($status == FeedView::STATUS_COMPLETE) {
                $decoratorClassPath = 'notice';
            } elseif ($status == FeedView::STATUS_ERROR) {
                $decoratorClassPath = 'critical';
            }
        }

        $statusHtml .= '<span class="grid-severity-' . $decoratorClassPath .'" style="display: inline-block"><span>' . __($title) . '</span></span>';

        return $statusHtml;
    }

    /**
     * @param string $type
     * @return mixed|string
     */
    public static function getUnbxdReferenceUrl($type = '')
    {
        if (!$type) {
            return isset(self::$unbxdReferenceUrls['base']) ? self::$unbxdReferenceUrls['base'] : '';
        }

        return isset(self::$unbxdReferenceUrls[$type]) ? self::$unbxdReferenceUrls[$type] : '';
    }

    /**
     * Create action url by path
     *
     * @param string $path
     * @return string
     */
    private function getActionUrl($path = '')
    {
        return $this->getUrl($path,
            [
                '_secure' => $this->getRequest()->isSecure()
            ]
        );
    }

    /**
     * @return string
     */
    public function getCatalogFeedConfigurationUrl()
    {
        return $this->getActionUrl('adminhtml/system_config/edit/section/unbxd_catalog');
    }
}