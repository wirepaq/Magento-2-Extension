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

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;

/**
 * Class FileManager
 * @package Unbxd\ProductFeed\Model\Feed
 */
class FileManager
{
    /**
     * Default file mime types
     */
    const DEFAULT_JSON_FILE_MIME_TYPE = 'application/json';
    const DEFAULT_ZIP_FILE_MIME_TYPE = 'application/zip';

    /**
     * @var WriteInterface
     */
    private $dir;

    /**
     * @var string
     */
    private $subDir = 'unbxd';

    /**
     * @var array
     */
    private $defaultMimeTypes = [
        self::DEFAULT_JSON_FILE_MIME_TYPE,
        self::DEFAULT_ZIP_FILE_MIME_TYPE
    ];

    /**
     * @var string
     */
    private $fileName = null;

    /**
     * @var string
     */
    private $filePath = null;

    /**
     * @var string
     */
    private $contentFormat = null;

    /**
     * @var null
     */
    private $archiveFormat = null;

    /**
     * @var array
     */
    private $allowedMimeTypes = [];

    /**
     * FileManager constructor.
     * @param Filesystem $filesystem
     * @param null $fileName
     * @param null $contentFormat
     * @param null $archiveFormat
     * @param array $allowedMimeTypes
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        $fileName = null,
        $contentFormat = null,
        $archiveFormat = null,
        array $allowedMimeTypes = []
    ) {
        $this->fileName = $fileName;
        $this->contentFormat = $contentFormat;
        $this->archiveFormat = $archiveFormat;
        $this->filePath = sprintf(
            '%s%s%s.%s',
            $this->subDir,
            DIRECTORY_SEPARATOR,
             $this->fileName,
            $this->contentFormat
            );
        $this->allowedMimeTypes = array_unique(array_merge($this->defaultMimeTypes, array_values($allowedMimeTypes)));
        $this->dir = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * @param $string
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function write($string)
    {
        $stream = $this->dir->openFile($this->getFilePath(), 'a');
        $stream->lock();
        $stream->write($string);
        $stream->unlock();
        $stream->close();

        return $this;
    }

    /**
     * @return Filesystem\File\WriteInterface
     */
    public function getFileStream()
    {
        return $this->dir->openFile($this->getFilePath(), 'r');
    }

    /**
     * Check if feed file exist
     *
     * @return bool
     */
    public function isExist()
    {
        return $this->dir->isExist($this->getFilePath());
    }

    /**
     * Init file, if file not exist create new one with empty content
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function initFile()
    {
        if (!$this->isExist()) {
            // just create file with empty content
            $this->dir->writeFile($this->getFilePath(), '');
        }
    }

    /**
     * Retrieve file name
     *
     * @return string
     */
    public function getFileName()
    {
        return sprintf('%s.%s', $this->fileName, $this->contentFormat);
    }

    /**
     * Retrieve file content format
     *
     * @return string
     */
    public function getContentFormat()
    {
        return $this->contentFormat;
    }

    /**
     * Retrieve file archive format
     *
     * @return string
     */
    public function getArchiveFormat()
    {
        return $this->archiveFormat;
    }

    /**
     * Retrieve file sub path location
     *
     * @param bool $isArchive
     * @return string
     */
    private function getFilePath($isArchive = false)
    {
        $path = $this->filePath;
        if ($isArchive) {
            $path = preg_replace(
                sprintf('/\.%s$/i', $this->getContentFormat()),
                sprintf('.%s', $this->getArchiveFormat()),
                $path
            );
        }

        return $path;
    }

    /**
     * Retrieve file location
     *
     * @return string
     */
    public function getFileLocation()
    {
        return $this->dir->getAbsolutePath($this->getFilePath());
    }

    /**
     * Retrieve file content
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getFileContent()
    {
        $this->initFile();
        return $this->dir->getDriver()->fileGetContents($this->getFileLocation());
    }

    /**
     * Retrieve file size
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getFileSize()
    {
        $this->initFile();
        $fileStat = $this->dir->stat($this->getFileLocation());
        $size = isset($fileStat['size']) ? round($fileStat['size'], 2) : 0; // in bytes
        return $size;
    }

    /**
     * Flush file content
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function flushFileContent()
    {
        $this->deleteFile();
        $this->initFile();
    }

    /**
     * Delete file content
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function deleteFile()
    {
        $this->dir->getDriver()->deleteFile($this->getFileLocation());
    }

    /**
     * @return string|null
     */
    public function getMimeType()
    {
        $path = $this->getFileLocation();
        $ext = substr($path, strrpos($path, '.') + 1);
        $contentType = null;
        if ($ext == $this->contentFormat) {
            $contentType = self::DEFAULT_JSON_FILE_MIME_TYPE;
        } else if ($ext == $this->archiveFormat) {
            $contentType = self::DEFAULT_ZIP_FILE_MIME_TYPE;
        }

        return $this->isMimeTypeValid($contentType) ? $contentType : null;
    }

    /**
     * Check if given mime type is valid
     *
     * @param string $mimeType
     * @return bool
     */
    private function isMimeTypeValid($mimeType)
    {
        return in_array($mimeType, $this->allowedMimeTypes);
    }

    /**
     * Check if given filename is valid
     *
     * @param string $name
     * @return bool
     */
    public function isFileNameValid($name)
    {
        // cannot contain \ / : * ? " < > |
        if (!preg_match('/^[^\\/?*:";<>()|{}\\\\]+$/', $name)) {
            return false;
        }

        return true;
    }
}