<?php

namespace Dedoc\Scramble\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('scramble');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('api_path')
                    ->defaultValue('/docs/api.json')
                    ->info('The path where the OpenAPI specification is served.')
                ->end()
                ->scalarNode('docs_path')
                    ->defaultValue('/docs/api')
                    ->info('The path where the API documentation UI is served.')
                ->end()
                ->arrayNode('info')
                    ->children()
                        ->scalarNode('version')->defaultValue('0.0.1')->end()
                        ->scalarNode('description')->defaultValue('')->end()
                    ->end()
                ->end()
                ->arrayNode('ui')
                    ->children()
                        ->scalarNode('title')->defaultValue('API')->end()
                    ->end()
                ->end()
                ->arrayNode('servers')
                    ->scalarPrototype()->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
