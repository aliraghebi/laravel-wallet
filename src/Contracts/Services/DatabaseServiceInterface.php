<?php

namespace ArsamMe\Wallet\Contracts\Services;

interface DatabaseServiceInterface
{
    function transaction(callable $callback): mixed;
}