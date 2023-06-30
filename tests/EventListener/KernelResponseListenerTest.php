<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\EventListener;

use Danilovl\CacheResponseBundle\EventListener\KernelResponseListener;
use Danilovl\CacheResponseBundle\Tests\TestController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
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
                attributes: ['_controller' => TestController::class . '::index'],
            ),
            HttpKernelInterface::MAIN_REQUEST,
            (new TestController())->index()
        );

        $cacheItemPool = new ArrayAdapter;
        $cacheItemKey = $cacheItemPool->getItem('index');
        $cacheItemKey->set((new TestController())->index());
        $cacheItemPool->save($cacheItemKey);

        $subscriber = new KernelResponseListener($cacheItemPool);
        $subscriber->onKernelResponse($event);

        $this->assertTrue(true);
    }
}
