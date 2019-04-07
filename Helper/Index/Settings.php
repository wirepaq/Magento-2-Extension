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
namespace Unbxd\ProductFeed\Helper\Index;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;

//@TODO - working

/**
 * Class Settings
 * @package Unbxd\ProductFeed\Helper\Index
 */
class Settings extends AbstractHelper
{
    /**
     * @var string
     */
    const XML_PATH_LOCALE_CODE = 'general/locale/code';

    /**
     * @var integer
     */
    const PER_SHARD_MAX_RESULT_WINDOW = 100000;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Settings constructor.
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
     * Return the locale code (eg.: "en_US") for a store.
     *
     * @param $store
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getLocaleCode($store)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LOCALE_CODE,
            ScopeInterface::SCOPE_STORES,
            $this->getStore($store)
        );
    }

    /**
     * Return the language code (eg.: "en") for a store.
     *
     * @param $store
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getLanguageCode($store)
    {
        $store = $this->getStore($store);
        $languageCode = current(explode('_', $this->getLocaleCode($store)));

        return $languageCode;
    }

    /**
     * Create a new index for an identifier by store including current date
     *
     * @param $indexIdentifier
     * @param $store
     */
    public function createIndexNameFromIdentifier($indexIdentifier, $store)
    {

    }

    /**
     * Retrieve the store code from object or store id.
     *
     * @param $store
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStoreCode($store)
    {
        return $this->getStore($store)->getCode();
    }

    /**
     * Ensure store is an object or load it from it's id / identifier.
     *
     * @param $store
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStore($store)
    {
        if (!is_object($store)) {
            if (!is_numeric($store)) {
                $store = $this->getStoreId($store);
            }
            $store = $this->storeManager->getStore($store);
        }

        return $store;
    }

    /**
     * @param $code
     * @return int
     */
    private function getStoreId($code)
    {
        foreach ($this->storeManager->getStores() as $store) {
            if ($store->getCode() == $code) {
                return $store->getId();
            }
        }

        return Store::DEFAULT_STORE_ID;
    }
}