<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Services\AtomicServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Contracts\Services\WalletServiceInterface;
use ArsamMe\Wallet\Exceptions\WalletIntegrityInvalidException;
use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Str;

class WalletService implements WalletServiceInterface {
    private string $walletSecret;

    public function __construct(private readonly AtomicServiceInterface $atomicService, private readonly MathServiceInterface $mathService, string $walletSecret) {
        $this->walletSecret = $walletSecret;
    }

    public function createWallet(Model $holder, string $name, ?string $slug = null, ?int $decimalPlaces = null, ?array $meta = null, array $params = []): Wallet {
        $defaultParams = config('wallet.creating', []);

        $uuid = Str::uuid7();
        $time = Carbon::parse('2024-01-01');

        $data = array_filter([
            'uuid' => $uuid,
            'holder_type' => $holder->getMorphClass(),
            'holder_id' => $holder->getKey(),
            'name' => $name,
            'slug' => $slug,
            'description' => $params['description'] ?? null,
            'decimal_places' => $decimalPlaces,
            'meta' => $meta,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $zero = $this->mathService->intValue('0', 24);
        $data['checksum'] = $this->createWalletChecksum($uuid, $zero, $zero, $zero, $zero, $zero, $time);
        $data['meta']['time'] = $time->timestamp;
        $data = array_merge($defaultParams, $data);

        Wallet::make($data)->save();

        return Wallet::where('uuid', $uuid)->firstOrFail();

    }

    public function findWalletBySlug(Model $holder, string $slug): ?Wallet {
        return Wallet::whereMorphedTo('holder', $holder)->where('slug', $slug)->first();
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findOrFailWalletBySlug(Model $holder, string $slug): Wallet {
        $wallet = $this->findWalletBySlug($holder, $slug);
        if (null == $wallet) {
            throw new ModelNotFoundException("Wallet not found with slug `$slug` for holder `{$holder->getMorphClass()}` with id `{$holder->getKey()}`");
        }

        return $wallet;
    }

    public function getBalance(Wallet $wallet): string {
        $wallet->refresh();

        return $wallet->getBalanceAttribute();
    }

    public function deposit(Wallet $wallet, float|int|string $amount, ?array $meta = null): void {
        $this->atomic($wallet, function () use ($meta, $amount, $wallet) {
            $wallet->refresh();
            $this->validateWalletIntegrity($wallet, true);

            $uuid = Str::uuid7();
            $time = now();

            $amount = $this->mathService->intValue($amount, $wallet->decimal_places);
            $balance = $this->mathService->add($wallet->raw_balance, $amount);

            $checksum = $this->createTransactionChecksum($wallet->uuid, $uuid, $amount, $balance, $time);

            Transaction::create([
                'uuid' => $uuid,
                'wallet_id' => $wallet->id,
                'credit' => $amount,
                'debit' => 0,
                'balance' => $balance,
                'meta' => $meta,
                'checksum' => $checksum,
                'created_at' => $time,
                'updated_at' => $time,
            ]);

            $frozenAmount = $wallet->raw_frozen_amount;

            $transactionsCount = $wallet->transactions()->count();
            $totalCredit = $wallet->transactions()->sum('credit');
            $totalDebit = $wallet->transactions()->sum('debit');

            $walletChecksum = $this->createWalletChecksum(
                $wallet->uuid,
                $transactionsCount,
                $totalCredit,
                $totalDebit,
                $balance,
                $frozenAmount,
                $time
            );
            $wallet->update([
                'balance' => $balance,
                'checksum' => $walletChecksum,
                'updated_at' => $time,
            ]);
        });
    }

    public function withdraw(Wallet $wallet, float|int|string $amount, ?array $meta = null): void {
        // TODO: Implement withdraw() method.
    }

    public function freeze(Wallet $wallet, float|int|string $amount, ?array $meta = null): void {
        // TODO: Implement freeze() method.
    }

    public function unFreeze(Wallet $wallet, float|int|string|null $amount = null, ?array $meta = null): void {
        // TODO: Implement unFreeze() method.
    }

    public function atomic(array|Wallet $wallets, $callback): mixed {
        if ($wallets instanceof Wallet) {
            $wallets = [$wallets];
        }

        return $this->atomicService->blocks($wallets, $callback);
    }

    public function validateWalletIntegrity(Wallet $wallet, bool $throw = false): bool {
        return $this->atomic($wallet, function () use ($throw, $wallet) {
            $wallet->refresh();

            $walletBalance = $this->mathService->round($wallet->raw_balance, 0);
            $frozenAmount = $wallet->raw_frozen_amount;

            $transactionsCount = $wallet->transactions()->count();
            $totalCredit = $this->mathService->intValue((string) $wallet->transactions()->sum('credit'), $wallet->decimal_places);
            $totalDebit = $this->mathService->intValue((string) $wallet->transactions()->sum('debit'), $wallet->decimal_places);
            $expectedBalance = $this->mathService->intValue($this->mathService->sub($totalCredit, $totalDebit, 64), $wallet->decimal_places);

            if (0 != $this->mathService->compare($walletBalance, $expectedBalance)) {
                throw_if($throw, new WalletIntegrityInvalidException);

                return false;
            }

            $expectedChecksum = $this->createWalletChecksum(
                $wallet->uuid,
                $transactionsCount,
                $totalCredit,
                $totalDebit,
                $expectedBalance,
                $frozenAmount,
                $wallet->updated_at
            );

            dd($wallet->decimal_places, $wallet->uuid, $transactionsCount, $totalCredit, $totalDebit, $expectedBalance, $frozenAmount, $wallet->updated_at, $expectedChecksum, $wallet->checksum);

            if (!hash_equals($expectedChecksum, $wallet->checksum)) {
                throw_if($throw, new WalletIntegrityInvalidException);

                return false;
            }

            return true;
        });
    }

    private function createWalletChecksum(string $uuid, int $transactionsCount, string $totalCredit, string $totalDebit, string $balance, string $frozenAmount, Carbon $updatedAt): string {
        return bin2hex(hash_hmac('sha256', "{$uuid}_{$transactionsCount}_{$totalCredit}_{$totalDebit}_{$balance}_{$frozenAmount}_$updatedAt->timestamp", $this->walletSecret, true));
    }

    private function createTransactionChecksum(string $walletUuid, string $uuid, string $amount, string $balance, Carbon $time): string {
        return bin2hex(hash_hmac('sha256', "{$walletUuid}_{$uuid}_{$amount}_{$balance}_$time->timestamp", $this->walletSecret, true));
    }
}
