<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Attribute;

use Attribute;
use Danilovl\CacheResponseBundle\Exception\CacheResponseInvalidArgumentException;
use Danilovl\CacheResponseBundle\Interfaces\CacheKeyFactoryInterface;
use DateInterval;
use DateTimeInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

#[Attribute(Attribute::TARGET_METHOD)]
readonly class CacheResponseAttribute
{
    public const string CACHE_KEY_PREFIX = 'danilovl.cache_response.';

    public const string REQUEST_ATTRIBUTES_CACHE_USED = 'danilovl.cache_response_used';
    public const string REQUEST_ATTRIBUTES_CACHE_IGNORE = 'danilovl.cache_response_ignore';

    public ?string $originalCacheKey;

    public ?string $key;

    public function __construct(
        ?string $key = null,
        public ?string $factory = null,
        public ?string $cacheAdapter = null,
        public int|DateInterval|null $expiresAfter = null,
        public ?DateTimeInterface $expiresAt = null,
        public bool $useSession = false,
        public bool $useRoute = false,
        public bool $useQuery = false,
        public bool $useRequest = false,
        public bool $useEnv = false,
        public bool $disableOnQuery = false,
        public bool $disableOnRequest = false,
    ) {
        if ($key === null && $factory === null) {
            throw new CacheResponseInvalidArgumentException('CacheKey or CacheKeyFactory is required.');
        }

        if ($key !== null) {
            $this->originalCacheKey = $key;
            $this->key = self::CACHE_KEY_PREFIX . self::hash($key);
        } else {
            $this->originalCacheKey = null;
            $this->key = null;
        }

        if ($factory !== null) {
            $interfaces = class_implements($factory);
            if ($interfaces !== false && !in_array(CacheKeyFactoryInterface::class, $interfaces)) {
                throw new CacheResponseInvalidArgumentException('Class CacheKeyFactory is not implemented CacheKeyFactoryInterface.');
            }
        }

        if ($cacheAdapter !== null) {
            $implements = class_implements($cacheAdapter);
            $implementCacheItemPoolInterface = $implements[CacheItemPoolInterface::class] ?? false;

            if (!$implementCacheItemPoolInterface) {
                $message = sprintf(
                    'The cache adapter "%s" must implement "%s".',
                    $cacheAdapter,
                    CacheItemPoolInterface::class
                );

                throw new CacheResponseInvalidArgumentException($message);
            }
        }
    }

    public function getCacheKeyNotNull(): string
    {
        if ($this->key === null) {
            throw new CacheResponseInvalidArgumentException('CacheKey can not be null.');
        }

        return $this->key;
    }

    public function getCacheKeyForRequest(Request $request): string
    {
        if ($this->key === null) {
            throw new CacheResponseInvalidArgumentException('CacheKey is required when CacheKeyFactory is not set.');
        }

        $data = [];

        if ($this->useSession) {
            $data['session'] = $request->getSession()->getId();
        }

        if ($this->useRoute) {
            $route = $request->attributes->getString('_route');
            if ($route === '') {
                throw new CacheResponseInvalidArgumentException('Route _route can not be empty when useRoute is true.');
            }

            /** @var array $routeParams */
            $routeParams = $request->attributes->get('_route_params', []);
            $data['route'] = $route;

            if (count($routeParams) > 0) {
                $data['routeParams'] = $routeParams;
            }
        }

        if ($this->useQuery) {
            $queryAll = $request->query->all();
            if (count($queryAll) > 0) {
                $data['query'] = $queryAll;
            }
        }

        if ($this->useRequest) {
            $requestAll = $request->request->all();
            if (count($requestAll) > 0) {
                $data['request'] = $requestAll;
            }
        }

        if ($this->useEnv) {
            $appEnv = $request->server->get('APP_ENV');
            if (empty($appEnv)) {
                throw new CacheResponseInvalidArgumentException('APP_ENV can not be empty when useEnv is true.');
            }

            $data['env'] = $appEnv;
        }

        if (count($data) === 0) {
            return $this->key;
        }

        $dataHash = self::hash(serialize($data));

        return $this->key . '.' . $dataHash;
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

    public static function hash(string $data): string
    {
        return hash('xxh3', $data);
    }
}
