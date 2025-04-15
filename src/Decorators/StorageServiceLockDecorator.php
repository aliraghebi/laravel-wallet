<?php

namespace ArsamMe\Wallet\Decorators;

use ArsamMe\Wallet\Contracts\Services\LockServiceInterface;
use ArsamMe\Wallet\Contracts\Services\StorageServiceInterface;

readonly class StorageServiceLockDecorator implements StorageServiceInterface {
    public function __construct(
        private StorageServiceInterface $storageService,
        private LockServiceInterface $lockService
    ) {}

    public function flush(): bool {
        return $this->storageService->flush();
    }

    public function forget(string $uuid): bool {
        return $this->storageService->forget($uuid);
    }

    public function get(string $uuid, ?string $class = null): mixed {
        return $this->storageService->get($uuid, $class);
    }

    public function sync(string $uuid, mixed $value): bool {
        return $this->storageService->sync($uuid, $value);
    }

    public function multiGet(array $uuids, ?string $class = null): array {
        return $this->storageService->multiGet($uuids, $class);
    }

    public function multiSync(array $inputs, bool $convertToJson = true): bool {
        return $this->lockService->blocks(
            array_keys($inputs),
            function () use ($inputs, $convertToJson) {
                return $this->storageService->multiSync($inputs, $convertToJson);
            }
        );
    }
}
