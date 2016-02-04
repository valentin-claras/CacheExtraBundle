<?php
namespace M6Web\Bundle\CacheExtraBundle\Listener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use M6Web\Component\CacheExtra\CacheInterface;
use M6Web\Component\CacheExtra\CacheException;

/**
 * Cache action listener allowed to cache render bloc server side using a cache service
 */
class CacheActionListener implements EventSubscriberInterface
{
    private $cacheService;
    private $cachedBlocks;
    private $debug;
    private $env;

    /**
     * Exlure ces variables de la clef de cache
     * @var array
     */
    private $excludeKey;

    /**
     * Constructor.
     *
     * @param boolean $debug active or not debug mode
     * @param string  $env   kernel env
     */
    public function __construct($debug, $env)
    {
        $this->cachedBlocks = [];
        $this->debug        = $debug;
        $this->env          = $env;
        $this->excludeKey   = [];
    }

    /**
     * Set the cache service
     *
     * @param CacheInterface $cacheService The cache service to use to store response
     */
    public function setCacheService(CacheInterface $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * @param array $exclude tableau des clefs
     */
    public function setCacheKeyExclude(array $exclude)
    {
        $this->excludeKey = $exclude;
    }

    /**
     * Method called on kernel.request event. Handle only "subrequest"
     * @param KernelEvent $event The received event
     *
     * @return void
     */
    public function onKernelRequest(KernelEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST == $event->getRequestType() || null === $this->cacheService) {
            return;
        }

        $request = $event->getRequest();
        if ($request->attributes->has('server_cache')) {
            $cacheKey = $this->getRequestCacheKey($request);
            $request->attributes->set('cache_key', $cacheKey);

            $controller = $request->attributes->get('controllerName');
            $fromCache  = false;

            $responseContent = $this->cacheService->getConcurrent($cacheKey);
            if ($responseContent || ($responseContent === '' &&  !$request->attributes->get('ignore_errors')) ) {

                $response = new Response($responseContent);
                $response->headers->set('server_cached', 1);
                $event->setResponse($response);

                if ($this->debug) {
                    $this->decorateResponse($request, $response, $controller, true);
                }

                $fromCache = true;
            }

            $this->cachedBlocks[$controller.' - key : '.$cacheKey] = $fromCache;
        }
    }

    /**
     * Method called on kernel.response event. Handle only if server_cache attribute is set
     *
     * @param FilterResponseEvent $event The handled event
     *
     * @return void
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();
        // If request is the master one, or if we don't have a cache service or if the response has already been server cached
        if (HttpKernelInterface::MASTER_REQUEST == $event->getRequestType() || null === $this->cacheService || $response->headers->has('server_cached')) {
            return;
        }
        if ($request->attributes->has('server_cache') && $response->getStatusCode() == 200) {

            $cacheKey = $request->attributes->get('cache_key');
            $ttl      = $response->headers->getCacheControlDirective('max-age');
            $this->cacheService->setConcurrent($cacheKey, $response->getContent(), $ttl);
            if ($this->debug) {
                $this->decorateResponse($request, $response, $request->attributes->get('_controller'), false, $ttl);
            }
        }
    }

    /**
     * Generate a cache key for a given request based on attribute parameters bag
     *
     * @param Request $request The request object
     *
     * @return string The cache key to use
     *
     * @throws \Exception
     */
    private function getRequestCacheKey(Request $request)
    {
        $p          = $request->attributes->all();
        $parameters = array();
        foreach ($p as $k => $v) {

            // On ne prend pas ces clefs
            if (in_array($k, $this->excludeKey)) {
                continue;
            }

            if ($this->validateRequestParameter($v)) {
                $parameters[$k] = $v;
            } else {
                throw new CacheException(sprintf('Request parameter "%s" is not valid', $k));
            }
        }
        ksort($parameters);

        if (count($parameters) < 1) {
            throw new \Exception("Can't generate cache key from request without attributes");
        }

        return $this->env.'-'.md5(json_encode($parameters));
    }

    /**
     * Request parameter must only contain scalar values
     *
     * @param mixed $value Parameter value
     *
     * @return bool
     */
    private function validateRequestParameter($value)
    {
        if (null == $value) {
            return true;
        }

        if (is_array($value)) {

            foreach ($value as $v) {
                if (!$this->validateRequestParameter($v)) {
                    return false;
                }
            }
        } elseif (!is_scalar($value)) {
            return false;
        }

        return true;
    }

    /**
     * Add a div layer around a cached block
     *
     * @param Request  $request        The request object
     * @param Response $response       The response object to decorate
     * @param string   $controllerName Name of the associated controller
     * @param boolean  $fromCache      Do the data have been grab from cache ?
     * @param integer  $ttl            Ttl of the current cache block
     *
     * @return string The decorated content
     */
    private function decorateResponse(Request $request, Response $response, $controllerName, $fromCache = false, $ttl = 0)
    {
        $cacheKey = $this->getRequestCacheKey($request);
        $content  = $response->getContent();
        $cssClass =  $fromCache ? 'hit' : 'miss';

        $html  = '';
        $html .= '<div class="cachedBlock">';
        $html .=    $content;
        $html .= '  <div class="cb-overlay cb-'.$cssClass.'">';
        $html .= '    <div>';
        $html .= '      <span>'.$controllerName.'</span>';

        if ($ttl) {
            $html .= '      <span><b>TTL :</b> '.$ttl.' sec.</span>';
        }

        $html .= '      <span><b>key :</b> '.$cacheKey.'</span>';
        $html .= '    </div>';
        $html .= '  </div>';
        $html .= '</div>';

        $response->setContent($html);
    }

    /**
     * Return static list of subscribed events
     *
     * @return array List of events we want to subscribe to
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::REQUEST  => 'onKernelRequest'
        );
    }

    /**
     * Return list of cached blocks with status
     *
     * @return array list of cached controller with status
     */
    public function getCachedBlocks()
    {
        return $this->cachedBlocks;
    }
}
