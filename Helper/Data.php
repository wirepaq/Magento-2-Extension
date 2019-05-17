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
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Framework\App\Config\ValueInterface as ConfigValueInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\Config\Backend\Cron;
use Unbxd\ProductFeed\Model\Config\Source\ProductTypes;
use Unbxd\ProductFeed\Model\Config\Source\FilterAttribute;
use Unbxd\ProductFeed\Model\FilterAttribute\FilterAttributeProvider;
use Unbxd\ProductFeed\Model\FilterAttribute\FilterAttributeInterface;

/**
 * Class Data
 * @package Unbxd\ProductFeed\Helper
 */
class Data extends AbstractHelper
{
    /**
     * XML paths
     *
     * setup section
     */
    const XML_PATH_SETUP_SITE_KEY = 'unbxd_setup/general/site_key';
    const XML_PATH_SETUP_SECRET_KEY = 'unbxd_setup/general/secret_key';
    const XML_PATH_SETUP_API_KEY = 'unbxd_setup/general/api_key';

    /**
     * API endpoints
     */
    const XML_PATH_FULL_FEED_API_ENDPOINT = 'unbxd_setup/general/api_endpoint/full';
    const XML_PATH_INCREMENTAL_FEED_API_ENDPOINT = 'unbxd_setup/general/api_endpoint/incremental';

    /**
     * catalog section
     */
    const XML_PATH_CATALOG_AVAILABLE_PRODUCT_TYPES = 'unbxd_catalog/general/available_product_types';
    const XML_PATH_CATALOG_EXCLUDE_PRODUCTS_FILTER_ATTRIBUTES = 'unbxd_catalog/general/filter_attributes';
    const XML_PATH_CATALOG_INDEXING_QUEUE_ENABLED = 'unbxd_catalog/indexing/enabled_queue';
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
     * @var ConfigInterface
     */
    private $configInterface;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var ConfigValueInterface
     */
    private $configData;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductTypes
     */
    protected $productTypes;

    /**
     * @var FilterAttributeProvider
     */
    protected $filterAttributeProvider;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param ConfigInterface $configInterface
     * @param ConfigWriter $configWriter
     * @param ConfigValueInterface $configData
     * @param StoreManagerInterface $storeManager
     * @param ProductTypes $productTypes
     * @param FilterAttributeProvider $filterAttributeProvider
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        ConfigInterface $configInterface,
        ConfigWriter $configWriter,
        ConfigValueInterface $configData,
        StoreManagerInterface $storeManager,
        ProductTypes $productTypes,
        FilterAttributeProvider $filterAttributeProvider
    ) {
        parent::__construct($context);
        $this->scopeConfig = $context->getScopeConfig();
        $this->configInterface = $configInterface;
        $this->configWriter = $configWriter;
        $this->configData = $configData;
        $this->storeManager = $storeManager;
        $this->productTypes = $productTypes;
        $this->filterAttributeProvider = $filterAttributeProvider;
    }

    /**
     * Retrieve core config value by path and store
     *
     * @param $path
     * @param string $scopeType
     * @param null $scopeCode
     * @return string
     */
    public function getConfigValue($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        return trim($this->scopeConfig->getValue($path, $scopeType, $scopeCode));
    }

    /**
     * Save config value to storage
     *
     * @param $path
     * @param $value
     * @param string $scope
     * @param int $scopeId
     */
    public function updateConfigValue($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $this->configWriter->save($path, trim($value), $scope, $scopeId);
    }

    /**
     * Save config value to the storage resource
     *
     * @param $path
     * @param $value
     * @param string $scope
     * @param int $scopeId
     */
    public function saveConfig($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $this->configInterface->saveConfig($path, $value, $scope, $scopeId);
    }

    /**
     * Delete config value from the storage resource
     *
     * @param $path
     * @param string $scope
     * @param int $scopeId
     */
    public function deleteConfig($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $this->configInterface->deleteConfig($path, $scope, $scopeId);
    }

    /**
     * Check whether or not core config value is enabled
     *
     * @param $path
     * @param string $scopeType
     * @param null $scopeCode
     * @return bool
     */
    public function isSetFlag($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        return $this->scopeConfig->isSetFlag($path, $scopeType, $scopeCode);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getSiteKey($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_SETUP_SITE_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getSecretKey($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_SETUP_SECRET_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getApiKey($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_SETUP_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    /**
     * @param null $store
     * @return string
     */
    public function getFullFeedApiEndpoint($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_FULL_FEED_API_ENDPOINT,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    /**
     * @param null $store
     * @return string
     */
    public function getIncrementalFeedApiEndpoint($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_INCREMENTAL_FEED_API_ENDPOINT,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
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
     * Retrieve all product types supported by Unbxd service
     *
     * @param null $store
     * @return array
     */
    public function getAvailableProductTypes($store = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_AVAILABLE_PRODUCT_TYPES,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $types = [];
        if ($value) {
            $types = explode(',', $value);
            if (!empty($types)) {
                if (in_array(ProductTypes::ALL_KEY, $types)) {
                    $types = $this->productTypes->getAllSupportedProductTypes();
                }
            }
        }

        return $types;
    }

    /**
     * @param null $store
     * @return array|FilterAttributeInterface[]
     */
    public function getFilterAttributes($store = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_EXCLUDE_PRODUCTS_FILTER_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $attributes = [];
        if ($value) {
            $attributes = explode(',', $value);
            if (!empty($attributes)) {
                if (in_array(FilterAttribute::DON_NOT_EXCLUDE_KEY, $attributes)) {
                    return [];
                }

                $result = [];
                foreach ($attributes as $attributeCode) {
                    /** @var FilterAttributeInterface $attribute */
                    $attribute = $this->filterAttributeProvider->getAttribute($attributeCode);
                    if ($attribute) {
                        $result[] = $attribute;
                    }
                }

                return $result;
            }
        }

        return $attributes;
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function isIndexingQueueEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_INDEXING_QUEUE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isCronEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_CRON_ENABLED,
            ScopeInterface::SCOPE_STORE,
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
                ScopeInterface::SCOPE_STORE,
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
                ScopeInterface::SCOPE_STORE,
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
     * Check whether manual synchronization enabled or not
     *
     * @param null $store
     * @return bool
     */
    public function isManualSynchronizationEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_MANUAL_SYNCHRONIZATION_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check whether instance search page enabled or not
     *
     * @param null $store
     * @return bool
     */
    public function isSearchPageEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SEARCH_PAGE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}