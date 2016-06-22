<?php

namespace OroCRMPro\Bundle\OutlookBundle\Manager;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class AddInManager
{
    const FILE_PATTERN     = 'OroCRMOutlookAddIn_*.*';
    const FILE_PREFIX      = 'OroCRMOutlookAddIn_';
    const CONFIG_FILE_NAME = 'config.yml';

    const FILE_TYPE_BINARY = 'binary';
    const FILE_TYPE_DOC    = 'doc';

    const CACHE_KEY = 'files';

    /** @var string */
    protected $addInDir;

    /** @var string */
    protected $addInBaseUri;

    /** @var string */
    protected $filePrefix;

    /** @var AssetHelper $assetHelper */
    protected $assetHelper;

    /** @var CacheProvider */
    protected $cache;

    /** @var array */
    protected $localCache;

    /**
     * @param string        $addInDir
     * @param string        $addInBaseUri
     * @param AssetHelper   $assetHelper
     * @param CacheProvider $cache
     */
    public function __construct(
        $addInDir,
        $addInBaseUri,
        AssetHelper $assetHelper,
        CacheProvider $cache
    ) {
        $this->addInDir = $addInDir;
        $this->addInBaseUri = $addInBaseUri;
        $this->assetHelper = $assetHelper;
        $this->cache = $cache;
    }

    /**
     * @return array The files are sorted by a version, the latest version is at the top
     *               [file name => file relative url, ...]
     */
    public function getBinaries()
    {
        $result = [];
        $data = $this->getData();
        foreach ($data['files'] as $version => $file) {
            $result[$file['name']] = $file['url'];
        }

        return $result;
    }

    /**
     * @return array
     *  [
     *      version => [
     *          'name'    => add-in file name,
     *          'url'     => add-in file relative url,
     *          'doc_url' => documentation file relative url // Optional
     *      ],
     *      ...
     *  ]
     */
    public function getFiles()
    {
        $data = $this->getData();

        return $data['files'];
    }

    /**
     * @param string $version
     *
     * @return array|null
     *  [
     *      'name'    => add-in file name,
     *      'url'     => add-in file relative url,
     *      'doc_url' => documentation file relative url // Optional
     *  ]
     */
    public function getFile($version)
    {
        $data = $this->getData();

        return isset($data['files'][$version])
            ? $data['files'][$version]
            : null;
    }

    /**
     * @return string[] The sorted versions, the latest version is at the top
     */
    public function getVersions()
    {
        $data = $this->getData();

        return array_keys($data['files']);
    }

    /**
     * @return string|null
     */
    public function getLatestVersion()
    {
        $data = $this->getData();
        reset($data['files']);

        return key($data['files']);
    }

    /**
     * @return string|null
     */
    public function getMinSupportedVersion()
    {
        $data = $this->getData();

        return array_key_exists('min_supported_version', $data)
            ? $data['min_supported_version']
            : null;
    }

    /**
     * Removes all data from the cache.
     */
    public function clearCache()
    {
        $this->cache->deleteAll();
        $this->localCache = null;
    }

    /**
     * @return array The files are sorted by a version, the latest version is at the top
     *  [
     *      'min_supported_version' => version, // Optional
     *      'files' => [
     *          version => [
     *              'name'    => add-in file name,
     *              'url'     => add-in file relative url,
     *              'doc_url' => documentation file relative url // Optional
     *          ],
     *          ...
     *      ]
     *  ]
     */
    protected function getData()
    {
        if (null !== $this->localCache) {
            return $this->localCache;
        }

        $data = $this->cache->fetch(self::CACHE_KEY);
        if (false === $data) {
            $data = $this->loadData();
            $this->cache->save(self::CACHE_KEY, $data);
        }
        $this->localCache = $data;

        return $data;
    }

    /**
     * @return array The files are sorted by a version, the latest version is at the top
     *  [
     *      'min_supported_version' => version, // Optional
     *      'files' => [
     *          version => [
     *              'name'    => add-in file name,
     *              'url'     => add-in file relative url,
     *              'doc_url' => documentation file relative url // Optional
     *          ],
     *          ...
     *      ]
     *  ]
     */
    protected function loadData()
    {
        $files = $this->loadFiles();
        uksort(
            $files,
            function ($a, $b) {
                return version_compare($a, $b, '<');
            }
        );

        $result = [];
        $configFilePath = realpath($this->addInDir . '/' . self::CONFIG_FILE_NAME);
        if ($configFilePath && is_file($configFilePath)) {
            $config = Yaml::parse(file_get_contents($configFilePath));
            if (!empty($config['min_supported_version'])) {
                $result['min_supported_version'] = $config['min_supported_version'];
            }
        }
        $resultFiles = [];
        foreach ($files as $version => $data) {
            $item = [];
            foreach ($data as $fileType => $fileName) {
                if (self::FILE_TYPE_BINARY === $fileType) {
                    $item['name'] = $fileName;
                    $item['url'] = $this->getFileUrl($fileName);
                } elseif (self::FILE_TYPE_DOC === $fileType) {
                    $item['doc_url'] = $this->getFileUrl($fileName);
                }
            }
            $resultFiles[$version] = $item;
        }
        $result['files'] = $resultFiles;

        return $result;
    }

    /**
     * @return array
     *  [
     *      version => [
     *          file type => file name,
     *          ...
     *      ],
     *      ...
     *  ]
     */
    protected function loadFiles()
    {
        $result = [];
        $finder = new Finder();
        $files = $finder->name(self::FILE_PATTERN)->in($this->addInDir);
        /** @var \SplFileInfo[] $files */
        foreach ($files as $file) {
            $fileType = $this->getFileType($file->getExtension());
            if ($fileType) {
                $fileVersion = $this->getFileVersion($file->getFilename(), $file->getExtension());
                $result[$fileVersion][$fileType] = $file->getFilename();
            }
        }

        return $result;
    }

    /**
     * @param string $fileName
     * @param string $fileExtension
     *
     * @return string
     */
    protected function getFileVersion($fileName, $fileExtension)
    {
        return substr($fileName, strlen(self::FILE_PREFIX), -strlen($fileExtension) - 1);
    }

    /**
     * @param string $fileExtension
     *
     * @return string|null
     */
    protected function getFileType($fileExtension)
    {
        switch (strtolower($fileExtension)) {
            case 'exe':
                return self::FILE_TYPE_BINARY;
            case 'md':
                return self::FILE_TYPE_DOC;
        }

        return null;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    protected function getFileUrl($fileName)
    {
        return $this->assetHelper->getUrl($this->addInBaseUri . '/' . $fileName);
    }
}
