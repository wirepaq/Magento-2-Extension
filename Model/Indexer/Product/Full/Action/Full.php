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
namespace Unbxd\ProductFeed\Model\Indexer\Product\Full\Action;

// @TODO - working

use Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\Action\Full as ResourceModel;

/**
 * Unbxd product full indexer.
 *
 * Class Full
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\Action
 */
class Full
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * Full constructor.
     * @param ResourceModel $resourceModel
     */
    public function __construct(
        ResourceModel $resourceModel
    ) {
        $this->resourceModel = $resourceModel;
    }   
}