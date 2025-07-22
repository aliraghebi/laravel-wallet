<?php

use AliRaghebi\Wallet\WalletConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        $config = app(WalletConfig::class);

        Schema::create($config->transaction_table, static function (Blueprint $table) use ($config) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('wallet_id')->constrained($config->wallet_table)->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('type', ['deposit', 'withdraw'])->index();
            $table->number('amount');
            $table->number('balance');
            $table->unsignedSmallInteger('decimal_places')->nullable();
            $table->string('purpose', 48)->nullable()->index();
            $table->string('description')->nullable();
            $table->jsonb('meta')->nullable();
            $table->string('checksum')->nullable();
            $table->softDeletesTz();
            $table->timestampsTz();
        });
    }

    public function down(): void {
        $config = app(WalletConfig::class);

        Schema::dropIfExists($config->transaction_table);
    }
};
