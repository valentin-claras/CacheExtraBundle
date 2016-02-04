<?php
namespace M6Web\Bundle\CacheExtraBundle\Resetter\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event represent a Cache Reset Event and hold the Request
 */
class CacheResetEvent extends Event
{
    protected $request;

    /**
     * Set the request object
     * @param Request $request The request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the Request object
     * @return Request The request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
