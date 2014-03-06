<?php
namespace M6Web\Bundle\CacheExtraBundle\Listener;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * This Listener listen for the first main request to associate to the Cache Resetter
 */
class CacheResetterListener
{
    private $cacheResetter;

    /**
     * Construct the listener with the specified cache reseter
     * @param Service $cacheResetter The cache Reseter service to attach the request
     */
    public function __construct($cacheResetter)
    {
        $this->cacheResetter = $cacheResetter;
    }

    /**
     * Method call on the first request
     * @param Event $event The kernel request event
     */
    public function onKernelRequest(Event $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST == $event->getRequestType()) {
            $this->cacheResetter->setRequest($event->getRequest());
        }
    }
}
