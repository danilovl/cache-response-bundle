<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\EventListener;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\Interfaces\CacheKeyFactoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class KernelControllerListener implements EventSubscriberInterface
{
    public function __construct(
        private CacheItemPoolInterface $cacheItemPool,
        private ContainerInterface $container
    ) {}

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!is_array($controllers = $event->getController())) {
            return;
        }

        [$controller, $method] = $controllers;

        $this->resolve($controller, $method, $event);
    }

    private function resolve(object $controller, string $method, ControllerEvent $event): void
    {
        $attributes = (new ReflectionClass($controller))
            ->getMethod($method)
            ->getAttributes(CacheResponseAttribute::class);

        $attributes = $attributes[0] ?? null;
        if ($attributes === null) {
            return;
        }

        /** @var CacheResponseAttribute $attribute */
        $attribute = $attributes->newInstance();
        $this->handleRequest($event, $attribute);
    }

    private function handleRequest(
        ControllerEvent $event,
        CacheResponseAttribute $hashidsParamConverterAttribute
    ): void {
        $request = $event->getRequest();
        $request->attributes->set(CacheResponseAttribute::REQUEST_ATTRIBUTES_CACHE_USED, false);

        if ($hashidsParamConverterAttribute->cacheKeyFactory !== null) {
            /** @var CacheKeyFactoryInterface $cacheFactory */
            $cacheFactory = $this->container->get($hashidsParamConverterAttribute->cacheKeyFactory);
            $cacheKey = $cacheFactory->getCacheKey();
        } else {
            $cacheKey = $hashidsParamConverterAttribute->getCacheKeyForRequest($request);
        }

        $cache = $this->cacheItemPool->getItem($cacheKey);
        if (!$cache->isHit()) {
            return;
        }

        $cacheResponse = $cache->get();
        if (!$cacheResponse instanceof Response) {
            return;
        }

        $event->setController(static fn(): Response => $cacheResponse);
        $event->getRequest()->attributes->set(CacheResponseAttribute::REQUEST_ATTRIBUTES_CACHE_USED, true);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController'
        ];
    }
}
