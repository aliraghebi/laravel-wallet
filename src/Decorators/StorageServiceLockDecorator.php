<?php

namespace ArsamMe\Wallet\Decorators;

use ArsamMe\Wallet\Contracts\Repositories\StateServiceInterface;
use ArsamMe\Wallet\Contracts\Services\LockServiceInterface;
use ArsamMe\Wallet\Contracts\Services\StorageServiceInterface;

readonly class StorageServiceLockDecorator implements StorageServiceInterface {
    public function __construct(
        private StorageServiceInterface $storageService,
        private LockServiceInterface $lockService,
        private StateServiceInterface $stateService,
    ) {}

    public function flush(): bool {
        return $this->storageService->flush();
    }

    public function forget(string $uuid): bool {
        return $this->storageService->forget($uuid);
    }

    public function get(string $uuid): mixed {
        return $this->storageService->get($uuid);
    }

    public function sync(string $uuid, mixed $value): bool {
        return $this->storageService->sync($uuid, $value);
    }

    public function multiGet(array $uuids): array {
        $missingKeys = [];
        $results = [];
        foreach ($uuids as $uuid) {
            $item = $this->stateService->get($uuid);
            if ($item === null) {
                $missingKeys[] = $uuid;

                continue;
            }

            $results[$uuid] = $item;
        }

        if ($missingKeys !== []) {
            $foundValues = $this->storageService->multiGet($missingKeys);
            foreach ($foundValues as $key => $value) {
                $results[$key] = $value;
            }
        }

        assert($results !== []);

        return $results;
    }

    public function multiSync(array $inputs): bool {
        return $this->lockService->blocks(
            array_keys($inputs),
            function () use ($inputs) {
                return $this->storageService->multiSync($inputs);
            }
        );
    }
}
