<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\EventListener;

use Danilovl\CacheResponseBundle\EventListener\KernelControllerListener;
use Danilovl\CacheResponseBundle\Tests\TestController;
use PHPUnit\Framework\TestCase;
use Psr\Cache\{
    CacheItemInterface,
    CacheItemPoolInterface
};
use Symfony\Component\HttpFoundation\{
    Request,
    Response
};
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\{
    KernelInterface,
    HttpKernelInterface
};

class KernelControllerListenerTest extends TestCase
{
    public function testOnKernelResponseExistCache(): void
    {
        $event = new ControllerEvent(
            $this->createMock(KernelInterface::class),
            [new TestController, 'index'],
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $cacheItemInterface = $this->createMock(CacheItemInterface::class);
        $cacheItemInterface->method('isHit')->willReturn(true);
        $cacheItemInterface->method('get')->willReturn(new Response('content'));

        $cacheItemPoolInterface = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPoolInterface->method('getItem')->willReturn($cacheItemInterface);

        $subscriber = new KernelControllerListener($cacheItemPoolInterface);
        $subscriber->onKernelController($event);

        /** @var Response $response */
        $response = $event->getController()();

        $this->assertEquals('content', $response->getContent());
    }

    public function testOnKernelResponseNotExistCache(): void
    {
        $eventController = [new TestController, 'index'];

        $event = new ControllerEvent(
            $this->createMock(KernelInterface::class),
            $eventController,
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $cacheItemInterface = $this->createMock(CacheItemInterface::class);
        $cacheItemInterface->method('isHit')->willReturn(false);

        $cacheItemPoolInterface = $this->createMock(CacheItemPoolInterface::class);

        $subscriber = new KernelControllerListener($cacheItemPoolInterface);
        $subscriber->onKernelController($event);

        $this->assertEquals($eventController, $event->getController());
    }
}
