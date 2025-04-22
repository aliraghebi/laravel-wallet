<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Services\StateServiceInterface;
use ArsamMe\Wallet\Data\WalletStateData;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\SimpleCache\InvalidArgumentException;

final readonly class StateService implements StateServiceInterface {
    /**
     * Keeps the state of wallet
     */
    private const PREFIX_STATE = 'wallet_s::';

    private CacheRepository $store;

    public function __construct(CacheFactory $cacheFactory) {
        $this->store = $cacheFactory->store('array');
    }

    /**
     * @param  string[]  $uuids
     * @param  callable(): array<string, string>  $value
     *
     * @throws InvalidArgumentException
     */
    public function multiFork(array $uuids, callable $value): void {

        $insertValues = [];
        $results = $value();
        foreach ($results as $rUuid => $rValue) {
            assert($rValue instanceof WalletStateData);
            $insertValues[self::PREFIX_STATE.$rUuid] = $rValue;
        }
        // set new values
        $this->store->setMultiple($insertValues);
    }

    public function get(string $uuid): ?WalletStateData {
        return $this->store->get(self::PREFIX_STATE.$uuid);
    }

    public function drop(string $uuid): void {
        $this->store->delete(self::PREFIX_STATE.$uuid);
    }
}
