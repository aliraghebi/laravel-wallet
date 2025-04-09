<?php

namespace ArsamMe\Wallet\Facades;

use ArsamMe\Wallet\Contracts\Services\WalletServiceInterface;
use Illuminate\Support\Facades\Facade as FacadesFacade;

class LaravelWallet extends FacadesFacade
{
    protected static function getFacadeAccessor(): string
    {
        return WalletServiceInterface::class;
    }
}
