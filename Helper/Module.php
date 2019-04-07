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
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\Dir as ModuleDir;
use Unbxd\ProductFeed\Model\Serializer;
use Magento\Framework\Exception\FileSystemException;

/**
 * Class Module
 * @package Unbxd\ProductFeed\Helper
 */
class Module extends AbstractHelper
{
    const COMPOSER_FILE_NAME = 'composer.json';

    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    private $moduleReader;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $fileSystem;

    /**
     * @var array|null
     */
    private $moduleData = null;

    /**
     * @var string
     */
    private $moduleName = '';

    /**
     * Module constructor.
     * @param ComponentRegistrar $componentRegistrar
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\App\Helper\Context $context
     * @param ModuleDir\Reader $moduleReader
     * @param \Magento\Framework\Filesystem\Driver\File $fileSystem
     * @param Serializer|null $serializer
     */
    public function __construct(
        ComponentRegistrar $componentRegistrar,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Framework\Filesystem\Driver\File $fileSystem,
        Serializer $serializer = null
    ) {
        parent::__construct($context);
        $this->componentRegistrar = $componentRegistrar;
        $this->moduleManager = $moduleManager;
        $this->moduleReader = $moduleReader;
        $this->fileSystem = $fileSystem;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Unbxd\ProductFeed\Model\Serializer::class);
    }

    /**
     * @return string
     */
    public function getModuleName()
    {
        if (!$this->moduleName) {
            $class = get_class($this);
            if ($class) {
                $class = explode('\\', $class);
                if (isset($class[0]) && isset($class[1])) {
                    $this->moduleName = sprintf('%s_%s', $class[0], $class[1]);
                }
            }
        }

        return $this->moduleName;
    }

    /**
     * @param $moduleName
     * @return bool
     */
    public function isModuleEnable($moduleName = '')
    {
        $moduleName = $moduleName ?: $this->getModuleName();

        return $this->moduleManager->isEnabled($moduleName);
    }

    /**
     * Read info about extension from composer json file
     *
     * @param null $moduleName
     * @return array|\Magento\Framework\DataObject|null
     */
    public function getModuleInfo($moduleName = null)
    {
        $moduleName = $moduleName ?: $this->getModuleName();
        if (!$this->moduleData) {
            $this->moduleData = new \Magento\Framework\DataObject();
            try {
                $dir = $this->moduleReader->getModuleDir('', $moduleName);
                $file = $dir . DIRECTORY_SEPARATOR . self::COMPOSER_FILE_NAME;
                if ($this->fileSystem->isExists($file)) {
                    $string = $this->fileSystem->fileGetContents($file);
                    $moduleData = $this->serializer->unserialize($string);
                    if ($moduleData) {
                        $this->moduleData->setData($moduleData);
                    }
                }
            } catch (\LogicException $e) {
                return $this->moduleData;
            } catch (FileSystemException $e) {
                return $this->moduleData;
            }
        }

        return $this->moduleData;
    }
}
