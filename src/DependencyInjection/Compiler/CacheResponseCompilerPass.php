<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\DependencyInjection\Compiler;

use Danilovl\CacheResponseBundle\DependencyInjection\Configuration;
use InvalidArgumentException;
use Danilovl\CacheResponseBundle\EventListener\{
    KernelResponseListener,
    KernelControllerListener
};
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Processor;

class CacheResponseCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig(Configuration::ALIAS);
        $configuration = new Configuration;
        $config = $this->processConfiguration($configuration, $configs);

        $this->injectCacheService($container, $config);
    }

    private function injectCacheService(ContainerBuilder $container, array $config): void
    {
        $cacheService = $config['cache_service'] ?? null;
        if ($cacheService === null) {
            return;
        }

        $cacheServiceContainer = $container->getDefinition($cacheService);
        $implements = class_implements($cacheServiceContainer->getClass(), false);
        $implementCacheItemPoolInterface = $implements[CacheItemPoolInterface::class] ?? false;

        if (!$implementCacheItemPoolInterface) {
            $message = sprintf(
                'The service "%s" must implement "%s".',
                $cacheService,
                CacheItemPoolInterface::class
            );

            throw new InvalidArgumentException($message);
        }

        $kernelControllerListener = $container->getDefinition(KernelControllerListener::class);
        $kernelControllerListener->setArgument(0, $cacheServiceContainer);

        $kernelResponseListener = $container->getDefinition(KernelResponseListener::class);
        $kernelResponseListener->setArgument(0, $cacheServiceContainer);
    }

    private function processConfiguration(ConfigurationInterface $configuration, array $configs): array
    {
        return (new Processor)->processConfiguration($configuration, $configs);
    }
}
