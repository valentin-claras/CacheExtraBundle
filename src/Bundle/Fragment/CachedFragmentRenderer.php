<?php
namespace M6Web\Bundle\CacheExtraBundle\Fragment;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;

/**
 * CachedFragmentRenderer
 */
class CachedFragmentRenderer extends InlineFragmentRenderer
{
    /**
     * {@inheritdoc}
     */
    public function render($uri, Request $request, array $options = array())
    {
        $options['ignore_errors'] = false;

        return parent::render($uri, $request, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function createSubRequest($uri, Request $request)
    {
        $subRequest = parent::createSubRequest($uri, $request);

        $subRequest->attributes->set('server_cache', true);

        return $subRequest;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'cached';
    }
}
