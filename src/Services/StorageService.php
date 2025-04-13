<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\BaseData;
use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Services\StorageServiceInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\RecordNotFoundException;
use InvalidArgumentException;
use JsonSerializable;
use Str;

class StorageService implements StorageServiceInterface
{
    private const PREFIX = 'wallet_sg::';

    public function __construct(
        private readonly CacheRepository $cacheRepository,
        private readonly ?int            $ttl
    )
    {
    }

    public function flush(): bool
    {
        return $this->cacheRepository->clear();
    }

    public function forget(string $uuid): bool
    {
        return $this->cacheRepository->forget(self::PREFIX . $uuid);
    }

    public function get(string $uuid, ?string $class = null): string
    {
        return current($this->multiGet([$uuid], $class));
    }

    public function sync(string $uuid, mixed $value): bool
    {
        return $this->multiSync([
            $uuid => $value,
        ]);
    }

    public function multiGet(array $uuids, ?string $class = null): array
    {
        $keys = [];
        foreach ($uuids as $uuid) {
            $keys[self::PREFIX . $uuid] = $uuid;
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

            if ($class != null) {
                $hasFromJson = method_exists($class, 'fromJson');
                $hasFromArray = method_exists($class, 'fromArray');
                if (!$hasFromJson && !$hasFromArray) {
                    throw new InvalidArgumentException("$class must have fromJson or fromArray method");
                }

                $isValueJson = Str::isJson($value);

                if (is_array($value) && $hasFromArray) {
                    $value = $class::fromArray($value);
                } elseif (is_array($value) && $hasFromJson) {
                    $value = $class::fromJson(json_encode($value));
                } elseif ($isValueJson && $hasFromJson) {
                    $value = $class::fromJson($value);
                } elseif ($isValueJson && $hasFromArray) {
                    $value = $class::fromArray(json_decode($value, true));
                }
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

    public function multiSync(array $inputs, bool $convertToJson = true): bool
    {
        $values = [];
        foreach ($inputs as $uuid => $value) {
            if ($convertToJson) {
                if ($value instanceof BaseData) {
                    $value = $value->toJson();
                } elseif ($value instanceof JsonSerializable) {
                    $value = json_encode($value);
                } elseif (is_array($value)) {
                    $value = json_encode($value);
                } elseif (!Str::isJson($value)) {
                    throw new InvalidArgumentException("Could not convert `value` to json");
                }
            }
            $values[self::PREFIX . $uuid] = $value;
        }

        if (count($values) === 1) {
            return $this->cacheRepository->set(key($values), current($values), $this->ttl);
        }

        return $this->cacheRepository->setMultiple($values, $this->ttl);
    }
}