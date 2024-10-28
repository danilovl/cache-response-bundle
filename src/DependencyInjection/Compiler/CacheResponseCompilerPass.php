<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\DependencyInjection\Compiler;

use Danilovl\CacheResponseBundle\Command\CacheResponseClearCommand;
use Danilovl\CacheResponseBundle\DependencyInjection\Configuration;
use Danilovl\CacheResponseBundle\EventSubscriber\CacheResponseSubscriber;
use Danilovl\CacheResponseBundle\Service\CacheService;
use InvalidArgumentException;
use Danilovl\CacheResponseBundle\EventListener\{
    KernelResponseListener,
    KernelControllerListener
};
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Config\Definition\{
    Processor,
    ConfigurationInterface
};
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
        /** @var string $class */
        $class = $cacheServiceContainer->getClass();
        $implements = class_implements($class, false);
        $implementCacheItemPoolInterface = $implements[CacheItemPoolInterface::class] ?? false;

        if (!$implementCacheItemPoolInterface) {
            $message = sprintf(
                'The service "%s" must implement "%s".',
                $cacheService,
                CacheItemPoolInterface::class
            );

            throw new InvalidArgumentException($message);
        }

        $definitions = [
            CacheService::class,
            CacheResponseClearCommand::class,
            KernelControllerListener::class,
            KernelResponseListener::class,
            CacheResponseSubscriber::class
        ];

        foreach ($definitions as $definition) {
            $definition = $container->getDefinition($definition);
            $definition->setArgument(0, $cacheServiceContainer);
        }
    }

    private function processConfiguration(ConfigurationInterface $configuration, array $configs): array
    {
        return (new Processor)->processConfiguration($configuration, $configs);
    }
}
