<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\EventListener;

use Danilovl\CacheResponseBundle\EventListener\KernelControllerListener;
use Danilovl\CacheResponseBundle\Tests\TestController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{
    Request,
    Response
};
use Symfony\Component\Cache\Adapter\ArrayAdapter;
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

        $cacheItemPool = new ArrayAdapter;
        $cacheItemKey = $cacheItemPool->getItem('index');
        $cacheItemKey->set(new Response('content'));
        $cacheItemPool->save($cacheItemKey);

        $subscriber = new KernelControllerListener($cacheItemPool);
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

        $subscriber = new KernelControllerListener((new ArrayAdapter));
        $subscriber->onKernelController($event);

        $this->assertEquals($eventController, $event->getController());
    }
}
