<?php

namespace ArsamMe\Wallet;

use Illuminate\Support\Arr;

/**
 * @property string $number_type
 * @property int $number_digits
 * @property int $number_decimal_places
 * @property bool $consistency_enabled
 * @property string $consistency_secret
 * @property string $wallet_table
 * @property string $wallet_model
 * @property string $wallet_default_name
 * @property string $wallet_default_slug
 * @property array|null $wallet_default_meta
 * @property string $transaction_table
 * @property string $transaction_model
 * @property string $transfer_table
 * @property string $transfer_model
 * @property string $cache_driver
 * @property int $cache_ttl
 * @property string $lock_driver
 * @property int $lock_seconds
 */
class WalletConfig {
    protected array $map = [
        'number_decimal_places' => 'number.decimal_places',
    ];

    protected array $config;

    public function __construct() {
        // Load full config once (or just the relevant ones like wallet, features, etc.)
        $this->config = config('wallet');
    }

    public function __get(string $name) {
        // Prefer mapped values
        if (isset($this->map[$name])) {
            return Arr::get($this->config, $this->map[$name]);
        }

        // Try auto-resolve: snake_case → dot.notation
        $dotKey = str_replace('_', '.', $name);

        return Arr::get($this->config, $dotKey);
    }

    public function get(string $key, $default = null) {
        return Arr::get($this->config, $key, $default);
    }
}
