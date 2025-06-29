<?php

namespace ArsamMe\Wallet\Repositories;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Data\WalletData;
use ArsamMe\Wallet\Data\WalletSumData;
use ArsamMe\Wallet\Exceptions\ModelNotFoundException;
use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException as EloquentModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

readonly class WalletRepository implements WalletRepositoryInterface {
    public function __construct(private Wallet $wallet, private readonly MathServiceInterface $mathService) {}

    public function createWallet(WalletData $data): Wallet {
        $instance = $this->wallet->newInstance($data->toArray());
        $instance->saveQuietly();

        return $instance;
    }

    public function findBy(array $attributes): ?Wallet {
        try {
            return $this->findOrFailBy($attributes);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    /**
     * @param  array<string, int|string>  $attributes
     */
    public function findOrFailBy(array $attributes): Wallet {
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
        $wallet->update($attributes);

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
            $case .= "ELSE $field\n";
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

    public function sumWallets(array $ids = [], array $uuids = [], array $slugs = []): WalletSumData {
        $results = $this->wallet->newQuery()
            ->select('decimal_places', DB::raw('SUM(balance) as balance'), DB::raw('SUM(frozen_amount) as frozen_amount'))
            ->when(!empty($ids), function (Builder $query) use ($ids) {
                $query->whereIn('id', $ids);
            })
            ->when(!empty($uuids), function (Builder $query) use ($uuids) {
                $query->whereIn('uuid', $uuids);
            })
            ->when(!empty($slugs), function (Builder $query) use ($slugs) {
                $query->whereIn('slug', $slugs);
            })
            ->groupBy('decimal_places')
            ->get();

        $balance = '0';
        $frozenAmount = '0';
        foreach ($results as $row) {
            $balance = $this->mathService->add($balance, $this->mathService->floatValue($row->balance, $row->decimal_places));
            $frozenAmount = $this->mathService->add($frozenAmount, $this->mathService->floatValue($row->frozen_amount, $row->decimal_places));
        }
        $availableBalance = $this->mathService->sub($balance, $frozenAmount);

        return new WalletSumData($balance, $frozenAmount, $availableBalance);
    }
}
