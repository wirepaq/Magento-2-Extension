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
namespace Unbxd\ProductFeed\Model\Admin;

use Magento\Framework\Notification\MessageInterface;
use Magento\Backend\Model\Auth\Session;
use Unbxd\ProductFeed\Helper\Module as ModuleHelper;
use Magento\Backend\Model\UrlInterface;
use Magento\Composer\MagentoComposerApplication;
use Magento\Framework\Composer\MagentoComposerApplicationFactory;
use Magento\Setup\Model\PackagesData;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;

/**
 * Class ModuleAvailabilityMessages
 * @package Unbxd\ProductFeed\Model\Admin
 */
class ModuleAvailabilityMessages implements MessageInterface
{
    /**#@+
     * Composer command params options
     */
    const PARAM_LATEST = '--latest';
    /**#@-*/

    /**
     * @var Session
     */
    protected $authSession;

    /**
     * @var ModuleHelper
     */
    private $moduleHelper;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var MagentoComposerApplication
     */
    protected $magentoComposerApplication;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var TimezoneInterface
     */
    protected $timeZone;

    /**
     * @var string
     */
    private $documentationUrl = 'http://unbxd.com/documentation/site-search/v2-search-plugins-magento/';

    /**
     * ModuleAvailabilityMessages constructor.
     * @param Session $authSession
     * @param ModuleHelper $moduleHelper
     * @param UrlInterface $url
     * @param MagentoComposerApplicationFactory $composerAppFactory
     * @param Filesystem $filesystem
     * @param TimezoneInterface $timeZone
     */
    public function __construct(
        Session $authSession,
        ModuleHelper $moduleHelper,
        UrlInterface $url,
        MagentoComposerApplicationFactory $composerAppFactory,
        Filesystem $filesystem,
        TimezoneInterface $timeZone
    ) {
        $this->authSession = $authSession;
        $this->moduleHelper = $moduleHelper;
        $this->url = $url;
        $this->magentoComposerApplication = $composerAppFactory->create();
        $this->filesystem = $filesystem;
        $this->timeZone = $timeZone;
    }

    /**
     * @return string
     */
    public function getIdentity()
    {
        return md5($this->getModuleName() . '_' . $this->authSession->getUser()->getLogdate());
    }

    /**
     * @return bool
     * @throws \Zend_Json_Exception
     */
    public function isDisplayed()
    {
        if (!$this->moduleHelper->isModuleEnable($this->getModuleName())) {
            return false;
        }

        if (!$this->getAvailablePackageVersion()) {
            return false;
        }

        return true;
    }

    /**
     * @return \Magento\Framework\Phrase|string
     * @throws \Exception
     */
    public function getText()
    {
        $message = 'A new version of the <strong><a href="%s">%s</a></strong> module is available (current - <strong>%s</strong>, available - <strong>%s</strong>).<br/>Please refer to the <a href="%s">documentation</a> for upgrade to the latest version.<br/>';
        if ($lastUpgradeDatetime = $this->getLastUpgradeDatetime($this->getPackageName())) {
            $message .= sprintf('Last module upgrade - %s.', $lastUpgradeDatetime);
        }

        $message = sprintf(
            $message,
            $this->getModuleConfigurationUrl(),
            $this->getModuleName(),
            $this->getCurrentInstalledVersion(),
            $this->getAvailablePackageVersion(),
            $this->documentationUrl
        );

        return __($message);
    }

    /**
     * @return int
     */
    public function getSeverity()
    {
        return MessageInterface::SEVERITY_NOTICE;
    }

    /**
     * Get full module name
     *
     * @return string
     */
    private function getModuleName()
    {
        return $this->moduleHelper->getModuleName();
    }

    /**
     * Get full package name
     *
     * @return mixed
     * @throws \Zend_Json_Exception
     */
    private function getPackageName()
    {
        $moduleInfo = $this->moduleHelper->getModuleInfo();
        if (!$moduleInfo instanceof DataObject) {
            return '';
        }

        return $moduleInfo->getName();
    }

    /**
     * @return mixed
     * @throws \Zend_Json_Exception
     */
    private function getCurrentInstalledVersion()
    {
        $moduleInfo = $this->moduleHelper->getModuleInfo();
        if (!$moduleInfo instanceof DataObject) {
            return '';
        }

        return $moduleInfo->getVersion();
    }

    /**
     * @return bool|string
     * @throws \Zend_Json_Exception
     */
    private function getAvailablePackageVersion()
    {
        $packageName = $this->getPackageName();
        if (!$packageName) {
            return false;
        }

        $availableVersion = $this->runComposerCommand($packageName);
        if (!$availableVersion) {
            return false;
        }

        if (version_compare($this->getCurrentInstalledVersion(), $availableVersion) >= 0) {
            return false;
        }

        return $availableVersion;
    }

    /**
     * @return string
     */
    private function getModuleConfigurationUrl()
    {
        return $this->url->getUrl('adminhtml/system_config/edit/section/unbxd_catalog');
    }

    /**
     * @param $time
     * @return string
     * @throws \Exception
     */
    private function formatDatetime($time)
    {
        return $this->timeZone->formatDateTime(
            new \DateTime('@' . $time),
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::MEDIUM
        );
    }

    /**
     * @param $packageName
     * @return array|string
     * @throws \Exception
     */
    private function getLastUpgradeDatetime($packageName)
    {
        $directory = $this->filesystem->getDirectoryRead(
            DirectoryList::ROOT
        );

        $composerPath = sprintf('vendor/%s/%s', $packageName, ModuleHelper::COMPOSER_FILENAME);
        if ($directory->isExist($composerPath)) {
            $fileData = $directory->stat($composerPath);
            return isset($fileData['mtime']) ? $this->formatDatetime($fileData['mtime']) : '';
        }

        return '';
    }

    /**
     * Runs composer command
     *
     * @param $package
     * @param string|null $workingDir
     * @return string
     * @throws \RuntimeException
     */
    public function runComposerCommand($package, $workingDir = null)
    {
        $commandParams = [
            PackagesData::PARAM_COMMAND => PackagesData::COMPOSER_SHOW,
            PackagesData::PARAM_PACKAGE => $package,
            self::PARAM_LATEST => true
        ];

        $output = null;
        try {
            $output = $this->magentoComposerApplication->runComposerCommand($commandParams, $workingDir);
        } catch (\RuntimeException $e) {
            return $output;
        }

        $latestVersionPattern = '/^latest\s*\:\s(.+)$/m';
        $matches = [];
        preg_match($latestVersionPattern, $output, $matches);
        if (isset($matches[1])) {
            // get clean available version value
            $output = preg_replace("/[^0-9.]/", "", $matches[1]);
        }

        return $output;
    }
}