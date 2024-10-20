<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Command;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\Service\CacheService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{
    InputInterface,
    InputOption
};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'danilovl:cache-response:clear', description: 'Clear cache response.')]
class CacheResponseClearCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly CacheItemPoolInterface $cacheItemPool,
        private readonly CacheService $cacheService
    ) {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function configure(): void
    {
        $this->addOption('all', null, InputOption::VALUE_OPTIONAL, 'Delete all cache items.')
            ->addOption('cacheKey', null, InputOption::VALUE_OPTIONAL, 'Delete only by cache key.')
            ->addOption('similarCacheKey', null, InputOption::VALUE_OPTIONAL, 'Delete all similar cache key.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $all = $input->getOption('all');
        /** @var string|null $deleteCacheKey */
        $deleteCacheKey = $input->getOption('cacheKey');
        /** @var string|null $deleteSimilarCacheKey */
        $deleteSimilarCacheKey = $input->getOption('similarCacheKey');

        if ($all === null && $deleteCacheKey === null && $deleteSimilarCacheKey === null) {
            $this->io->error('You must specify at least one option: all, cacheKey, similarCacheKey.');

            return Command::FAILURE;
        }

        if ($deleteCacheKey !== null && empty($deleteCacheKey)) {
            $this->io->error('cacheKey option must not be empty.');

            return Command::FAILURE;
        }

        if ($deleteSimilarCacheKey !== null && empty($deleteSimilarCacheKey)) {
            $this->io->error('similarCacheKey option must not be empty.');

            return Command::FAILURE;
        }

        if ($all) {
            $this->cacheItemPool->deleteItems($this->cacheService->getCacheKeys());
            $this->cacheItemPool->deleteItem(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS);

            $this->io->success('Done.');

            return Command::SUCCESS;
        }

        if ($deleteCacheKey || $deleteSimilarCacheKey) {
            $deleteCacheKeys = [];

            if ($deleteCacheKey) {
                $deleteCacheKey = CacheResponseAttribute::getCacheKeyWithPrefix($deleteCacheKey);
                if (!$this->cacheService->isCacheKeyExistInCache($deleteCacheKey)) {
                    $this->io->warning('Cache key not found or actually cache for this key is already empty.');

                    return Command::FAILURE;
                }

                $deleteCacheKeys[] = $deleteCacheKey;
            }

            if ($deleteSimilarCacheKey) {
                $similarCacheKeys = $this->cacheService->findSimilarCacheKeys($deleteSimilarCacheKey);
                if (count($similarCacheKeys) === 0) {
                    $this->io->warning('Cache key not found or actually cache for this key is already empty.');

                    return Command::FAILURE;
                }

                $deleteCacheKeys = array_merge($deleteCacheKeys, $similarCacheKeys);
            }

            $this->cacheItemPool->deleteItems($deleteCacheKeys);

            $attributeCacheKeysItem = $this->cacheItemPool->getItem(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS);

            /** @var array $cacheKeysItem */
            $cacheKeysItem = $attributeCacheKeysItem->get();
            $newCacheKeys = array_diff($cacheKeysItem, $deleteCacheKeys);

            $attributeCacheKeysItem->set($newCacheKeys);
            $this->cacheItemPool->save($attributeCacheKeysItem);
        }

        $this->io->success('Done.');

        return Command::SUCCESS;
    }
}
