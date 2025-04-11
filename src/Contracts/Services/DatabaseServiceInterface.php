<?php

namespace ArsamMe\Wallet\Contracts\Services;

use Illuminate\Database\ConnectionInterface;

interface DatabaseServiceInterface {
    public function getConnection(): ConnectionInterface;

    public function transaction(callable $callback): mixed;
}
