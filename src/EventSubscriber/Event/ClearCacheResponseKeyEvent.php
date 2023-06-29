<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\EventSubscriber\Event;

readonly class ClearCacheResponseKeyEvent
{
    public function __construct(public string $key) {}
}
