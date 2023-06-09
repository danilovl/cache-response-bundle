<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\EventListener;

use Danilovl\CacheResponseBundle\EventListener\KernelResponseListener;
use Danilovl\CacheResponseBundle\Tests\TestController;
use PHPUnit\Framework\TestCase;
use Psr\Cache\{
    CacheItemInterface,
    CacheItemPoolInterface
};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\{
    Event\ResponseEvent,
    KernelInterface,
    HttpKernelInterface
};

class KernelResponseListenerTest extends TestCase
{
    public function testOnKernelResponseExistCache(): void
    {
        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(
                attributes: ['_controller' => 'Danilovl\CacheResponseBundle\Tests\TestController::index'],
            ),
            HttpKernelInterface::MAIN_REQUEST,
            (new TestController())->index()
        );

        $cacheItemInterface = $this->createMock(CacheItemInterface::class);
        $cacheItemInterface->method('isHit')->willReturn(true);
        $cacheItemInterface->method('get')->willReturn((new TestController())->index());

        $cacheItemPoolInterface = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPoolInterface->method('getItem')->willReturn($cacheItemInterface);

        $subscriber = new KernelResponseListener($cacheItemPoolInterface);
        $subscriber->onKernelResponse($event);

        $this->assertTrue(true);
    }
}
