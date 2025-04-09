<?php

namespace ArsamMe\Wallet\Contracts\Services;

use Illuminate\Database\ConnectionInterface;

interface DatabaseServiceInterface
{
    function getConnection(): ConnectionInterface;

    function transaction(callable $callback): mixed;
}