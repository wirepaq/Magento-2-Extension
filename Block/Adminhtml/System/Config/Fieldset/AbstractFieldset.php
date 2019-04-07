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
     * @var TimezoneInterface
     */
    protected $dateTime;

    /**
     * General constructor.
     * @param Context $context
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param ModuleHelper $moduleHelper
     * @param TimezoneInterface $dateTime
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Config\Model\Config\Structure $configStructure,
        ModuleHelper $moduleHelper,
        TimezoneInterface $dateTime,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->assetRepo = $assetRepo;
        $this->configStructure = $configStructure;
        $this->moduleHelper = $moduleHelper;
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
     * @return bool
     */
    public function isCatalogSynchronized()
    {
        // @TODO - implement
        return false;
    }

    /**
     * @return |null
     */
    public function getLastCatalogSyncDatetime()
    {
        // @TODO - implement
        if ($this->isCatalogSynchronized()) {
            return null;
        }

        return null;
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