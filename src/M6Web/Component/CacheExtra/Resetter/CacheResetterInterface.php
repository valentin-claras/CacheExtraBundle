<?php

namespace M6Web\Component\CacheExtra\Resetter;

/**
 * Interface qui décrit les méthodes que doit exposer un système de reset de cache
 * Ce cache resetter est injectable dans les objets de type cache interface
 */
interface CacheResetterInterface
{
    /**
     * Checks if the cache must be reset or not
     *
     * @return Boolean True if the cache must be clear or false otherwise
     */
    public function shouldResetCache();
}
