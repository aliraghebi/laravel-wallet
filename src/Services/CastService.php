<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Services\CastServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DatabaseServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DispatcherServiceInterface;
use ArsamMe\Wallet\Contracts\Wallet;
use ArsamMe\Wallet\Events\WalletCreatedEvent;
use ArsamMe\Wallet\Models\Wallet as WalletModel;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal
 */
final readonly class CastService implements CastServiceInterface {
    public function __construct(
        private DatabaseServiceInterface $databaseService,
        private DispatcherServiceInterface $dispatcherService
    ) {}

    public function getWallet(object $object, bool $save = true): WalletModel {
        $wallet = $this->getModel($object);
        if (!($wallet instanceof WalletModel)) {
            $wallet = $wallet->getAttribute('wallet');
            assert($wallet instanceof WalletModel);
        }

        if ($save && !$wallet->exists) {
            $this->databaseService->transaction(function () use ($wallet) {
                $result = $wallet->saveQuietly();

                $this->dispatcherService->dispatch(new WalletCreatedEvent(
                    $wallet->id,
                    $wallet->uuid,
                    $wallet->holder_type,
                    $wallet->holder_id,
                    $wallet->description,
                    $wallet->meta,
                    $wallet->decimal_places,
                    $wallet->created_at->toImmutable(),
                ));

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
