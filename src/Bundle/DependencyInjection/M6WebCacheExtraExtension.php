<?php
namespace M6Web\Bundle\CacheExtraBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class M6WebCacheExtraExtension extends Extension
{
    protected $twigExtension = array('renderCache');

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        if ($config['action_cache']['enabled']) {
            $this->loadActionCacheConfiguration(
                $container,
                $config['action_cache']['service'],
                $config['action_cache']['debug'],
                $config['action_cache']['env']
            );
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('data_collector.yml');
        $loader->load('services.yml');

        if (isset($config['cache_resetter'])) {
            $container->setParameter('cache_resetter.cache_clear_param', $config['cache_resetter']['param']);
            $loader->load('cache_resetter.yml');
        }
    }

    /**
     * Load the cache action configuration
     *
     * @param ContainerBuilder $container    The container
     * @param string           $cacheService The cache service to use
     * @param boolean          $debug        Is debug mode activated ?
     * @param string           $env          Kernel env
     */
    protected function loadActionCacheConfiguration(ContainerBuilder $container, $cacheService, $debug, $env)
    {
        if (!$container->hasParameter('m6.listener.cache_action.excludekeys')) {
            $container->setParameter(
                'm6.listener.cache_action.excludekeys',
                ['_template', '_cache', '_method']
            );
        }

        $definition = new Definition('%m6.action_cache.listener.class%');
        $definition->addArgument($debug);
        $definition->addArgument($env);
        $definition->addMethodCall('setCacheService', array(new Reference($cacheService)));
        $definition->addMethodCall('setCacheKeyExclude', array($container->getParameter('m6.listener.cache_action.excludekeys')));
        $definition->addTag('kernel.event_subscriber');
        $container->setDefinition('m6.action_cache.listener', $definition);
    }


    /**
     * @return string
     */
    public function getAlias()
    {
        return 'm6_cache_extra';
    }
}
