<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Models\Wallet;
use ArsamMe\Wallet\Contracts\Services\CastServiceInterface;
use ArsamMe\Wallet\Models\Wallet as WalletModel;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal
 */
final readonly class CastService implements CastServiceInterface {
    public function __construct() {}

    public function getWallet(object $object): WalletModel {
        $wallet = $this->getModel($object);
        if (!($wallet instanceof WalletModel)) {
            $wallet = $wallet->getAttribute('wallet');
            assert($wallet instanceof WalletModel);
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
