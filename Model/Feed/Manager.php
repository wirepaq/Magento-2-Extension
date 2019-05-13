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
namespace Unbxd\ProductFeed\Model\Feed;

use Unbxd\ProductFeed\Api\Data\FeedViewInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Product as HelperProduct;
use Unbxd\ProductFeed\Helper\AttributeHelper;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Helper\ProductHelper;
use Unbxd\ProductFeed\Helper\Profiler;
use Unbxd\ProductFeed\Model\CacheManager;
use Magento\Framework\UrlInterface;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Unbxd\ProductFeed\Model\Feed\DataHandler\Image as ImageDataHandler;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Unbxd\ProductFeed\Model\FeedView\Handler as FeedViewManager;
use Unbxd\ProductFeed\Model\Feed\FileManager as FeedFileManager;
use Unbxd\ProductFeed\Model\Feed\FileManagerFactory;
use Unbxd\ProductFeed\Model\Feed\Api\ConnectorFactory;
use Unbxd\ProductFeed\Model\Feed\Api\Response as FeedResponse;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Logger\OptionsListConstants;
use Unbxd\ProductFeed\Model\Serializer;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\SimpleDataObjectConverter;

/**
 * Class Manager
 *
 *  Supported events:
 *   - unbxd_productfeed_prepare_data_before
 *   - unbxd_productfeed_prepare_data_after
 *   - unbxd_productfeed_send_before
 *   - unbxd_productfeed_send_after
 *
 * @package Unbxd\ProductFeed\Model\Feed
 */
class Manager
{
    /**
     * @var AttributeHelper
     */
    private $attributeHelper;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var FeedConfig
     */
    private $feedConfig;

    /**
     * @var ImageDataHandler
     */
    private $imageDataHandler;

    /**
     * @var FeedViewManager
     */
    private $feedViewManager;

    /**
     * @var FileManagerFactory
     */
    private $fileManagerFactory;

    /**
     * @var ConnectorFactory
     */
    private $connectorFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlInterface
     */
    private $frontendUrlBuilder;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Prefix of available events for dispatch
     *
     * @var string
     */
    private $eventPrefix = 'unbxd_productfeed';

    /**
     * Cache for product rewrite suffix
     *
     * @var array
     */
    private $productUrlSuffix = [];

    /**
     * Cache for product visibility types
     *
     * @var array
     */
    private $visibility = [];

    /**
     * Feed catalog data
     *
     * @var null|array
     */
    private $catalog = null;

    /**
     * Feed schema data
     *
     * @var null|array
     */
    private $schema = null;

    /**
     * Full feed data
     *
     * @var null|array
     */
    private $fullFeed = null;

    /**
     * Local cache for feed file manager
     *
     * @var null
     */
    private $fileManager = null;

    /**
     * Local cache for feed API connector manager
     *
     * @var null
     */
    private $connectorManager = null;

    /**
     * Feed type (full or incremental)
     *
     * @var null
     */
    private $type = null;

    /**
     * Flag for detect whether feed locked
     *
     * @var bool
     */
    private $isFeedLock = false;

    /**
     * Feed locked time
     *
     * @var bool
     */
    private $lockedTime = null;

    /**
     * Feed view ID related to current execution
     *
     * @var null
     */
    private $feedViewId = null;

    /**
     * Manager constructor.
     * @param AttributeHelper $attributeHelper
     * @param HelperData $helperData
     * @param FeedHelper $feedHelper
     * @param ProductHelper $productHelper
     * @param Profiler $profiler
     * @param CacheManager $cacheManager
     * @param Config $feedConfig
     * @param ImageDataHandler $imageDataHandler
     * @param FeedViewManager $feedViewManager
     * @param \Unbxd\ProductFeed\Model\Feed\FileManagerFactory $fileManagerFactory
     * @param ConnectorFactory $connectorFactory
     * @param LoggerInterface $logger
     * @param UrlInterface $frontendUrlBuilder
     * @param Serializer $serializer
     * @param EventManager $eventManager
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AttributeHelper $attributeHelper,
        HelperData $helperData,
        FeedHelper $feedHelper,
        ProductHelper $productHelper,
        Profiler $profiler,
        CacheManager $cacheManager,
        FeedConfig $feedConfig,
        ImageDataHandler $imageDataHandler,
        FeedViewManager $feedViewManager,
        FileManagerFactory $fileManagerFactory,
        ConnectorFactory $connectorFactory,
        LoggerInterface $logger,
        UrlInterface $frontendUrlBuilder,
        Serializer $serializer,
        EventManager $eventManager,
        StoreManagerInterface $storeManager
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->helperData = $helperData;
        $this->feedHelper = $feedHelper;
        $this->productHelper = $productHelper;
        $this->profiler = $profiler;
        $this->cacheManager = $cacheManager;
        $this->feedConfig = $feedConfig;
        $this->imageDataHandler = $imageDataHandler;
        $this->feedViewManager = $feedViewManager;
        $this->fileManagerFactory = $fileManagerFactory;
        $this->connectorFactory = $connectorFactory;
        $this->logger = $logger->create(OptionsListConstants::LOGGER_TYPE_FEED);
        $this->frontendUrlBuilder = $frontendUrlBuilder;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Serializer::class);
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
    }

    /**
     * Start profiling
     *
     * @return $this
     */
    private function startProfiler()
    {
        $this->profiler->startProfiling();

        return $this;
    }

    /**
     * Stops profiling
     *
     * @return $this
     */
    private function stopProfiler()
    {
        $this->profiler->stopProfiling();
        $profilerResult = $this->profiler->getProfilingStatAsString();
        $this->logger->debug('Profiler: ' . $profilerResult);

        return $this;
    }

    /**
     * Performing operations related with synchronization with Unbxd service
     *
     * @param $index
     * @param string $type
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($index, $type = FeedConfig::FEED_TYPE_FULL)
    {
        if (empty($index)) {
            $this->logger->error('Unable to execute feed. Index data are empty.');
            return false;
        }

        $this->logger->info('START feed execute.')->startTimer();

        $ids = ($type == FeedConfig::FEED_TYPE_FULL) ? [] : array_keys($index);
        $this->preProcessActions($ids, $type);
        if ($this->isFeedLock) {
            $this->lockedTime = round($this->lockedTime - microtime(true), 2);
            $this->logger->error(
                'Unable to execute feed. Feed lock by another process. Locked time: ' . $this->lockedTime
            );
            return false;
        }

        // caching feed operation type
        $this->type = $type;

        $this->startProfiler();

        $this->prepareData($index);
        $this->buildFeed($index);
        $this->serializeFeed();
        $this->writeFeed();
        $this->sendFeed();
        $this->postProcessActions();

        $this->logger->info('END feed execute. STATS:')->logStats();
        $this->stopProfiler();

        return true;
    }

    /**
     * Prepare index data for feed operations
     *
     * @param array $index
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareData(array &$index)
    {
        $this->logger->info('Prepare feed content based on index data.');

        $websiteIdKey = 'website_id';
        $entityIdKey = Config::FIELD_KEY_ENTITY_ID;
        $urlKeyKey = Config::FIELD_KEY_PRODUCT_URL_KEY;
        $imageKey = Config::FIELD_KEY_IMAGE_PATH;
        $categoryKey = Config::FIELD_KEY_CATEGORY_DATA;
        $visibilityKey = ProductInterface::VISIBILITY;

        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_prepare_data_before.');
        $this->eventManager->dispatch($this->eventPrefix . '_prepare_data_before',
            ['index' => $index, 'feed_manager' => $this]
        );

        foreach ($index as $productId => &$data) {
            if (is_int($productId)) {
                $defaultStoreId = $this->getStore()->getId();
                $storeId = isset($data[Store::STORE_ID]) ? $data[Store::STORE_ID] : $defaultStoreId;
                $data[Store::STORE_ID] = $storeId;

                $websiteId = isset($data[$websiteIdKey])
                    ? $data[$websiteIdKey]
                    : $this->getWebsite($defaultStoreId)->getId();
                $data[$websiteIdKey] = $websiteId;

                // append child data to parent
                if (
                    isset($data[Config::CHILD_PRODUCT_IDS_FIELD_KEY])
                    && !empty($data[Config::CHILD_PRODUCT_IDS_FIELD_KEY])
                ) {
                    $this->appendChildDataToParent($index, $data, $data[Config::CHILD_PRODUCT_IDS_FIELD_KEY]);
                }
                // filter index data helper fields
                $this->filterFields($data);

                // prepare unique_id field
                if (isset($data[$entityIdKey]) && !empty($data[$entityIdKey])) {
                    $data[Config::SPECIFIC_FIELD_KEY_UNIQUE_ID] = $data[$entityIdKey];
                    unset($data[$entityIdKey]);
                }
                // prepare product_url field
                if (isset($data[$urlKeyKey]) && !empty($data[$urlKeyKey])) {
                    $productUrl = $this->buildProductUrl($data[$urlKeyKey], $storeId);
                    if ($productUrl) {
                        $data[Config::SPECIFIC_FIELD_KEY_PRODUCT_URL] = $productUrl;
                        unset($data[$urlKeyKey]);
                    }
                }
                // prepare image_url field
                if (isset($data[$imageKey]) && !empty($data[$imageKey])) {
                    $imageUrl = $this->imageDataHandler->getImageUrl($data[$imageKey]);
                    if ($imageUrl) {
                        $data[Config::SPECIFIC_FIELD_KEY_IMAGE_URL] = $imageUrl;
                    }
                } else {
                    // if image doesn't exist just add default placeholder
                    $data[Config::SPECIFIC_FIELD_KEY_IMAGE_URL] = $this->imageDataHandler->getDefaultImagePlaceHolderUrl();
                }
                unset($data[$imageKey]);

                // prepare category_path_id field
                if (isset($data[$categoryKey]) && !empty($data[$categoryKey])) {
                    $categoryData = $this->buildCategoryList($data[$categoryKey]);
                    if (!empty($categoryData)) {
                        $data[Config::SPECIFIC_FIELD_KEY_CATEGORY_PATH_ID] = $categoryData;
                        unset($data[$categoryKey]);
                    }
                }
                // prepare visibility field
                if (isset($data[$visibilityKey]) && !empty($data[$visibilityKey])) {
                    $data[$visibilityKey] = $this->getVisibilityTypeLabel($data[$visibilityKey]);
                }

                // change array keys to needed format
                $data = $this->formatArrayKeysToCamelCase($data);
            }
        }

        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_prepare_data_after.');
        $this->eventManager->dispatch($this->eventPrefix . '_prepare_data_after',
            ['index' => $index, 'feed_manager' => $this]
        );

        return $this;
    }

    /**
     * Build feed content to needed format based on prepared index data
     *
     * @param $preparedData
     * @return $this|bool
     */
    private function buildFeed($preparedData)
    {
        $this->logger->info('Build feed content.');

        if (empty($preparedData)) {
            $this->logger->info('Can\'t build feed content. Prepared data are empty.');
            return false;
        }

        // schema fields
        $schema = isset($preparedData['fields']) ? $preparedData['fields'] : [];
        if (!empty($schema) && !isset($preparedData[Config::SCHEMA_FIELD_KEY]) && Config::INCLUDE_SCHEMA) {
            $this->schema = [
                Config::SCHEMA_FIELD_KEY => $schema
            ];
            unset($preparedData['fields']);
        }

        // catalog product data
        if (!empty($preparedData) && Config::INCLUDE_CATALOG) {
            $this->catalog = [
                Config::OPERATION_TYPE_ADD => [
                    Config::CATALOG_ITEMS_FIELD_KEY => array_values($preparedData)
                ]
            ];
        }

        // full feed
        if ($this->schema && $this->catalog) {
            $this->fullFeed = [
                Config::FEED_FIELD_KEY => [
                    Config::CATALOG_FIELD_KEY => array_merge($this->schema, $this->catalog)
                ]
            ];
        }

        return $this;
    }

    /**
     * Serialize formed feed content
     *
     * @return array|bool|string|null
     */
    private function serializeFeed()
    {
        $this->logger->info('Serialize feed content.');

        if ($this->fullFeed) {
            try {
                $this->fullFeed = $this->serializer->serialize($this->fullFeed);
            } catch (\Exception $e) {
                // catch and log exception
                $this->logger->error('Can\'t serialized feed content.')->critical($e);
            }
        }

        return $this;
    }

    /**
     * Write feed content to file
     *
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function writeFeed()
    {
        $this->logger->info('Write feed content to file.');

        /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $fileManager */
        $fileManager = $this->getFileManager();
        if ($this->fullFeed) {
            // remove old file if exist
            if ($fileManager->isExist()) {
                $fileManager->deleteFile();
            }

            try {
                $fileManager->write($this->fullFeed);
            } catch (\Exception $e) {
                // catch and log exception
                $this->logger->error('Can\'t write feed content.')->critical($e);
            }
        }

        return $this;
    }

    /**
     * Send feed data through Unbxd API
     *
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function sendFeed()
    {
        $this->logger->info('Send feed to service.');

        $params = $this->prepareFileParameters();
        if (empty($params)) {
            $this->logger->error('File parameters for request are empty.');
            return $this;
        }

        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_send_before.');
        $this->eventManager->dispatch($this->eventPrefix . '_send_before',
            ['file_params' => $params, 'feed_manager' => $this]
        );

        /** @var \Unbxd\ProductFeed\Model\Feed\Api\Connector $connectorManager */
        $connectorManager = $this->getConnectorManager();
        try {
            $connectorManager->execute($params);
        } catch (\Exception $e) {
            // catch and log exception
            $this->logger->error('Can\'t send feed.')->critical($e);
        }

        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_send_after.');
        $this->eventManager->dispatch($this->eventPrefix . '_send_after',
            ['file_params' => $params, 'feed_manager' => $this]
        );

        return $this;
    }

    /**
     * Prepare feed file request parameters which must be send through API
     *
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function prepareFileParameters()
    {
        /** @var \Unbxd\ProductFeed\Model\Feed\Api\Connector $connectorManager */
        $connectorManager = $this->getConnectorManager();
        /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $fileManager */
        $fileManager = $this->getFileManager();
        $params = [];

        if (!$fileManager->isExist()) {
            return $params;
        }

        $filePath = $fileManager->getFileLocation();
        $fileName = $fileManager->getFileName();
        $fileMimeType = $fileManager->getMimeType();

        // add file config (use in case if 'POST' method not use)
        $connectorManager->prepareFileConfig([
            'name' => $fileName,
            'path' => $filePath,
            'size' => $fileManager->getFileSize()
        ]);

        if (function_exists('curl_file_create')) {
            $params['file'] = curl_file_create($filePath, $fileMimeType, $fileName);
        } else {
            $params['file'] = "@$filePath;filename="
                . ($fileName ?: basename($filePath))
                . ($fileMimeType ? ";type=$fileMimeType" : '');
        }

        return $params;
    }

    /**
     * Perform actions before
     *
     * @param array $ids
     * @param string $type
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function preProcessActions($ids, $type)
    {
        $this->logger->info('Pre-process execution actions.');

        $isFeedLock = $this->feedHelper->isFullSynchronizationLocked();
        $isFeedLock = $isFeedLock || $this->getFileManager()->isExist();
        if ($isFeedLock) {
            if (!$this->lockedTime) {
                $this->lockedTime = microtime(true);
            }
            $this->isFeedLock = true;
            return $this;
        } else {
            $this->isFeedLock = false;
            $this->lockedTime = null;
        }

        // update config status value
        $this->feedHelper->setLastSynchronizationStatus(FeedView::STATUS_RUNNING);

        // filter schema fields
        $ids = array_filter($ids, function($value) {
            return is_int($value);
        });
        // @TODO - need to figure out with stores
        // create feed view for current execution
        $storeId = 1;
        $feedViewId = $this->getFeedViewManager()->add($ids, $type, $storeId);
        if ($feedViewId) {
            $this->feedViewId = $feedViewId;
        }

        return $this;
    }

    /**
     * Update related config information about current execution
     *
     * @param FeedResponse $response
     * @return $this
     */
    private function updateConfigStats(FeedResponse $response)
    {
        $this->logger->info('Update config statistics.');

        $status = $response->getIsSuccess() ? FeedView::STATUS_COMPLETE : FeedView::STATUS_ERROR;
        $type = $this->type;

        $this->feedHelper->setFullSynchronizationLocked($this->isFeedLock)
            ->setLastSynchronizationOperationType($type)
            ->setLastSynchronizationDatetime(date('Y-m-d H:i:s'))
            ->setLastSynchronizationStatus($status);

        if ($this->lockedTime) {
            $this->feedHelper->setFullSynchronizationLockedTime($this->lockedTime);
        }
        if ($type == FeedConfig::FEED_TYPE_FULL) {
            $this->feedHelper->setFullCatalogSynchronizedStatus(true);
        }
        if ($type == FeedConfig::FEED_TYPE_INCREMENTAL) {
            $this->feedHelper->setIncrementalProductSynchronizedStatus(true);
        }

        return $this;
    }

    /**
     * Update related feed view information about current execution
     *
     * @param FeedResponse $response
     * @return $this
     */
    private function updateFeedView(FeedResponse $response)
    {
        $this->logger->info('Update feed view:');

        $status = $response->getIsSuccess() ? FeedView::STATUS_COMPLETE : FeedView::STATUS_ERROR;
        if ($this->feedViewId) {
            $updateData = [
                FeedViewInterface::STATUS => $status,
                FeedViewInterface::FINISHED_AT => date('Y-m-d H:i:s'),
                FeedViewInterface::EXECUTION_TIME => $this->logger->getTime()
            ];
            if ($response->getIsError()) {
                $updateData[FeedViewInterface::ADDITIONAL_INFORMATION] = $response->getErrorsAsString();
            }

            $this->getFeedViewManager()->update($this->feedViewId, $updateData);
        }

        return $this;
    }

    /**
     * Perform actions after
     *
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function postProcessActions()
    {
        $this->logger->info('Post-process execution actions.');

        /** @var \Unbxd\ProductFeed\Model\Feed\Api\Connector $connectorManager */
        $connectorManager = $this->getConnectorManager();
        /** @var FeedResponse $response */
        $response = $connectorManager->getResponse();

        if ($response->getIsError()) {
            // log errors if any
            $this->logger->error($response->getErrorsAsString());
        }

        // performing operations with response
        $this->updateConfigStats($response);
        $this->updateFeedView($response);

        // reset local cache to initial state
        $this->reset();

        // clean file
        $this->getFileManager()->deleteFile();

        return $this;
    }

    /**
     * @param $index
     * @param array $data
     * @param array $childIds
     */
    private function appendChildDataToParent(array &$index, array &$data, array $childIds)
    {
        foreach ($childIds as $id) {
            if (array_key_exists($id, $index)) {
                $childData = $this->formatChildArrayKeys($index[$id]);
                $data[Config::CHILD_PRODUCTS_FIELD_KEY][] = $childData;
                unset($index[$id]);
            }
        }
    }

    /**
     * @param array $data
     * @return $this
     */
    private function filterParentFieldsChildrenAttributes(array &$data)
    {
        if (array_key_exists(Config::CHILD_PRODUCT_ATTRIBUTES_FIELD_KEY, $data)) {
            foreach ($data[Config::CHILD_PRODUCT_ATTRIBUTES_FIELD_KEY] as $attributeCode) {
                if (array_key_exists($attributeCode, $data)) {
                    unset($data[$attributeCode]);
                }
            }
        }

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    private function filterParentFieldsChildrenRelated(array &$data)
    {
        $fields = [
            Config::CHILD_PRODUCT_SKUS_FIELD_KEY,
            Config::CHILD_PRODUCT_IDS_FIELD_KEY,
            Config::CHILD_PRODUCT_ATTRIBUTES_FIELD_KEY,
            Config::CHILD_PRODUCT_CONFIGURABLE_ATTRIBUTES_FIELD_KEY
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                unset($data[$field]);
            }
        }

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    private function filterAdditionalFields(array &$data)
    {
        $fields = [
            'indexed_attributes',
            'small_image',
            'thumbnail',
            'image_label',
            'small_image_label',
            'thumbnail_label'
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                unset($data[$field]);
            }
        }

        return $this;
    }

    /**
     * @param array $data
     */
    private function prepareOptionValues(array &$data)
    {
        $matchKeyPart = 'option_text_';
        foreach ($data as $key => $value) {
            $pureKey = str_replace($matchKeyPart, '', $key);
            $searchKey = $matchKeyPart . $key;
            if (strpos($key, $matchKeyPart) !== false) {
                // fields with option values
                $data[$pureKey] = array_key_exists($searchKey, $data)
                    ? implode(',', $data[$searchKey])
                    : (
                    (is_array($value) && ($key != Config::SPECIFIC_FIELD_KEY_CATEGORY_PATH_ID))
                        ? implode(',', $value)
                        : $value
                    );
            } else {
                $excluded = [
                    Config::FIELD_KEY_CATEGORY_DATA,
                    Config::CHILD_PRODUCTS_FIELD_KEY
                ];
                $data[$key] = (is_array($value) && !in_array($key, $excluded))
                    ? implode(',', $value)
                    : $value;
            }
            unset($data[$searchKey]);
        }
    }

    /**
     * Filter fields in feed data
     *
     * @param array $data
     * @return $this
     */
    private function filterFields(array &$data)
    {
        // remove attributes fields related to child products
        $this->filterParentFieldsChildrenAttributes($data);

        // remove fields related to child products
        $this->filterParentFieldsChildrenRelated($data);

        // index helper fields which must be deleted from feed content
        $this->filterAdditionalFields($data);

        // convert option values and labels only in labels
        $this->prepareOptionValues($data);

        return $this;
    }

    /**
     * Build category list related to product, in specific format supported by Unbxd service:
     * (ex.: /fashion|Fashion>/fashion/shoes|Shoes>/fashion/shoes/casual|Casual)
     *
     * @param $categoryData
     * @return array
     */
    private function buildCategoryList($categoryData)
    {
        $result = [];
        foreach ($categoryData as $key => $data) {
            $related = isset($data['related']) ? (string) $data['related'] : false;
            $urlPath = isset($data['url_path']) ? (string) $data['url_path'] : null;
            if (!$related || !$urlPath) {
                continue;
            }

            $pathData = explode('/', $urlPath);
            if (!empty($pathData)) {
                $path = '';
                $urlPart = '';
                foreach ($pathData as $urlKey) {
                    $key = array_search($urlKey, array_column($categoryData, 'url_key'));
                    $name = isset($categoryData[$key]['name']) ? trim($categoryData[$key]['name']) : 'Undefined';
                    $urlPart .= '/' . $urlKey;
                    $path .= sprintf('%s|%s>', $urlPart, $name);
                }
                $result[] = rtrim(trim($path, '>'), '/');
            }
        }

        return $result;
    }

    /**
     * Retrieve product frontend url
     *
     * @param $urlKey
     * @param $storeId
     * @return mixed
     */
    private function buildProductUrl($urlKey, $storeId)
    {
        $path = $urlKey . $this->getProductUrlSuffix($storeId);
        $url = $this->getFrontendUrl($path);
        // check if use category path for product url
        if ($this->helperData->isSetFlag(HelperProduct::XML_PATH_PRODUCT_URL_USE_CATEGORY)) {
            // @TODO - we need to implement this?
        }

        return (substr($url, -1) == '/') ? substr($url, 0, -1) : $url;
    }

    /**
     * Retrieve product rewrite suffix for store
     *
     * @param int $storeId
     * @return string
     */
    private function getProductUrlSuffix($storeId)
    {
        if (!isset($this->productUrlSuffix[$storeId])) {
            $this->productUrlSuffix[$storeId] = $this->helperData->getConfigValue(
                ProductUrlPathGenerator::XML_PATH_PRODUCT_URL_SUFFIX
            );
        }

        return $this->productUrlSuffix[$storeId];
    }

    /**
     * Retrieve product visibility label by value
     *
     * @param string $value
     * @return mixed
     */
    private function getVisibilityTypeLabel($value)
    {
        if (!isset($this->visibility[$value])) {
            $this->visibility[$value] = (string) $this->productHelper->getVisibilityTypeLabelByValue($value);
        }

        return $this->visibility[$value];
    }

    /**
     * Get frontend url
     *
     * @param $routePath
     * @param string $scope
     * @return mixed
     */
    public function getFrontendUrl($routePath, $scope = '')
    {
        $this->frontendUrlBuilder->setScope($scope);
        $href = $this->frontendUrlBuilder->getUrl(
            $routePath,
            [
                '_current' => false,
                '_nosid' => true,
                '_query' => false
            ]
        );

        return $href;
    }

    /**
     * @param $data
     * @return array
     */
    private function formatChildArrayKeys($data)
    {
        $result = [];
        array_walk($data, function ($value, $key) use (&$result) {
            $newKey = sprintf(
                '%s%s',
                Config::CHILD_PRODUCT_FIELD_PREFIX,
                ucfirst(SimpleDataObjectConverter::snakeCaseToCamelCase($key))
                );
            $result[$newKey] = $value;
        });

        return $result;
    }


    /**
     * @param $data
     * @return array
     */
    private function formatArrayKeysToCamelCase($data)
    {
        $result = [];
        array_walk($data, function ($value, $key) use (&$result) {
            $result[SimpleDataObjectConverter::snakeCaseToCamelCase($key)] = $value;
        });

        return $result;
    }

    /**
     * @param array $data
     * @return array
     */
    private function formatArrayKeysToVariant($data)
    {
        $result = [];
        array_walk($data, function ($value, $key) use (&$result) {
            $result[Config::CHILD_PRODUCT_FIELD_PREFIX . '_' . $key] = $value;
        });

        return $result;
    }

    /**
     * @param string $storeId
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStore($storeId = '')
    {
        return $this->storeManager->getStore($storeId);
    }

    /**
     * @param string $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getWebsite($storeId = '')
    {
        return $this->getStore($storeId)->getWebsite();
    }

    /**
     * Retrieve feed view manager instance. Init if needed
     *
     * @return FeedViewManager
     */
    private function getFeedViewManager()
    {
        return $this->feedViewManager;
    }

    /**
     * Retrieve file manager instance. Init if needed
     *
     * @return FileManager|null
     */
    private function getFileManager()
    {
        if (null == $this->fileManager) {
            /** @var \Unbxd\ProductFeed\Model\Feed\FileManager */
            $this->fileManager = $this->fileManagerFactory->create();
        }

        return $this->fileManager;
    }

    /**
     * Retrieve connector manager instance. Init if needed
     *
     * @return Api\Connector|null
     */
    private function getConnectorManager()
    {
        if (null == $this->connectorManager) {
            /** @var \Unbxd\ProductFeed\Model\Feed\Api\Connector */
            $this->connectorManager = $this->connectorFactory->create();
        }

        return $this->connectorManager;
    }

    /**
     * Reset all cache handlers to initial state
     *
     * @return void
     */
    private function reset()
    {
        $this->productUrlSuffix = [];
        $this->visibility = [];
        $this->type = null;
        $this->schema = null;
        $this->catalog = null;
        $this->fullFeed = null;
        $this->isFeedLock = false;
        $this->lockedTime = null;
        $this->feedViewId = null;
    }
}