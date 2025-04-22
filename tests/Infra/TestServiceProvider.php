<?php

namespace ArsamMe\Wallet\Test\Infra;

use Illuminate\Support\ServiceProvider;

final class TestServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->loadMigrationsFrom([dirname(__DIR__).'/migrations']);
    }
}
