<?php

namespace Vivait\LicensingClientBundle\DependencyInjection;

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
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('vivait_licensing_client');
        $rootNode
            ->children()
                ->scalarNode('client_id')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('client_secret')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('debug')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('app_name')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('token_url')
                    ->isRequired()
                ->end()
                ->scalarNode('check_url')
                    ->isRequired()
                ->end()
            ->end()
        ;


        return $treeBuilder;
    }
}
