<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Models;

use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Models\Wallet as WalletModel;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use function config;

/**
 * Class Transaction.
 *
 * @property non-empty-string $uuid
 * @property int $wallet_id
 * @property non-empty-string $credit
 * @property non-empty-string $debit
 * @property non-empty-string $balance
 * @property null|array $meta
 * @property string $checksum
 * @property Wallet $wallet
 * @property DateTimeInterface $created_at
 * @property DateTimeInterface $updated_at
 *
 * @method int getKey()
 */
class Transaction extends Model {
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'wallet_id',
        'credit',
        'debit',
        'balance',
        'meta',
        'checksum',
        'created_at',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array {
        return [
            'wallet_id' => 'int',
            'meta' => 'json',
        ];
    }

    public function getTable(): string {
        if ('' === (string) $this->table) {
            $this->table = config('wallet.transaction.table', 'transactions');
        }

        return parent::getTable();
    }

    /**
     * @return BelongsTo<WalletModel, self>
     */
    public function wallet(): BelongsTo {
        return $this->belongsTo(config('wallet.wallet.model', WalletModel::class));
    }

    public function getDecimalPlacesAttribute(): int {
        return $this->wallet->decimal_places;
    }

    public function getRawCreditAttribute(): string {
        return (string) $this->getRawOriginal('credit', 0);
    }

    public function getRawDebitAttribute(): string {
        return (string) $this->getRawOriginal('debit', 0);
    }

    public function getRawBalanceAttribute(): string {
        return (string) $this->getRawOriginal('balance', 0);
    }

    public function getCreditAttribute(): string {
        $math = app(MathServiceInterface::class);

        return $math->floatValue($this->attributes['credit'], $this->decimal_places);
    }

    public function getDebitAttribute(): string {
        $math = app(MathServiceInterface::class);

        return $math->floatValue($this->attributes['debit'], $this->decimal_places);
    }

    public function getBalanceAttribute(): string {
        $math = app(MathServiceInterface::class);

        return $math->floatValue($this->attributes['balance'], $this->decimal_places);
    }
}
