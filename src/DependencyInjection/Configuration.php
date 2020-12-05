<?php

namespace Scopeli\SymfonyCommons\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('scopeli_symfony_commons');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('translation')
                    ->children()
                        ->scalarNode('default_locale')->end()
                    ->end()
                ->end() // translation
            ->end()
        ;

        return $treeBuilder;
    }
}
