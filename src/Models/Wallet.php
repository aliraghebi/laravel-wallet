<?php

namespace AliRaghebi\Wallet\Models;

use AliRaghebi\Wallet\Contracts\Models\Wallet as WalletContract;
use AliRaghebi\Wallet\Contracts\Services\CastServiceInterface;
use AliRaghebi\Wallet\Traits\WalletFunctions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

use function config;

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

    public function setNameAttribute(string $name): void {
        $this->attributes['name'] = $name;
        /**
         * Must be updated only if the model does not exist or the slug is empty.
         */
        if ($this->exists) {
            return;
        }
        if (array_key_exists('slug', $this->attributes)) {
            return;
        }
        $this->attributes['slug'] = Str::slug($name);
    }

    /**
     * Returns all transactions related to the wallet.
     *
     * This method retrieves all transactions associated with the wallet.
     * It uses the `getWallet` method of the `CastServiceInterface` to retrieve the wallet instance.
     * The `false` parameter indicates that the wallet should not be saved if it does not exist.
     * The method then uses the `hasMany` method on the wallet instance to retrieve all transactions related to the wallet.
     * The transaction model class is retrieved from the configuration using `config('wallet.transaction.model', Transaction::class)`.
     * The relationship is defined using the `wallet_id` foreign key.
     *
     * @return HasMany<Transaction> Returns a `HasMany` relationship of transactions related to the wallet.
     */
    public function transactions(): HasMany {
        // Retrieve the wallet instance using the `getWallet` method of the `CastServiceInterface`.
        // The `false` parameter indicates that the wallet should not be saved if it does not exist.
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        // Retrieve all transactions related to the wallet using the `hasMany` method on the wallet instance.
        // The transaction model class is retrieved from the configuration using `config('wallet.transaction.model', Transaction::class)`.
        // The relationship is defined using the `wallet_id` foreign key.
        return $wallet->hasMany(config('wallet.transaction.model', Transaction::class), 'wallet_id');
    }

    /**
     * Retrieves all transfers related to the wallet.
     *
     * This method retrieves all transfers associated with the wallet.
     * It uses the `getWallet` method of the `CastServiceInterface` to retrieve the wallet instance.
     * The `false` parameter indicates that the wallet should not be saved if it does not exist.
     * The method then uses the `hasMany` method on the wallet instance to retrieve all transfers related to the wallet.
     * The transfer model class is retrieved from the configuration using `config('wallet.transfer.model', Transfer::class)`.
     * The relationship is defined using the `from_id` foreign key.
     *
     * @return HasMany<Transfer> The `HasMany` relationship object representing all transfers related to the wallet.
     */
    public function transfers(): HasMany {
        // Retrieve all transfers associated with the wallet.
        // The `hasMany` method is used on the wallet instance to retrieve all transfers related to the wallet.
        // The transfer model class is retrieved from the configuration using `config('wallet.transfer.model', Transfer::class)`.
        // The relationship is defined using the `from_id` foreign key.
        return $this->hasMany(
            // Retrieve the transfer model class from the configuration.
            // The default value is `Transfer::class`.
            config('wallet.transfer.model', Transfer::class),
            // Define the foreign key for the relationship.
            // The foreign key is `from_id`.
            'from_id'
        );
    }

    /**
     * Retrieves all the receiving transfers to this wallet.
     *
     * This method retrieves all receiving transfers associated with the wallet.
     * It uses the `getWallet` method of the `CastServiceInterface` to retrieve the wallet instance.
     * The `false` parameter indicates that the wallet should not be saved if it does not exist.
     * The method then uses the `hasMany` method on the wallet instance to retrieve all receiving transfers related to the wallet.
     * The transfer model class is retrieved from the configuration using `config('wallet.transfer.model', Transfer::class)`.
     * The relationship is defined using the `to_id` foreign key.
     *
     * @return HasMany<Transfer> The `HasMany` relationship object representing all receiving transfers related to the wallet.
     */
    public function receivedTransfers(): HasMany {
        // Retrieve all receiving transfers associated with the wallet.
        // The `hasMany` method is used on the wallet instance to retrieve all receiving transfers related to the wallet.
        // The transfer model class is retrieved from the configuration using `config('wallet.transfer.model', Transfer::class)`.
        // The relationship is defined using the `to_id` foreign key.
        return $this->hasMany(
            // Retrieve the transfer model class from the configuration.
            // The default value is `Transfer::class`.
            config('wallet.transfer.model', Transfer::class),
            // Define the foreign key for the relationship.
            // The foreign key is `to_id`.
            'to_id'
        );
    }
}
