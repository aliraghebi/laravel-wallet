<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Models;

use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Models\Wallet as WalletModel;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

use function config;

/**
 * Class Transaction.
 *
 * @property non-empty-string $uuid
 * @property int $wallet_id
 * @property string $type
 * @property non-empty-string $amount
 * @property null|array $meta
 * @property string $checksum
 * @property Wallet $wallet
 * @property DateTimeInterface $created_at
 * @property DateTimeInterface $updated_at
 *
 * @method int getKey()
 */
class Transaction extends Model {
    use SoftDeletes;

    final public const TYPE_DEPOSIT = 'deposit';

    final public const TYPE_WITHDRAW = 'withdraw';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'wallet_id',
        'type',
        'amount',
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
            'meta' => 'json',
        ];
    }

    public function getTable(): string {
        if ((string) $this->table === '') {
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

    public function getRawAmountAttribute(): string {
        return (string) $this->getRawOriginal('amount', 0);
    }

    public function getAmountAttribute(): string {
        $math = app(MathServiceInterface::class);

        return $math->floatValue($this->attributes['amount'], $this->decimal_places);
    }
}
