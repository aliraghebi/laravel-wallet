<?php

namespace AliRaghebi\Wallet\Models;

use AliRaghebi\Wallet\Models\Wallet as WalletModel;
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
 * @property non-empty-string $balance
 * @property null|string $purpose
 * @property null|string $description
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
        'balance',
        'purpose',
        'description',
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
            'amount' => 'string',
            'balance' => 'string',
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
}
