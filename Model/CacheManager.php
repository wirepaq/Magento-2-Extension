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
namespace Unbxd\ProductFeed\Model;

use Magento\Framework\App\CacheInterface;
use Unbxd\ProductFeed\Model\Serializer;

/**
 * Class CacheManager
 * @package Unbxd\ProductFeed\Model
 */
class CacheManager
{
    /**
     * @var integer
     */
    const DEFAULT_LIFETIME = 9600;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var array
     */
    private $localCache = [];

    /**
     * CacheManager constructor.
     * @param CacheInterface $cache
     * @param \Unbxd\ProductFeed\Model\Serializer $serializer
     */
    public function __construct(
        CacheInterface $cache,
        Serializer $serializer
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Unbxd\ProductFeed\Model\Serializer::class);
    }

    /**
     * Save data into an cache.
     *
     * @param $cacheKey
     * @param $data
     * @param array $cacheTags
     * @param int $lifetime
     */
    public function saveCache($cacheKey, $data, $cacheTags = [], $lifetime = self::DEFAULT_LIFETIME)
    {
        $this->localCache[$cacheKey] = $data;
        if (!is_string($data)) {
            $data = $this->serializer->serialize($data);
        }

        $this->cache->save($data, $cacheKey, $cacheTags, $lifetime);
    }

    /**
     * Load data from the cache
     *
     * @param $cacheKey
     * @return mixed
     */
    public function loadCache($cacheKey)
    {
        if (!isset($this->localCache[$cacheKey])) {
            $data = $this->cache->load($cacheKey);
            if ($data) {
                $data = $this->serializer->unserialize($data);
            }
            $this->localCache[$cacheKey] = $data;
        }

        return $this->localCache[$cacheKey];
    }

    /**
     * Clean the cache by identifier and store
     *
     * @param $identifier
     * @param $storeId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function cleanCache($identifier, $storeId)
    {
        $cacheTags = $this->getCacheTags($identifier, $storeId);
        $this->localCache = [];
        $this->cache->clean($cacheTags);
    }

    /**
     * Get cache tag by identifier / store.
     *
     * @param $identifier
     * @param $storeId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCacheTags($identifier, $storeId)
    {
        return [$identifier . '_' . $storeId];
    }
}