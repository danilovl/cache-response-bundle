<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\{
    TreeBuilder,
    NodeParentInterface
};
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ALIAS = 'danilovl_cache_response';

    public function getConfigTreeBuilder(): NodeParentInterface
    {
        $treeBuilder = new TreeBuilder(self::ALIAS);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('cache_service')
                    ->defaultNull()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
