<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Attribute;

use Attribute;
use Danilovl\CacheResponseBundle\Exception\CacheResponseInvalidArgumentException;
use Danilovl\CacheResponseBundle\Interfaces\CacheKeyFactoryInterface;
use DateInterval;
use DateTimeInterface;
use Symfony\Component\HttpFoundation\Request;

#[Attribute(Attribute::TARGET_METHOD)]
readonly class CacheResponseAttribute
{
    public const string CACHE_KEY_PREFIX = 'danilovl.cache_response.';
    public const string REQUEST_ATTRIBUTES_CACHE_USED = 'danilovl.cache_response_used';

    public ?string $originalCacheKey;

    public ?string $cacheKey;

    public function __construct(
        ?string $cacheKey = null,
        public ?string $cacheKeyFactory = null,
        public int|DateInterval|null $expiresAfter = null,
        public ?DateTimeInterface $expiresAt = null,
        public bool $cacheKeyWithQuery = false,
        public bool $cacheKeyWithRequest = false,
        public bool $env = false
    ) {
        if ($cacheKey === null && $cacheKeyFactory === null) {
            throw new CacheResponseInvalidArgumentException('CacheKey or CacheKeyFactory is required.');
        }

        if ($cacheKey !== null) {
            $this->originalCacheKey = $cacheKey;
            $this->cacheKey = self::CACHE_KEY_PREFIX . sha1($cacheKey);
        } else {
            $this->originalCacheKey = null;
            $this->cacheKey = null;
        }

        if ($cacheKeyFactory !== null) {
            $interfaces = class_implements($cacheKeyFactory);
            if ($interfaces !== false && !in_array(CacheKeyFactoryInterface::class, $interfaces)) {
                throw new CacheResponseInvalidArgumentException('Class CacheKeyFactory is not implemented CacheKeyFactoryInterface.');
            }
        }
    }

    public function getCacheKeyNotNull(): string
    {
        if ($this->cacheKey === null) {
            throw new CacheResponseInvalidArgumentException('CacheKey can not be null.');
        }

        return $this->cacheKey;
    }

    public function getCacheKeyForRequest(Request $request): string
    {
        if ($this->cacheKey === null) {
            throw new CacheResponseInvalidArgumentException('CacheKey is required when CacheKeyFactory is not set.');
        }

        if (!$this->cacheKeyWithQuery && !$this->cacheKeyWithRequest && !$this->env) {
            return $this->cacheKey;
        }

        $cacheKey = $this->cacheKey;
        if ($this->cacheKeyWithQuery) {
            $queryAll = $request->query->all();
            if (count($queryAll) > 0) {
                $cacheKey .= '.' . sha1(serialize($queryAll));
            }
        }

        if ($this->cacheKeyWithRequest) {
            $requestAll = $request->request->all();
            if (count($requestAll) > 0) {
                $cacheKey .= '.' . sha1(serialize($requestAll));
            }
        }

        if ($this->env) {
            /** @var string|null $appEnv */
            $appEnv = $request->server->get('APP_ENV');
            if ($appEnv) {
                $cacheKey .= '.' . $appEnv;
            }
        }

        return $cacheKey;
    }

    public static function getCacheKeyWithPrefix(string $cacheKey): string
    {
        if (self::isCacheKeyContainsPrefix($cacheKey)) {
            return $cacheKey;
        }

        return self::CACHE_KEY_PREFIX . $cacheKey;
    }

    public static function isCacheKeyContainsPrefix(string $cacheKey): bool
    {
        return str_contains($cacheKey, self::CACHE_KEY_PREFIX);
    }
}
