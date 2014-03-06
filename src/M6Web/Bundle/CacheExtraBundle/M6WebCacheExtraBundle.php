<?php

namespace M6Web\Bundle\CacheExtraBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * M6WebCacheExtraBundle class
 */
class M6WebCacheExtraBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new DependencyInjection\M6WebCacheExtraExtension();
    }
}
