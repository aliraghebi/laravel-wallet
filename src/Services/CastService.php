<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Models\Wallet;
use ArsamMe\Wallet\Contracts\Services\CastServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DatabaseServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DispatcherServiceInterface;
use ArsamMe\Wallet\Events\WalletCreatedEvent;
use ArsamMe\Wallet\Models\Wallet as WalletModel;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal
 */
final readonly class CastService implements CastServiceInterface {
    public function __construct(private DatabaseServiceInterface $databaseService, private DispatcherServiceInterface $dispatcherService) {}

    public function getWallet(object $object, bool $save = true): WalletModel {
        $wallet = $this->getModel($object);
        if (!($wallet instanceof WalletModel)) {
            $wallet = $wallet->getAttribute('wallet');
            assert($wallet instanceof WalletModel);
        }

        if ($save && !$wallet->exists) {
            $this->databaseService->transaction(function () use ($wallet) {
                $result = $wallet->saveQuietly();
                $this->dispatcherService->dispatch(WalletCreatedEvent::fromWallet($wallet));

                return $result;
            });
        }

        return $wallet;
    }

    public function getHolder(Model|Wallet $object): Model {
        return $this->getModel($object instanceof WalletModel ? $object->holder : $object);
    }

    public function getModel(object $object): Model {
        assert($object instanceof Model);

        return $object;
    }
}
