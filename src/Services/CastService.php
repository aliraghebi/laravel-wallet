<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Services\CastServiceInterface;
use ArsamMe\Wallet\Interfaces\Wallet;
use ArsamMe\Wallet\Internal\Assembler\WalletCreatedEventAssemblerInterface;
use ArsamMe\Wallet\Internal\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Internal\Service\DatabaseServiceInterface;
use ArsamMe\Wallet\Internal\Service\DispatcherServiceInterface;
use ArsamMe\Wallet\Models\Wallet as WalletModel;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal
 */
final readonly class CastService implements CastServiceInterface
{
    public function __construct(
        private WalletCreatedEventAssemblerInterface $walletCreatedEventAssembler,
        private DispatcherServiceInterface $dispatcherService,
        private DatabaseServiceInterface $databaseService
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function getWallet(Wallet $object, bool $save = true): WalletModel
    {
        $wallet = $this->getModel($object);
        if (! ($wallet instanceof WalletModel)) {
            $wallet = $wallet->getAttribute('wallet');
            assert($wallet instanceof WalletModel);
        }

        if ($save && ! $wallet->exists) {
            $this->databaseService->transaction(function () use ($wallet) {
                $result = $wallet->saveQuietly();
                $this->dispatcherService->dispatch($this->walletCreatedEventAssembler->create($wallet));

                return $result;
            });
        }

        return $wallet;
    }

    public function getHolder(Model|Wallet $object): Model
    {
        return $this->getModel($object instanceof WalletModel ? $object->holder : $object);
    }

    public function getModel(object $object): Model
    {
        assert($object instanceof Model);

        return $object;
    }
}
