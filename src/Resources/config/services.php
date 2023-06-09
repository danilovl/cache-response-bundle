<?php declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Danilovl\CacheResponseBundle\EventListener\{
    KernelResponseListener,
    KernelControllerListener
};

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(KernelControllerListener::class, KernelControllerListener::class)
        ->autowire()
        ->autoconfigure()
        ->public();

    $container->services()
        ->set(KernelResponseListener::class, KernelResponseListener::class)
        ->autowire()
        ->autoconfigure()
        ->public();
};
