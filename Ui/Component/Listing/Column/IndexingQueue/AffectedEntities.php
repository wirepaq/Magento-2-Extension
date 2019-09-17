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
namespace Unbxd\ProductFeed\Ui\Component\Listing\Column\IndexingQueue;

use Magento\Ui\Component\Listing\Columns\Column;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Magento\Framework\UrlInterface;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class AffectedEntities
 * @package Unbxd\ProductFeed\Ui\Component\Listing\Column\IndexingQueue
 */
class AffectedEntities extends Column
{
    /**
     * @var IndexingQueue
     */
    private $indexingQueue;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * AffectedEntities constructor.
     * @param IndexingQueue $indexingQueue
     * @param UrlInterface $urlBuilder
     * @param FilterManager $filterManager
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        IndexingQueue $indexingQueue,
        UrlInterface $urlBuilder,
        FilterManager $filterManager,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
        $this->indexingQueue = $indexingQueue;
        $this->urlBuilder = $urlBuilder;
        $this->filterManager = $filterManager;
    }

    /**
     * Prepare data source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item['affected_entities'] = $this->decorateCell($item['affected_entities']);
            }
        }

        return $dataSource;
    }

    /**
     * @param $value
     * @return string
     */
    private function decorateCell($value)
    {
        // link on full catalog (by default)
        $cell = '<a href="' . $this->getUrl('catalog/product/index') .'" target="_blank">' . $value .'</a>';
        if (strpos($value, '#') !== false) {
            // grab links for separate products
            $entityIds = array_map(function($item) {
                return trim($item, '#');
            }, explode(', ', $value));

            $links = [];
            foreach ($entityIds as $id) {
                $url = $this->getUrl('catalog/product/edit', ['id' => $id]);
                $links[] = '<a href="' . $url .'" target="_blank">' . '#' . $id .'</a>';
            }

            $cell = implode(', ', $links);
        }

        return $cell;
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
