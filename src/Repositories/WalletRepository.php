<?php

namespace ArsamMe\Wallet\Repositories;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use ArsamMe\Wallet\Exceptions\ModelNotFoundException;
use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\ModelNotFoundException as EloquentModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

readonly class WalletRepository implements WalletRepositoryInterface {
    public function __construct(private Wallet $wallet) {}

    public function createWallet(array $attributes): Wallet {
        $instance = $this->wallet->newInstance($attributes);
        $instance->saveQuietly();

        return $instance;
    }

    public function findById(int $id): ?Wallet {
        try {
            return $this->findOrFailById($id);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    public function findByUuid(string $uuid): ?Wallet {
        try {
            return $this->findOrFailByUuid($uuid);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    public function findBySlug(string $holderType, int|string $holderId, string $slug): ?Wallet {
        try {
            return $this->findOrFailBySlug($holderType, $holderId, $slug);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findOrFailById(int $id): Wallet {
        return $this->findOrFailBy([
            'id' => $id,
        ]);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findOrFailByUuid(string $uuid): Wallet {
        return $this->findOrFailBy([
            'uuid' => $uuid,
        ]);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findOrFailBySlug(string $holderType, int|string $holderId, string $slug): Wallet {
        return $this->findOrFailBy([
            'holder_type' => $holderType,
            'holder_id' => $holderId,
            'slug' => $slug,
        ]);
    }

    /**
     * @param  array<string, int|string>  $attributes
     */
    private function findOrFailBy(array $attributes): Wallet {
        assert($attributes !== []);

        try {
            $wallet = $this->wallet->newQuery()
                ->where($attributes)
                ->firstOrFail();
            assert($wallet instanceof Wallet);

            return $wallet;
        } catch (EloquentModelNotFoundException $exception) {
            throw new ModelNotFoundException(
                $exception->getMessage(),
                ExceptionInterface::MODEL_NOT_FOUND,
                $exception
            );
        }
    }

    public function update(Wallet $wallet, array $attributes): Wallet {
        $attributes['updated_at'] ??= now();

        $wallet->fill($attributes)->saveQuietly();

        return $wallet;
    }

    public function multiUpdate(array $data): bool {
        // One element gives x10 speedup, on some data
        if (count($data) === 1) {
            return $this->wallet->newQuery()
                ->whereKey(key($data))
                ->update(current($data));
        }

        // Multiple wallet updates using CASE WHEN
        $ids = array_keys($data);

        // Get all unique fields across wallets
        $allFields = collect($data)
            ->flatMap(fn ($fields) => array_keys($fields))
            ->unique()
            ->values()
            ->all();

        // Build CASE expressions for each field
        $cases = [];

        foreach ($allFields as $field) {
            $case = "CASE id\n";
            foreach ($data as $id => $fields) {
                if (array_key_exists($field, $fields)) {
                    $value = DB::getPdo()->quote($fields[$field]);
                    $case .= "WHEN {$id} THEN {$value}\n";
                }
            }
            $case .= 'END';
            $cases[$field] = $case;
        }

        // Build SET clause
        $updateParams = collect($cases)->mapWithKeys(fn ($case, $field) => [$field => $this->wallet->getConnection()->raw($case)])->toArray();

        return $this->wallet->newQuery()
            ->whereIn('id', $ids)
            ->update($updateParams);
    }

    public function multiGet(array $keys, string $column = 'id'): Collection {
        return $this->wallet->newQuery()
            ->withCount('walletTransactions as transactions_count')
            ->withSum('walletTransactions as transactions_sum', 'amount')
            ->whereIn($column, $keys)
            ->get();
    }
}
