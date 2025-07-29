<?php

namespace AliRaghebi\Wallet\Services;

use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Contracts\Services\CastServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\DatabaseServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\DispatcherServiceInterface;
use AliRaghebi\Wallet\Events\WalletCreatedEvent;
use AliRaghebi\Wallet\Models\Wallet as WalletModel;
use AliRaghebi\Wallet\WalletConfig;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal
 */
final readonly class CastService implements CastServiceInterface {
    public function __construct(private DatabaseServiceInterface $databaseService, private DispatcherServiceInterface $dispatcherService, private WalletConfig $config) {}

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
        } elseif ($save && $wallet->deleted_at != null) {
            $wallet->restore();
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
