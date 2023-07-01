<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Interfaces;

interface CacheKeyFactoryInterface
{
    public function getCacheKey(): string;
}
