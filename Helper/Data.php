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
namespace Unbxd\ProductFeed\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\Config\Backend\Cron;

/**
 * Class Data
 * @package Unbxd\ProductFeed\Helper
 */
class Data extends AbstractHelper
{
    /**
     * Unbxd API endpoint(s)
     */
    const FULL_SYNC_DEFAULT_API_ENDPOINT = 'http://feed.unbxd.io/api/%s/upload/catalog/full';
    const INCREMENTAL_SYNC_DEFAULT_API_ENDPOINT = '';

    /**
     * XML paths
     *
     * setup section
     */
    const XML_PATH_SETUP_SITE_KEY = 'unbxd_setup/general/site_key';
    const XML_PATH_SETUP_SECRET_KEY = 'unbxd_setup/general/secret_key';
    const XML_PATH_SETUP_API_KEY = 'unbxd_setup/general/api_key';

    /**
     * catalog section
     */
    const XML_PATH_CATALOG_AVAILABLE_PRODUCT_TYPES = 'unbxd_catalog/general/available_product_types';
    const XML_PATH_CATALOG_EXCLUDE_PRODUCTS_SPECIAL_ATTRIBUTES = 'unbxd_catalog/general/special_attributes';
    const XML_PATH_CATALOG_PRODUCT_INDEXING_ENABLED = 'unbxd_catalog/indexing/enable_product_indexing';
    const XML_PATH_CATALOG_PRODUCT_INDEX_PREFIX = 'unbxd_catalog/indexing/product_index_prefix';
    const XML_PATH_CATALOG_CRON_ENABLED = 'unbxd_catalog/cron/enabled';
    const XML_PATH_CATALOG_CRON_TYPE = 'unbxd_catalog/cron/cron_type';
    const XML_PATH_CATALOG_CRON_TYPE_MANUALLY_SCHEDULE = 'unbxd_catalog/cron/cron_type_manually_schedule';
    const XML_PATH_CATALOG_CRON_TYPE_TEMPLATE_TIME = 'unbxd_catalog/cron/cron_type_template_time';
    const XML_PATH_CATALOG_CRON_TYPE_TEMPLATE_FREQUENCY = 'unbxd_catalog/cron/cron_type_template_frequency';
    const XML_PATH_CATALOG_MANUAL_SYNCHRONIZATION_ENABLED = 'unbxd_catalog/actions/enabled';

    /**
     * search section
     */
    const XML_PATH_SEARCH_PAGE_ENABLED = 'unbxd_search/general/enabled';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->scopeConfig = $context->getScopeConfig();
        $this->storeManager = $storeManager;
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getSiteKey($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SETUP_SITE_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getSecretKey($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SETUP_SECRET_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getApiKey($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SETUP_API_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isAuthorizationCredentialsSetup($store = null)
    {
        return (bool) ($this->getSiteKey($store) && $this->getSecretKey($store) && $this->getApiKey($store));
    }

    /**
     * @param null $store
     * @return array
     */
    public function getAvailableProductTypes($store = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_AVAILABLE_PRODUCT_TYPES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $types = [];
        if ($value) {
            $types = explode(',', $value);
        }

        return $types;
    }

    /**
     * @param null $store
     * @return array
     */
    public function getSpecialAttributesForExcludeProducts($store = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_EXCLUDE_PRODUCTS_SPECIAL_ATTRIBUTES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $attributes = [];
        if ($value) {
            $attributes = explode(',', $value);
        }

        return $attributes;
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function isProductIndexingEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_PRODUCT_INDEXING_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getProductIndexPrefix($store = null)
    {
        if ($this->isProductIndexingEnabled($store)) {
            return $this->scopeConfig->getValue(
                self::XML_PATH_CATALOG_PRODUCT_INDEX_PREFIX,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return null;
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isCronEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_CRON_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool|null
     */
    public function getCronType($store = null)
    {
        if ($this->isCronEnabled($store)) {
            return $this->scopeConfig->getValue(
                self::XML_PATH_CATALOG_CRON_TYPE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return null;
    }

    /**
     * @param null $store
     * @return bool|null
     */
    public function getCronSchedule($store = null)
    {
        if ($this->isCronEnabled($store) && $this->getCronType()) {
            return $this->scopeConfig->getValue(
                Cron::CRON_STRING_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return null;
    }

    /**
     * Check whether related cron job is configured or not
     *
     * @param null $store
     * @return bool
     */
    public function isCronConfigured($store = null)
    {
        return (bool) ($this->isCronEnabled($store) && $this->getCronSchedule($store));
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isManualSynchronizationEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_MANUAL_SYNCHRONIZATION_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isSearchPageEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SEARCH_PAGE_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}