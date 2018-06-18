<?php

namespace Phue\Application;

/**
 * Cache trait
 */
trait CacheTrait
{

    protected $cache = [];

    /**
     * Retrieves or sets a cache value identified by $cacheId and built by a builder callable
     *
     * @param string $cacheId
     * @param callable $builder
     * @param bool $fresh
     *
     * @return mixed
     */
    protected function getCacheValue($cacheId, $builder, $fresh = false)
    {
        if ($fresh || !isset($this->cache[$cacheId])) {
            $this->cache[$cacheId] = call_user_func($builder);
        }

        return $this->cache[$cacheId];
    }
}
