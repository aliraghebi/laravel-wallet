<?php

namespace AliRaghebi\Wallet\Models;

use AliRaghebi\Wallet\Contracts\Services\ConsistencyServiceInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

use function config;

/**
 * Class Transfer.
 *
 * @property non-empty-string $uuid
 * @property int $from_id
 * @property int $to_id
 * @property Wallet $from
 * @property Wallet $to
 * @property int $deposit_id
 * @property int $withdrawal_id
 * @property non-empty-string $amount
 * @property non-empty-string $fee
 * @property ?array $meta
 * @property Transaction $deposit
 * @property Transaction $withdrawal
 * @property DateTimeInterface $created_at
 * @property DateTimeInterface $updated_at
 * @property DateTimeInterface $deleted_at
 *
 * @method int getKey()
 */
class Transfer extends Model {
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'deposit_id',
        'withdrawal_id',
        'from_id',
        'to_id',
        'amount',
        'fee',
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
            'fee' => 'string',
            'meta' => 'json',
        ];
    }

    public function getTable(): string {
        if ((string) $this->table === '') {
            $this->table = config('wallet.transfer.table', 'transfers');
        }

        return parent::getTable();
    }

    public function getIsIntegrityValidAttribute(): bool {
        $consistencyService = app(ConsistencyServiceInterface::class);

        return $consistencyService->validateTransferChecksum($this);
    }

    /**
     * @return BelongsTo<Wallet, self>
     */
    public function from(): BelongsTo {
        return $this->belongsTo(config('wallet.wallet.model', Wallet::class), 'from_id');
    }

    /**
     * @return BelongsTo<Wallet, self>
     */
    public function to(): BelongsTo {
        return $this->belongsTo(config('wallet.wallet.model', Wallet::class), 'to_id');
    }

    /**
     * @return BelongsTo<Transaction, self>
     */
    public function deposit(): BelongsTo {
        return $this->belongsTo(config('wallet.transaction.model', Transaction::class), 'deposit_id');
    }

    /**
     * @return BelongsTo<Transaction, self>
     */
    public function withdrawal(): BelongsTo {
        return $this->belongsTo(config('wallet.transaction.model', Transaction::class), 'withdrawal_id');
    }
}
