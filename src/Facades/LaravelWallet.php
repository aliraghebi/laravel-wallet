<?php

namespace AliRaghebi\Wallet\Facades;

use AliRaghebi\Wallet\Contracts\Services\WalletServiceInterface;
use Illuminate\Support\Facades\Facade as FacadesFacade;

class LaravelWallet extends FacadesFacade {
    protected static function getFacadeAccessor(): string {
        return WalletServiceInterface::class;
    }
}
