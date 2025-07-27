<?php

namespace AliRaghebi\Wallet\Services;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use AliRaghebi\Wallet\Contracts\Services\StorageServiceInterface;
use AliRaghebi\Wallet\Exceptions\RecordNotFoundException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

readonly class StorageService implements StorageServiceInterface {
    public function __construct(
        private CacheRepository $cacheRepository,
        private ?int $ttl,
        private string $prefix = 'wallet_sg::'
    ) {}

    public function flush(): bool {
        return $this->cacheRepository->clear();
    }

    public function forget(string $uuid): bool {
        return $this->cacheRepository->forget($this->prefix.$uuid);
    }

    public function get(string $uuid): mixed {
        return current($this->multiGet([$uuid]));
    }

    public function sync(string $uuid, mixed $value): bool {
        return $this->multiSync([
            $uuid => $value,
        ]);
    }

    public function multiGet(array $uuids): array {
        $keys = [];
        foreach ($uuids as $uuid) {
            $keys[$this->prefix.$uuid] = $uuid;
        }

        $missingKeys = [];
        if (count($keys) === 1) {
            $values = [];
            foreach (array_keys($keys) as $key) {
                $values[$key] = $this->cacheRepository->get($key);
            }
        } else {
            $values = $this->cacheRepository->getMultiple(array_keys($keys));
        }

        $results = [];
        /** @var array<float|int|non-empty-string|null> $values */
        foreach ($values as $key => $value) {
            $uuid = $keys[$key];
            if ($value === null) {
                $missingKeys[] = $uuid;

                continue;
            }

            $results[$uuid] = $value;
        }

        if ($missingKeys !== []) {
            throw new RecordNotFoundException(
                'The repository did not find the object',
                ExceptionInterface::RECORD_NOT_FOUND,
                $missingKeys
            );
        }

        assert($results !== []);

        return $results;
    }

    public function multiSync(array $inputs): bool {
        $values = [];
        foreach ($inputs as $uuid => $value) {
            $values[$this->prefix.$uuid] = $value;
        }

        if (count($values) === 1) {
            return $this->cacheRepository->set(key($values), current($values), $this->ttl);
        }

        return $this->cacheRepository->setMultiple($values, $this->ttl);
    }
}
