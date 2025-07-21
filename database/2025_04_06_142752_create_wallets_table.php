<?php

use ArsamMe\Wallet\Models\Wallet;
use ArsamMe\Wallet\WalletConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        $config = app(WalletConfig::class);

        Schema::create($config->wallet_table, static function (Blueprint $table) use ($config) {
            $table->id();
            $table->uuid()->unique();
            $table->morphs('holder');
            $table->string('name');
            $table->string('slug')->index();
            $table->string('description')->nullable();
            $table->jsonb('meta')->nullable();
            $table->number('balance');
            $table->number('frozen_amount');
            if ($config->number_type == 'unscaled') {
                $table->unsignedSmallInteger('decimal_places');
            }
            $table->string('checksum')->nullable();
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->unique(['holder_type', 'holder_id', 'slug']);
        });
    }

    public function down(): void {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists($this->table());
    }

    private function table(): string {
        return (new Wallet)->getTable();
    }
};
