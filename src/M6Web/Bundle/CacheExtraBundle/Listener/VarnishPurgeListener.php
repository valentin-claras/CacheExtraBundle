<?php
namespace M6Web\Bundle\CacheExtraBundle\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use M6Web\Component\CacheExtra\Resetter\CacheResetterInterface;

/**
 * Listen to the cache.reset event to clear related varnish server cache
 */
class VarnishPurgeListener implements EventSubscriberInterface
{
    protected $logger;
    protected $cacheResetter;
    protected $purgeHelper;

    /**
     * Construct the service with the given servers
     *
     * @param CacheResetterInterface $cacheResetter cache resetter service
     * @param object                 $purgeHelper   Purge Helper
     * @param object                 $logger        logger service
     */
    public function __construct(CacheResetterInterface $cacheResetter, $purgeHelper = null, $logger = null)
    {
        $this->logger        = $logger;
        $this->purgeHelper   = $purgeHelper;
        $this->cacheResetter = $cacheResetter;
    }

    /**
     * Purge varnish servers when a kernel.request event is dispatched
     *
     * @return boolean
     */
    public function onKernelRequest()
    {
        if (!$this->purgeHelper) {
            return false;
        }

        $request = $this->cacheResetter->getRequest();

        if (!$request) {
            return false;
        }

        $url = $this->cleanUrl($request->getRequestUri());

        if (false === $url) {
            return false;
        }

        if ($this->cacheResetter->shouldResetCache()) {
            $this->purgeHelper->purgeUrl($url, $request);

            // log
            if ($this->logger) {
                $this->logger->log('VARNISH PURGE : '.$url, 'VarnishPurger');
            }

        } elseif ($this->logger) {
            if (!$this->cacheResetter->isWhiteListed()) {
                $this->logger->log('your IP : '.$request->getClientIp().' is not allowed', 'VarnishPurger');
            }

        }

        return true;
    }

    /**
     * Clean an URL by removing the clear cache param
     *
     * @param string $url URL
     *
     * @return string|boolean
     *
     * @throws \Exception
     */
    protected function cleanUrl($url)
    {
        if ($turl = parse_url($url)) {
            if (isset($turl['scheme']) or isset($turl['host'])) {
                throw new \Exception("scheme or host are here ? : ".implode(' ', $turl));
            }

            // Rebuild url without querystring
            $url = $turl['path'];
            if (!empty($turl['query'])) {
                $tquery = array();
                $param  = $this->cacheResetter->getParamName();

                // Remove cache parameters
                foreach (explode('&', $turl['query']) as $argVal) {
                    $regex = sprintf("/^%s(\=[^\&\#]+)?$/", $param);

                    if (!preg_match($regex, $argVal)) {
                        $tquery[] = $argVal;
                    }
                }

                if (count($tquery)) {
                    $url .= '?'.implode('&', $tquery);
                }
            }
        } else {
            return false;
        }

        return $url;
    }

    /**
     * Return static list of subscribed events
     *
     * @return array List of events we want to subscribe to
     */
    public static function getSubscribedEvents()
    {
        return array(
            'kernel.request' => array(
                array('onKernelRequest', 0),
            ),
        );
    }
}
