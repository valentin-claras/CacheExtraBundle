<?php
namespace M6Web\Bundle\CacheExtraBundle\Resetter;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

use M6Web\Bundle\FirewallBundle\Firewall\Provider;
use M6Web\Component\CacheExtra\Resetter\CacheResetterInterface;

/**
 * The Cache resetter say if the cache must be purge or not on a given CacheInterface
 * It use the request object
 */
class CacheResetter implements CacheResetterInterface
{
    protected $eventDispatcher;
    protected $allowedIps = [];
    protected $paramName;
    protected $request;
    protected $shouldReset;
    protected $allowed;
    protected $firewallProvider;

    /**
     * Construct the cache resetter
     * 
     * @param EventDispatcherInterface $eventDispatcher  The Event Dispatcher to dispatch the cache.reset event
     * @param string                   $paramName        Name of the parameter to check to purge
     * @param Provider                 $firewallProvider Firewall provider (Factory)
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, $paramName, Provider $firewallProvider = null)
    {
        $this->eventDispatcher  = $eventDispatcher;
        $this->paramName        = $paramName;
        $this->firewallProvider = $firewallProvider;
    }

    /**
     * Add allowed IPs
     *
     * @param array $allowedIps Allowed Ips array
     *
     * @return $this
     */
    public function addAllowedIps(array $allowedIps)
    {
        foreach ($allowedIps as $allowedIp) {
            if (is_array($allowedIp)) {
                $this->addAllowedIps($allowedIp);
            } else {
                if (!in_array($allowedIp, $this->allowedIps)) {
                    $this->allowedIps[] = $allowedIp;
                }
            }
        }

        return $this;
    }

    /**
     * Get Allowed IPs
     *
     * @return array
     */
    public function getAllowedIps()
    {
        return $this->allowedIps;
    }

    /**
     * Set the request object
     * 
     * @param Request $request The Request object
     *
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get the request object
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the resetter param name
     *
     * @return string
     */
    public function getParamName()
    {
        return $this->paramName;
    }

    /**
     * This function must say if the cache should be reset or not
     *
     * @return boolean True if the cache must be reset or false if not
     */
    public function shouldResetCache()
    {
        if (!$this->getRequest()) {
            return false;
        }

        if ($this->shouldReset !== null) {
            return $this->shouldReset;
        }

        $whiteListed   = $this->isWhiteListed();
        $hasClearParam = $this->hasClearingParam($this->getRequest());

        $this->shouldReset = $hasClearParam && $whiteListed;

        return $this->shouldReset;
    }

    /**
     * Check if the request has the clearing param in it
     *
     * @return boolean True if we have the param
     */
    public function hasClearingParam()
    {
        return $this->request->query->has($this->getParamName());
    }

    /**
     * Check if the request has an allowed ip in his REMOTE_ADDR
     *
     * @return boolean Return true if Request is allowed
     */
    public function isWhiteListed()
    {
        if (!$this->firewallProvider) {
            return true;
        }

        if ($this->allowed !== null) {
            return $this->allowed;
        }

        if ($this->request !== null) {
            $firewall = $this->firewallProvider->getFirewall(null, array(), $this->request);
        } else {
            $firewall = $this->firewallProvider->getFirewall();
        }

        $firewall->setDefaultState(false);
        $firewall->addList($this->allowedIps, 'whitelist', true);
        $firewall->setThrowError(false);

        return $this->allowed = $firewall->handle();
    }
}
