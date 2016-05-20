<?php

namespace Konst\UploaderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('konst_uploader');


        $rootNode
            ->children()
                ->arrayNode('parameters')
                    ->children()
                        ->arrayNode('file_upload_rules')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('format')->end()
                                    ->scalarNode('max')->end()
                                    ->scalarNode('stopwords')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('servers_list')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('type')->end()
                                    ->scalarNode('host')->end()
                                    ->scalarNode('port')->end()
                                    ->scalarNode('access')->end()
                                    ->scalarNode('path')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
