<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Services\StorageServiceInterface;
use ArsamMe\Wallet\Exceptions\RecordNotFoundException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class StorageService implements StorageServiceInterface {
    private const PREFIX = 'wallet_sg::';

    public function __construct(
        private readonly CacheRepository $cacheRepository,
        private readonly ?int $ttl
    ) {}

    public function flush(): bool {
        return $this->cacheRepository->clear();
    }

    public function forget(string $uuid): bool {
        return $this->cacheRepository->forget(self::PREFIX.$uuid);
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
            $keys[self::PREFIX.$uuid] = $uuid;
        }

        $missingKeys = [];
        if (1 === count($keys)) {
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
            if (null === $value) {
                $missingKeys[] = $uuid;

                continue;
            }

            $results[$uuid] = $value;
        }

        if ([] !== $missingKeys) {
            throw new RecordNotFoundException(
                'The repository did not find the object',
                ExceptionInterface::RECORD_NOT_FOUND,
                $missingKeys
            );
        }

        assert([] !== $results);

        return $results;
    }

    public function multiSync(array $inputs): bool {
        $values = [];
        foreach ($inputs as $uuid => $value) {
            $values[self::PREFIX.$uuid] = $value;
        }

        if (1 === count($values)) {
            return $this->cacheRepository->set(key($values), current($values), $this->ttl);
        }

        return $this->cacheRepository->setMultiple($values, $this->ttl);
    }
}
