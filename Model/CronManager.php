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

use Magento\Framework\Crontab\CrontabManagerInterface;
use Magento\Framework\Crontab\TasksProviderInterface;
use Magento\Cron\Model\ConfigInterface;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\ScheduleFactory;

/**
 * Class CronManager
 * @package Unbxd\ProductFeed\Model
 */
class CronManager
{
    const DEFAULT_SIZE = 10;

    /**
     * @var CrontabManagerInterface
     */
    private $crontabManager;

    /**
     * @var TasksProviderInterface
     */
    private $tasksProvider;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var CollectionFactory
     */
    protected $cronFactory;

    /**
     * @var ScheduleFactory
     */
    protected $scheduleFactory;

    /**
     * Cron jobs cache
     *
     * @var null
     */
    protected $jobs = null;

    /**
     * CronManager constructor.
     * @param CrontabManagerInterface $crontabManager
     * @param TasksProviderInterface $tasksProvider
     * @param ConfigInterface $config
     * @param CollectionFactory $cronFactory
     * @param ScheduleFactory $scheduleFactory
     */
    public function __construct(
        CrontabManagerInterface $crontabManager,
        TasksProviderInterface $tasksProvider,
        ConfigInterface $config,
        CollectionFactory $cronFactory,
        ScheduleFactory $scheduleFactory
    ) {
        $this->crontabManager = $crontabManager;
        $this->tasksProvider = $tasksProvider;
        $this->config = $config;
        $this->cronFactory = $cronFactory;
        $this->scheduleFactory = $scheduleFactory;
    }

    /**
     * @param int $size
     * @return null
     */
    public function getCronJobs($size)
    {
        if (!$this->jobs) {
            $result = [];
            /** @var \Magento\Cron\Model\ResourceModel\Schedule\Collection $scheduleCollection */
            $scheduleCollection = $this->cronFactory->create();
            $this->filterCollection($scheduleCollection, $size);
            if (count($scheduleCollection) > 0) {
                foreach ($scheduleCollection as $jobRow) {
                    $result[] = [
                        'schedule_id' => $jobRow['schedule_id'],
                        'code' => $jobRow['job_code'],
                        'status' => $jobRow['status'],
                        'created_at' => $jobRow['created_at']
                    ];
                }
                $this->jobs = $result;
            }
        }

        return $this->jobs;
    }

    /**
     * @param \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection
     * @param string $size
     * @return $this
     */
    private function filterCollection(\Magento\Cron\Model\ResourceModel\Schedule\Collection $collection, $size = '')
    {
        // try to retrieve data from last 24hrs
        $time = time();
        $to = date('Y-m-d H:i:s', $time);
        $lastTime = $time - 86400; // 60*60*24
        $from = date('Y-m-d H:i:s', $lastTime);
        $collection->addFieldToFilter(
            'created_at',
            ['from' => $from, 'to' => $to]
        )->setOrder('schedule_id')->setPageSize($size ?: self::DEFAULT_SIZE);

        return $this;
    }
}