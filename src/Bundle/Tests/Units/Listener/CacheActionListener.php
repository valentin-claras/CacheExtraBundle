<?php
namespace M6Web\Bundle\CacheExtraBundle\Listener\Tests\Units;

use \mageekguy\atoum;
use M6Web\Bundle\CacheExtraBundle\Listener\CacheActionListener as BaseCacheActionListener;

use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * classe testant le CacheActionListener
 */
class CacheActionListener extends atoum\test
{
    const CACHE_ENV        = 'test';
    const CACHE_NAMESPACE  = 'TestCacheAction/';
    const CACHE_TIMEOUT    = 42;
    const RESPONSE_CONTENT = 'contenu de test';
    const RESPONSE_MAXAGE  = 42;

    /**
     * @return \mock\M6Web\Component\CacheExtraBundle\CacheInterface
     */
    protected function buildMockCacheInterface()
    {
        $cache = new \mock\M6Web\Component\CacheExtra\CacheInterface;

        return $cache;
    }

    /**
     * @param string  $type    Type de request
     * @param Request $request Request
     *
     * @return \mock\Symfony\Component\HttpKernel\Event\GetResponseEvent
     */
    protected function buildMockEvent($type, $request)
    {
        $kernelInterface = new \mock\Symfony\Component\HttpKernel\HttpKernelInterface();

        $event = new \mock\Symfony\Component\HttpKernel\Event\GetResponseEvent(
            $kernelInterface,
            $request,
            $type
        );

        return $event;
    }

    /**
     * @param ParameterBag $attributes
     *
     * @return \mock\Symfony\Component\HttpFoundation\Request
     */
    protected function buildMockRequest($attributes)
    {
        $request = new \mock\Symfony\Component\HttpFoundation\Request();
        $request->attributes = $attributes;

        return $request;
    }

    /**
     * @param array $param
     *
     * @return \mock\Symfony\Component\HttpFoundation\ParameterBag
     */
    protected function buildMockAttributes(array $param)
    {
        $attributes = new \mock\Symfony\Component\HttpFoundation\ParameterBag($param);

        return $attributes;
    }

    /**
     * @param integer $satusCode
     *
     * @return \mock\Symfony\Component\HttpFoundation\Response
     */
    protected function buildMockResponse($satusCode = 200)
    {
        $response = new \mock\Symfony\Component\HttpFoundation\Response(
            self::RESPONSE_CONTENT,
            $satusCode,
            $headers = [
                'cache-control' => 'max-age:'.self::RESPONSE_MAXAGE
            ]
        );

        return $response;
    }

    /**
     * @param string   $type     Type de la requete
     * @param Request  $request  Request
     * @param Response $response Reponse
     *
     * @return \mock\Symfony\Component\HttpKernel\HttpKernelInterface
     */
    protected function buildMockFilterEvent($type, $request, $response)
    {
        $kernelInterface = new \mock\Symfony\Component\HttpKernel\HttpKernelInterface();

        $event = new \mock\Symfony\Component\HttpKernel\Event\FilterResponseEvent(
            $kernelInterface,
            $request,
            $type,
            $response
        );

        return $event;
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    protected function getCacheKey(array $parameters)
    {
        ksort($parameters);

        return self::CACHE_ENV.'-'.md5(json_encode($parameters));
    }

    /**
     * @return null
     */
    public function dataProvider()
    {
        return [
            [
                [
                    'server_cache' => true,
                    'controllerName' => 'FakeController',
                    'id' => 42,
                    'tableau' => ['test' => 'value'],
                ],
                [
                    'server_cache' => true,
                    'controllerName' => 'FakeController',
                    'id' => 42,
                    'tableau' => ['test' => 'value'],
                ]
            ],
            [
                [
                    'server_cache' => true,
                    'controllerName' => 'FakeController',
                    'id' => 42,
                    '_template' => 'Template',
                ],
                [
                    'server_cache' => true,
                    'controllerName' => 'FakeController',
                    'id' => 42,
                ]
            ],
            [
                [
                    'server_cache' => true,
                    'controllerName' => 'FakeController',
                    'id' => 42,
                    'tableau' => ['test' => ['foo' => 'bar']],
                ],
                [
                    'server_cache' => true,
                    'controllerName' => 'FakeController',
                    'id' => 42,
                    'tableau' => ['test' => ['foo' => 'bar']],
                ]
            ],
        ];
    }

    /**
     * test Master request
     *
     * @param array $requestAttributes Attributs de la requete
     * @param array $keyParameters     Parametre de la clef
     *
     * @return void
     *
     * @dataProvider dataProvider
     */
    public function testRequest(array $requestAttributes, array $keyParameters)
    {
        $cacheListener = new BaseCacheActionListener(false, self::CACHE_ENV);

        $attributes = $this->buildMockAttributes($requestAttributes);

        $request = $this->buildMockRequest($attributes);
        $response = $this->buildMockResponse();

        $event = $this->buildMockEvent(HttpKernelInterface::MASTER_REQUEST, $request);

        $filterEvent = $this->buildMockFilterEvent(HttpKernelInterface::MASTER_REQUEST, $request, $response);

        $cache = $this->buildMockCacheInterface();

        $cacheListener->setCacheService($cache);
        $cacheListener->setCacheKeyExclude(array('_template'));
        $cacheListener->onKernelRequest($event);

        $this
            ->mock($event)
                ->call('getRequest')
                    ->never()
        ;

        $cacheListener->onKernelResponse($filterEvent);

        $this
            ->mock($response)
                ->call('getStatusCode')
                    ->never()
        ;
    }

    /**
     * test Sub request
     *
     * @param array $requestAttributes Attributs de la requete
     * @param array $keyParameters     Parametre de la clef
     *
     * @return void
     *
     * @dataProvider dataProvider
     */
    public function testSubRequest(array $requestAttributes, array $keyParameters)
    {
        $cacheListener = new BaseCacheActionListener(false, self::CACHE_ENV);

        $attributes = $this->buildMockAttributes($requestAttributes);

        $request = $this->buildMockRequest($attributes);
        $response = $this->buildMockResponse();

        $event = $this->buildMockEvent(HttpKernelInterface::SUB_REQUEST, $request);
        $filterEvent = $this->buildMockFilterEvent(HttpKernelInterface::SUB_REQUEST, $request, $response);

        $cacheService = $this->buildMockCacheInterface();

        $cacheListener->setCacheService($cacheService);
        $cacheListener->setCacheKeyExclude(array('_template'));
        $cacheListener->onKernelRequest($event);

        $cacheKey = $this->getCacheKey($keyParameters);

        $this
            ->mock($event)
                ->call('getRequest')
                    ->once()

            ->mock($cacheService)
                ->call('getConcurrent')
                    ->withArguments($cacheKey)
                        ->once()
        ;

        $cacheListener->onKernelResponse($filterEvent);

        $this
            ->mock($response)
                ->call('getStatusCode')
                    ->once()

            ->mock($cacheService)
                ->call('setConcurrent')
                    ->withArguments($cacheKey, self::RESPONSE_CONTENT, self::RESPONSE_MAXAGE)
                        ->once()
        ;
    }

    /**
     * test
     *
     * @return void
     */
    public function testException()
    {
        $object = new \mock\ObjectFake();
        $object->getMockController()->getParam = 1;

        $requestAttributes = [
            'server_cache' => true,
            'controllerName' => 'FakeController',
            'id' => 42,
            '_template' => 'Template',
            'object' => $object,
        ];

        $cacheListener = new BaseCacheActionListener(false, self::CACHE_ENV);

        $attributes = $this->buildMockAttributes($requestAttributes);

        $request = $this->buildMockRequest($attributes);

        $event = $this->buildMockEvent(HttpKernelInterface::SUB_REQUEST, $request);

        $cache = $this->buildMockCacheInterface();

        $cacheListener->setCacheService($cache);
        $cacheListener->setCacheKeyExclude(['_template']);

        $this
            ->exception(function() use ($event, $cacheListener) {
                $cacheListener->onKernelRequest($event);
            })
                ->isInstanceOf('M6Web\Component\CacheExtra\CacheException')
                ->message
                    ->match('#Request parameter "(.*)" is not valid#');
    }
}
