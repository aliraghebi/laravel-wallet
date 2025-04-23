<?php

namespace ArsamMe\Wallet\Models;

use ArsamMe\Wallet\Contracts\Models\Wallet as WalletContract;
use ArsamMe\Wallet\Contracts\Services\IdentifierFactoryServiceInterface;
use ArsamMe\Wallet\Traits\WalletFunctions;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

use function config;

/**
 * Class Wallet.
 *
 * @property non-empty-string $uuid
 * @property class-string $holder_type
 * @property int|non-empty-string $holder_id
 * @property class-string $currency_type
 * @property int|non-empty-string $currency_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property null|array $meta
 * @property int $decimal_places
 * @property Model $holder
 * @property string $currency
 * @property DateTimeInterface $created_at
 * @property DateTimeInterface $updated_at
 * @property DateTimeInterface $deleted_at
 *
 * @method int getKey()
 */
class Wallet extends Model implements WalletContract {
    use SoftDeletes, WalletFunctions;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'holder_type',
        'holder_id',
        'name',
        'slug',
        'description',
        'meta',
        'balance',
        'frozen_amount',
        'decimal_places',
        'checksum',
        'created_at',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array {
        return [
            'decimal_places' => 'int',
            'meta' => 'json',
        ];
    }

    public function getTable(): string {
        if ((string) $this->table === '') {
            $this->table = config('wallet.wallet.table', 'wallets');
        }

        return parent::getTable();
    }

    /**
     * @return MorphTo<Model, self>
     */
    public function holder(): MorphTo {
        return $this->morphTo();
    }
}
