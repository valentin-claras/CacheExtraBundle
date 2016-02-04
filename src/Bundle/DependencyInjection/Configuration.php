<?php

namespace M6Web\Bundle\CacheExtraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('m6_cache_extra');

        $this->addActionCacheSection($rootNode);

        return $treeBuilder;
    }

    private function addActionCacheSection($rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('action_cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('debug')->defaultValue('%kernel.debug%')->end()
                        ->scalarNode('env')->defaultValue('%kernel.environment%')->end()
                        ->scalarNode('enabled')->defaultValue(false)->end()
                        ->scalarNode('service')->defaultValue(null)->end()
                    ->end()
                ->end()
                ->arrayNode('cache_resetter')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('param')->defaultValue('delete')->end()
                    ->end()
                ->end()
            ->end();
    }
}
