<?php

namespace AliRaghebi\Wallet\Test;

use Illuminate\Support\ServiceProvider;

final class TestServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->loadMigrationsFrom([__DIR__.'/migrations']);
    }
}
