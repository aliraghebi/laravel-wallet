<?php

namespace AliRaghebi\Wallet\Contracts\Services;

use Illuminate\Database\ConnectionInterface;

interface DatabaseServiceInterface {
    public function getConnection(): ConnectionInterface;

    public function transaction(callable $callback): mixed;
}
