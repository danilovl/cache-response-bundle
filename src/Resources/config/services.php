<?php declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('$container', service('service_container'))
        ->public();

    $container->services()
        ->load('Danilovl\\CacheResponseBundle\\', '../../../src')
        ->exclude([
            '../../../src/Attribute',
            '../../../src/DependencyInjection',
            '../../../src/EventSubscriber/Event',
            '../../../src/Resources'
        ]);
};
