<?php

namespace OroCRM\Bundle\MagentoBundle\Service;

use Guzzle\Http\ClientInterface;

use Symfony\Component\Filesystem\Filesystem;

class WsdlManager
{
    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var ClientInterface
     */
    protected $guzzleClient;

    /**
     * @var string
     */
    protected $cachePath;

    /**
     * @param Filesystem $filesystem
     * @param ClientInterface $guzzleClient
     * @param string $cachePath
     */
    public function __construct(Filesystem $filesystem, ClientInterface $guzzleClient, $cachePath)
    {
        $this->fs = $filesystem;
        $this->guzzleClient = $guzzleClient;
        $this->cachePath = $cachePath;
    }

    /**
     * Load remote WSDL to local cache.
     *
     * @param string $url
     * @return string
     */
    public function loadWsdl($url)
    {
        $response = $this->guzzleClient->get($url)->send();

        $cacheFilePath = $this->getCachedWsdlPath($url);
        $this->fs->dumpFile($cacheFilePath, $response->getBody(true));

        return $cacheFilePath;
    }

    /**
     * Check that cache is loaded.
     *
     * @param string $url
     * @return bool
     */
    public function isCacheLoaded($url)
    {
        return $this->fs->exists($this->getCachedWsdlPath($url));
    }

    /**
     * Get cache WSDL path.
     *
     * @param string $url
     * @return string
     */
    public function getCachedWsdlPath($url)
    {
        return $this->getWsdlCachePath() . DIRECTORY_SEPARATOR . md5($url) . '.wsdl';
    }

    /**
     * Remove cached WSDL by URL.
     *
     * @param string $url
     */
    public function clearCacheForUrl($url)
    {
        $path = $this->getCachedWsdlPath($url);
        if ($this->fs->exists($path)) {
            $this->fs->remove($path);
        }
    }

    /**
     * Refresh WSDL cache by URL.
     *
     * @param string $url
     * @return string
     */
    public function refreshCacheForUrl($url)
    {
        $this->clearCacheForUrl($url);

        return $this->loadWsdl($url);
    }

    /**
     * Get WSDL cache path.
     *
     * @return string
     */
    public function getWsdlCachePath()
    {
        return $this->cachePath . DIRECTORY_SEPARATOR . 'oro' . DIRECTORY_SEPARATOR . 'wsdl.cache';
    }

    /**
     * Remove all cached WSDLs.
     */
    public function clearAllWsdlCaches()
    {
        $path = $this->getWsdlCachePath();
        if ($this->fs->exists($path)) {
            $this->fs->remove($path);
        }
    }
}
