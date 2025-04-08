<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Models;

use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use function array_key_exists;
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
class Wallet extends Model
{
    use SoftDeletes;

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
     * @var array<string, int|string>
     */
    protected $attributes = [
        'balance' => 0,
        'frozen_amount' => 0,
        'decimal_places' => 2,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'decimal_places' => 'int',
            'meta' => 'json',
        ];
    }

    public function getTable(): string
    {
        if ((string)$this->table === '') {
            $this->table = config('wallet.wallet.table', 'wallets');
        }

        return parent::getTable();
    }

    public function setNameAttribute(string $name): void
    {
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
     * @return MorphTo<Model, self>
     */
    public function holder(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, self>
     */
    public function currency(): MorphTo
    {
        return $this->morphTo();
    }

    public function getRawBalanceAttribute()
    {
        return $this->getRawOriginal('balance', 0);
    }

    public function getRawFrozenAmountAttribute()
    {
        return $this->getRawOriginal('frozen_amount', 0);
    }

    public function getBalanceAttribute()
    {
        $mathService = app(MathServiceInterface::class);
        return $mathService->floatValue($this->attributes['balance'], $this->attributes['decimal_places']);
    }

    public function getFrozenAmountAttribute()
    {
        $mathService = app(MathServiceInterface::class);
        return $mathService->floatValue($this->attributes['frozen_amount'], $this->attributes['decimal_places']);
    }

    public function getAvailableBalanceAttribute()
    {
        $mathService = app(MathServiceInterface::class);

        $balance = $mathService->floatValue($this->attributes['balance'], $this->attributes['decimal_places']);
        $frozenAmount = $mathService->floatValue($this->attributes['frozen_amount'], $this->attributes['decimal_places']);
        return $mathService->sub($balance, $frozenAmount, $this->attributes['decimal_places']);
    }
}
