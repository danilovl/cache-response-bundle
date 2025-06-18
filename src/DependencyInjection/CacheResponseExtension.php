<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\DependencyInjection;

use Danilovl\CacheResponseBundle\EventListener\{
    KernelResponseListener,
    KernelControllerListener
};
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class CacheResponseExtension extends Extension
{
    private const string DIR_CONFIG = '/../Resources/config';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . self::DIR_CONFIG));
        $loader->load('services.yaml');

        $configuration = new Configuration;
        $config = $this->processConfiguration($configuration, $configs);

        $kernelControllerPriority = $config['kernel_controller_priority'];
        if (!empty($kernelControllerPriority)) {
            KernelControllerListener::$priority = $kernelControllerPriority;
        }

        $kernelResponsePriority = $config['kernel_response_priority'];
        if (!empty($kernelResponsePriority)) {
            KernelResponseListener::$priority = $kernelResponsePriority;
        }
    }

    public function getAlias(): string
    {
        return Configuration::ALIAS;
    }
}
