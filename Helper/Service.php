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

use Magento\Framework\File\Size as FileSizeService;

/**
 * Class Service
 * @package Unbxd\ProductFeed\Helper
 */
class Service
{
    /**
     * @var FileSizeService
     */
    protected $fileSizeService;

    /**
     * Service constructor.
     * @param FileSizeService $fileSizeService
     */
    public function __construct(
        FileSizeService $fileSizeService
    ) {
        $this->fileSizeService = $fileSizeService;
    }

    /**
     * Gets the value of a configuration option
     *
     * @param $param
     * @return string|null
     */
    private function iniGet($param)
    {
        if (function_exists('ini_get')) {
            return trim(ini_get($param));
        }

        return null;
    }

    /**
     * Sets the value of a configuration option
     *
     * @param $param
     * @param $value
     * @return $this
     */
    private function iniSet($param, $value)
    {
        if (function_exists('ini_set')) {
            try {
                @ini_set(trim($param), trim($value));
            } catch (\Exception $e) {
                // catch exception
            }
        }

        return $this;
    }

    /**
     * Get post max size
     *
     * @return string
     */
    public function getPostMaxSize()
    {
        return $this->iniGet('post_max_size');
    }

    /**
     * Get memory limit
     *
     * @return string
     */
    public function getMemoryLimit()
    {
        return $this->iniGet('memory_limit');
    }

    /**
     * Converts memory value (e.g. 64M, 129K) to bytes.
     *
     * Case insensitive value might be used.
     *
     * @param string $memoryValue
     * @return int
     */
    private function convertToByte($memoryValue)
    {
        if (stripos($memoryValue, 'G') !== false) {
            return (int) $memoryValue * pow(1024, 3);
        } elseif (stripos($memoryValue, 'M') !== false) {
            return (int) $memoryValue * 1024 * 1024;
        } elseif (stripos($memoryValue, 'K') !== false) {
            return (int) $memoryValue * 1024;
        }

        return (int) $memoryValue;
    }

    /**
     * Get file needed memory size
     *
     * @return $this
     */
    private function getFileNeedMemorySize()
    {
        //@TODO - implement
        return $this;
    }

    /**
     * Checks whether memory limit is reached.
     *
     * @return bool
     */
    private function isMemoryLimitReached()
    {
        $memoryLimit = $this->convertToByte($this->getMemoryLimit());
        $requiredMemory = $this->getFileNeedMemorySize();
        if ($memoryLimit === -1) {
            // a limit of -1 means no limit: http://www.php.net/manual/en/ini.core.php#ini.memory-limit
            return false;
        }

        return memory_get_usage(true) + $requiredMemory > $memoryLimit;
    }

    /**
     * @param $newValue
     * @return $this
     */
    private function updateMemoryLimit($newValue)
    {
        $memoryLimit = $this->convertToByte($this->getMemoryLimit());
        if ($memoryLimit != -1 && $memoryLimit < 756 * 1024 * 1024) {
            $this->iniSet('memory_limit', '756M');
        }

        return $this;
    }
}