<?php
namespace M6Web\Bundle\CacheExtraBundle\Tests\Units\Resetter;

use \mageekguy\atoum;
use M6Web\Bundle\CacheExtraBundle\Resetter\CacheResetter as CacheResetterClass;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * classe testant le cacheResetter
 */
class CacheResetter extends atoum\test
{
    public function testInstanciate()
    {
        $cacheResetter = new CacheResetterClass(
            $this->getDispatcherMock(),
            'delete',
            $this->getFirewallProviderMock($this->getContainerMock())
        );

        $this
            ->assert

                // Should reset cache ?

                ->boolean($cacheResetter->shouldResetCache())
                    ->isIdenticalTo(false)

                // Purge Parameter

                ->string($cacheResetter->getParamName())
                    ->isIdenticalTo('delete')

                // Should reset cache ?

                ->boolean($cacheResetter->shouldResetCache())
                    ->isIdenticalTo(false)

                // Allowed IPs

                ->object($cacheResetter->addAllowedIps(array()))
                    ->isIdenticalTo($cacheResetter)
                ->array($cacheResetter->getAllowedIps())
                    ->isEmpty()

                // Should reset cache ?

                ->boolean($cacheResetter->shouldResetCache())
                    ->isIdenticalTo(false)

                // Request

                ->object($cacheResetter->setRequest($request = $this->getRequestMock()))
                    ->isIdenticalTo($cacheResetter)
                ->object($cacheResetter->getRequest())
                    ->isIdenticalTo($request)

                // Should reset cache ?

                ->boolean($cacheResetter->shouldResetCache())
                    ->isIdenticalTo(false)
                ;
    }

    public function testHasClearingParam()
    {
        // Paramétrage

        $request   = $this->getRequestMock();
        $container = $this->getContainerMock();
        $container->getMockController()->get = function($serviceName) use ($request) {
                switch ($serviceName) {
                    case 'request':
                        return $request;
                }
            };

        // Test

        $cacheResetter = new CacheResetterClass(
            $this->getDispatcherMock(),
            'delete',
            $this->getFirewallProviderMock($container)
        );

        $this
            ->assert
                ->object($cacheResetter->setRequest($request))
                    ->isIdenticalTo($cacheResetter)
                ->object($cacheResetter->getRequest())
                    ->isIdenticalTo($request)
                ->string($cacheResetter->getParamName())
                    ->isIdenticalTo('delete')
                ->boolean($cacheResetter->hasClearingParam())
                    ->isIdenticalTo(false)
        ;

        $request->query->set('delete', '');

        $this
            ->assert
                ->boolean($cacheResetter->hasClearingParam())
                    ->isTrue()

                // Should reset cache ?
                ->boolean($cacheResetter->shouldResetCache())
                    ->isFalse()
        ;
    }

    public function testHasBadClearingParam()
    {
        // Paramétrage

        $request   = $this->getRequestMock();
        $container = $this->getContainerMock();
        $container->getMockController()->get = function($serviceName) use ($request) {
                switch ($serviceName) {
                    case 'request':
                        return $request;
                }
            };

        // Test

        $cacheResetter = new CacheResetterClass(
            $this->getDispatcherMock(),
            'delete',
            $this->getFirewallProviderMock($container)
        );

        $this
            ->assert
                ->object($cacheResetter->setRequest($request))
                    ->isIdenticalTo($cacheResetter)
                ->object($cacheResetter->getRequest())
                    ->isIdenticalTo($request)
                ->string($cacheResetter->getParamName())
                    ->isIdenticalTo('delete')
                ->boolean($cacheResetter->hasClearingParam())
                    ->isIdenticalTo(false)
        ;

        $request->query->set('kikoolol', '');

        $this
            ->assert
                ->boolean($cacheResetter->hasClearingParam())
                    ->isIdenticalTo(false)

                // Should reset cache ?

                ->boolean($cacheResetter->shouldResetCache())
                    ->isIdenticalTo(false)
                ;
        ;
    }

    /**
     * @dataProvider whiteListedProvider
     *
     * test le IsWhiteLister
     * @return void
     */
    public function testIsWhiteListed($clientIp, $allowedIps, $allowed)
    {
        // Paramétrage

        $request = $this->getRequestMock();
        $request->getMockController()->getClientIp = function () use ($clientIp) {
                return $clientIp;
            };

        $container = $this->getContainerMock();
        $container->getMockController()->get = function($serviceName) use ($request) {
                switch ($serviceName) {
                    case 'request':
                        return $request;
                }
            };

        // Test

        $cacheResetter = new CacheResetterClass(
            $this->getDispatcherMock(),
            'delete',
            $this->getFirewallProviderMock($container)
        );

        $cacheResetter->addAllowedIps($allowedIps);

        $this
            ->assert
                ->string($cacheResetter->getParamName())
                    ->isIdenticalTo('delete')
                ->boolean($cacheResetter->isWhiteListed())
                    ->isEqualTo($allowed)

                // Should reset cache ?

                ->boolean($cacheResetter->shouldResetCache())
                    ->isIdenticalTo(false)
                ;
    }

    /**
     * @dataProvider whiteListedProvider
     *
     * test le IsWhiteLister
     * @return void
     */
    public function testIsWhiteListedNoFirewall($clientIp, $allowedIps, $allowed)
    {
        // Paramétrage

        $request = $this->getRequestMock();

        // Test

        $cacheResetter = new CacheResetterClass(
            $this->getDispatcherMock(),
            'delete'
        );

        $this
            ->assert
                ->string($cacheResetter->getParamName())
                    ->isIdenticalTo('delete')
                ->boolean($cacheResetter->isWhiteListed())
                    ->isEqualTo(true)

                // Should reset cache ?

                ->boolean($cacheResetter->shouldResetCache())
                    ->isIdenticalTo(false)
                ;
    }

    /**
     * @dataProvider whiteListedProvider
     *
     * test le IsWhiteLister
     * @return void
     */
    public function testIsWhiteListedAndHasClearingParam($clientIp, $allowedIps, $allowed)
    {
        // Paramétrage

        $request = $this->getRequestMock();
        $request->getMockController()->getClientIp = function () use ($clientIp) {
                return $clientIp;
            };
        $request->query->set('delete', "");

        $container = $this->getContainerMock();
        $container->getMockController()->get = function($serviceName) use ($request) {
                switch ($serviceName) {
                    case 'request':
                        return $request;
                }
            };

        // Test

        $cacheResetter = new CacheResetterClass(
            $this->getDispatcherMock(),
            'delete',
            $this->getFirewallProviderMock($container)
        );

        $this
            ->assert
                ->object($cacheResetter->addAllowedIps($allowedIps))
                    ->isIdenticalTo($cacheResetter)
                ->object($cacheResetter->setRequest($request))
                    ->isIdenticalTo($cacheResetter)
                ->string($cacheResetter->getParamName())
                    ->isIdenticalTo('delete')
                ->boolean($cacheResetter->hasClearingParam())
                    ->isIdenticalTo(true)
                ->boolean($cacheResetter->isWhiteListed())
                    ->isEqualTo($allowed)
        ;

        // Should reset cache ?
        $this
            ->boolean($cacheResetter->shouldResetCache())
                ->isIdenticalTo($allowed)
        ;
    }

    /**
     * @dataProvider whiteListedProvider
     *
     * test le IsWhiteLister
     * @return void
     */
    public function testIsWhiteListednoFirewallAndHasClearingParam($clientIp, $allowedIps, $allowed)
    {
        // Paramétrage

        $request = $this->getRequestMock();
        $request->query->set('delete', "");

        // Test

        $cacheResetter = new CacheResetterClass(
            $this->getDispatcherMock(),
            'delete'
        );

        $this
            ->assert
                ->object($cacheResetter->setRequest($request))
                    ->isIdenticalTo($cacheResetter)
                ->string($cacheResetter->getParamName())
                    ->isIdenticalTo('delete')
                ->boolean($cacheResetter->hasClearingParam())
                    ->isIdenticalTo(true)
                ->boolean($cacheResetter->isWhiteListed())
                    ->isEqualTo(true)
        ;

        // Should reset cache ?

        $this
            ->boolean($cacheResetter->shouldResetCache())
                ->isIdenticalTo(true)
        ;
    }

    /**
     * Data provider pour testIsWhiteListed
     */
    protected function whiteListedProvider()
    {
        return array(
            array(
                "127.0.0.1",
                array('127.0.0.1'),
                true
            ),
            array(
                "127.0.0.1",
                array('2a01:a580:2:*', '141.138.90.*'),
                false
            ),
            array(
                "2a01:a580:2:2000:6132:9769:fac3:c47c",
                array('2a01:a580:2::/48'),
                true
            )
        );
    }

    protected function getDispatcherMock()
    {
        $dispatcher = new \mock\Symfony\Component\EventDispatcher\EventDispatcher();

        return $dispatcher;
    }

    protected function getContainerMock()
    {
        $container = new \mock\Symfony\Component\DependencyInjection\ContainerInterface();

        return $container;
    }

    protected function getRequestMock()
    {
        $request = new \mock\Symfony\Component\HttpFoundation\Request();

        return $request;
    }

    protected function getFirewallProviderMock($container)
    {
        $provider = new \mock\M6Web\Bundle\FirewallBundle\Firewall\Provider($container);

        return $provider;
    }
}
