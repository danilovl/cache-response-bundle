<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle;

use Danilovl\CacheResponseBundle\DependencyInjection\CacheResponseExtension;
use Danilovl\CacheResponseBundle\DependencyInjection\Compiler\CacheResponseCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CacheResponseBundle extends Bundle
{
    public function getContainerExtension(): CacheResponseExtension
    {
        return new CacheResponseExtension;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CacheResponseCompilerPass);
    }
}
