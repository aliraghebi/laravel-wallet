<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Contracts\Services;

use Illuminate\Database\RecordNotFoundException;

interface StorageServiceInterface {
    /**
     * Flushes all the stored values.
     *
     * This method clears all the stored values, effectively removing them from the storage.
     * The method returns a boolean value indicating whether the flush operation was successful
     * or not.
     *
     * @return bool True if the flush operation was successful, false otherwise.
     */
    public function flush(): bool;

    /**
     * Forgets the stored value for the given UUID.
     *
     * This method removes the stored value associated with the provided UUID from the storage.
     *
     * @param  non-empty-string  $uuid  The UUID of the stored value to forget.
     * @return bool True if the value was successfully forgotten, false otherwise.
     */
    public function forget(string $uuid): bool;

    /**
     * Retrieves the stored value for the given UUID.
     *
     * This method retrieves the stored value associated with the provided UUID from the storage.
     * If the value with the given UUID does not exist, a `RecordNotFoundException` is thrown.
     *
     * @param  non-empty-string  $uuid  The UUID of the stored value.
     * @return mixed The stored value.
     *
     * @throws RecordNotFoundException If the value with the given UUID is not found.
     */
    public function get(string $uuid): mixed;

    /**
     * Synchronizes the stored value for the given UUID.
     *
     * This method updates the stored value associated with the provided UUID with the specified value.
     * If the value does not exist, it will be created. If the value already exists, it will be updated.
     *
     * @param  non-empty-string  $uuid  The UUID of the stored value.
     * @param  mixed  $value  The value to synchronize.
     * @return bool Returns `true` if the synchronization was successful, `false` otherwise.
     */
    public function sync(string $uuid, mixed $value): bool;

    /**
     * Retrieves the stored values for the given UUIDs.
     *
     * This method retrieves the stored values associated with the provided UUIDs from the storage.
     * If any of the values with the given UUIDs do not exist, a `RecordNotFoundException` is thrown.
     *
     * @param  non-empty-array<non-empty-string>  $uuids  The UUIDs of the stored values.
     * @return non-empty-array<non-empty-string, non-empty-string> The stored values. The keys are the UUIDs and the values are the corresponding
     *                                                             stored values.
     *
     * @throws RecordNotFoundException If any of the values with the given UUIDs are not found.
     */
    public function multiGet(array $uuids): array;

    /**
     * Synchronizes multiple stored values at once.
     *
     * This method updates the stored values associated with the provided UUIDs with the specified values.
     * If any of the values with the given UUIDs do not exist, a `RecordNotFoundException` is thrown.
     *
     * @param  non-empty-array<non-empty-string, float|int|non-empty-string>  $inputs  An associative array
     *                                                                                 where the keys are UUIDs and the values are the corresponding
     *                                                                                 stored values.
     * @return bool Returns `true` if the synchronization was successful, `false` otherwise.
     *
     * @throws RecordNotFoundException If any of the values with the given UUIDs are not found.
     */
    public function multiSync(array $inputs): bool;
}
