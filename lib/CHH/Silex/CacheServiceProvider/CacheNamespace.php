<?php

namespace CHH\Silex\CacheServiceProvider;

use Doctrine\Common\Cache\Cache;

/**
 * Provides non conflicting access to a common cache instance.
 *
 * @author Christoph Hochstrasser <christoph.hochstrasser@gmail.com>
 */
class CacheNamespace implements Cache
{
    /** @var \Doctrine\Common\Cache\Cache */
    protected $cache;
    protected $namespace;

    /** @var int Current Namespace version */
    protected $namespaceVersion;

    /** @const Key to store the namespace's version */
    const NAMESPACE_CACHE_KEY = "CHH_Silex_CacheServiceProvider_CacheNamespaceVersion[%s]";

    /**
     * Constructor
     *
     * @param string $namespace
     * @param Cache  $cache
     */
    function __construct($namespace, Cache $cache)
    {
        $this->namespace = $namespace;
        $this->cache = $cache;
    }

    function getNamespace()
    {
        return $this->namespace;
    }

    function contains($id)
    {
        return $this->cache->contains($this->getNamespaceId($id));
    }

    function fetch($id)
    {
        return $this->cache->fetch($this->getNamespaceId($id));
    }

    function save($id, $data, $lifeTime = 0)
    {
        return $this->cache->save(
            $this->getNamespaceId($id),
            $data,
            $lifeTime
        );
    }

    function delete($id)
    {
        return $this->cache->delete($this->getNamespaceId($id));
    }

    function getStats()
    {
        return $this->cache->getStats();
    }

    function incrementNamespaceVersion()
    {
        $version = $this->getNamespaceVersion();
        $version += 1;

        $this->namespaceVersion = $version;

        $this->cache->save($this->getNamespaceCacheKey($this->namespace), $this->namespaceVersion);
    }

    protected function getNamespaceId($id)
    {
        return sprintf("%s[%s][%s]", $this->namespace, $id, $this->getNamespaceVersion());
    }

    protected function getNamespaceCacheKey($namespace)
    {
        return sprintf(self::NAMESPACE_CACHE_KEY, $namespace);
    }

    protected function getNamespaceVersion()
    {
        if (null !== $this->namespaceVersion) {
            return $this->namespaceVersion;
        }

        $namespaceCacheKey = $this->getNamespaceCacheKey($this->namespace);
        $namespaceVersion = $this->cache->fetch($namespaceCacheKey);

        if (false === $namespaceVersion) {
            $namespaceVersion = 1;
            $this->cache->save($namespaceCacheKey, $namespaceVersion);
        }

        $this->namespaceVersion = $namespaceVersion;

        return $this->namespaceVersion;
    }
}
