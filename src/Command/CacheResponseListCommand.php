<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Command;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\Service\CacheService;
use DateInterval;
use DateTimeInterface;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'danilovl:cache-response:list', description: 'List of cache response attributes.')]
class CacheResponseListCommand extends Command
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly RouterInterface $router
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $routes = $this->router->getRouteCollection()->all();

        $table = (new Table($output))
            ->setHeaders(['Controller', 'Action', 'Сache info', 'Expires after', 'Expires at', 'With query', 'Wth request']);

        foreach ($routes as $route) {
            $controller = $route->getDefault('_controller');
            if (!str_contains($controller, '::')) {
                continue;
            }

            [$controller, $method] = explode('::', $controller);

            if (!class_exists($controller)) {
                continue;
            }

            $attributes = (new ReflectionClass($controller))
                ->getMethod($method)
                ->getAttributes(CacheResponseAttribute::class);

            $attributes = $attributes[0] ?? null;
            if ($attributes === null) {
                continue;
            }

            /** @var CacheResponseAttribute $attribute */
            $attribute = $attributes->newInstance();
            $actuallyInCache = $this->cacheService->findSimilarCacheKeys($attribute->cacheKey);

            $table->addRow([
                $controller,
                $method,
                'Original cache key:',
                $this->getFormattedExpiration($attribute->expiresAfter),
                $this->getFormattedExpiration($attribute->expiresAt),
                $attribute->cacheKeyWithQuery ? 'yes' : 'no',
                $attribute->cacheKeyWithRequest ? 'yes' : 'no'
            ]);

            $table->addRow([null, null, $attribute->originalCacheKey, null, null, null, null]);
            $table->addRow([null, null, null, null, null, null, null]);
            $table->addRow([null, null, 'Attribute cache key:', null, null, null, null]);
            $table->addRow([null, null, $attribute->cacheKey, null, null, null, null]);
            $table->addRow([null, null, null, null, null, null, null]);
            $table->addRow([null, null, 'Actually in cache:', null, null, null, null]);
            $table->addRow([null, null, implode(PHP_EOL, $actuallyInCache), null, null, null, null]);
            $table->addRow([null, null, null, null, null, null, null]);
        }

        $table->render();

        return Command::SUCCESS;
    }

    function getFormattedExpiration(int|DateInterval|null $expiration): mixed
    {
        $result = 'no';

        if (is_int($expiration)) {
            $result = $expiration;
        } elseif ($expiration instanceof DateTimeInterface) {
            $result = $expiration->format('Y-m-d H:i:s');
        }

        return $result;
    }
}
