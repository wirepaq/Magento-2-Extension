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
namespace Unbxd\ProductFeed\Cron;

use Unbxd\ProductFeed\Model\CronManager;

/**
 * Class Feed
 * @package Unbxd\ProductFeed\Cron
 */
class Feed
{
    /**
     * @var CronManager
     */
    protected $cronManager;

    /**
     * Feed constructor.
     * @param CronManager $cronManager
     */
    public function __construct(
        CronManager $cronManager
    ) {
        $this->cronManager = $cronManager;
    }

    /**
     * Run indexing/feed operation(s)
     */
    public function execute()
    {
        $this->cronManager->runJobs();
    }
}