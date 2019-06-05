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
     * Run indexing/feed operation(s) by schedule
     *
     * @throws \Exception
     */
    public function executeQueueJobs()
    {
        $this->cronManager->runJobs();
    }

    /**
     * Check status for uploaded feed
     *
     * @throws \Exception
     */
    public function checkUploadFeedStatus()
    {
        $this->cronManager->checkUploadFeedStatus();
    }
}