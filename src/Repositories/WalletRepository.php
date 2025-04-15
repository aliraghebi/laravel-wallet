<?php

namespace ArsamMe\Wallet\Repositories;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use ArsamMe\Wallet\Data\WalletStateData;
use ArsamMe\Wallet\Exceptions\ModelNotFoundException;
use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\ModelNotFoundException as EloquentModelNotFoundException;

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
        assert([] !== $attributes);

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
        $wallet->fill($attributes)->saveQuietly();

        return $wallet;
    }

    public function multiUpdate(string $column, array $data): bool {
        // One element gives x10 speedup, on some data
        if (1 === count($data)) {
            return $this->wallet->newQuery()
                ->whereKey(key($data))
                ->update([
                    $column => current($data),
                ]);
        }

        $cases = [];
        foreach ($data as $walletId => $balance) {
            $cases[] = 'WHEN id = '.$walletId.' THEN '.$balance;
        }

        $buildQuery = $this->wallet->getConnection()
            ->raw('CASE '.implode(' ', $cases).' END');

        return $this->wallet->newQuery()
            ->whereIn('id', array_keys($data))
            ->update([
                $column => $buildQuery,
            ]);
    }

    public function getWalletStateData(Wallet $wallet): WalletStateData {
        $fWallet = $this->wallet->newQuery()
            ->withCount('transactions')
            ->withSum('transactions', 'amount')
            ->findOrFail($wallet->id);

        return new WalletStateData(
            $fWallet->uuid,
            (string) $fWallet->getRawOriginal('balance'),
            (string) $fWallet->getRawOriginal('frozen_amount'),
            $fWallet->transactions_count,
            (string) $fWallet->transactions_sum_amount,
            (string) $fWallet->checksum
        );
    }
}
