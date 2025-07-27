<?php

use AliRaghebi\Wallet\Models\Transaction;
use AliRaghebi\Wallet\Models\Transfer;
use AliRaghebi\Wallet\Models\Wallet;
use AliRaghebi\Wallet\WalletConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        $config = app(WalletConfig::class);

        Schema::create($config->transfer_table, function (Blueprint $table) use ($config) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('from_id')->constrained($config->wallet_table)->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('to_id')->constrained($config->wallet_table)->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('deposit_id')->constrained($config->transaction_table)->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('withdrawal_id')->constrained($config->transaction_table)->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('amount', $config->number_digits, $config->number_decimal_places);
            $table->decimal('fee', $config->number_digits, $config->number_decimal_places)->default(0);
            $table->string('checksum')->nullable();
            $table->string('purpose', 48)->nullable()->index();
            $table->string('description')->nullable();
            $table->jsonb('meta')->nullable();
            $table->softDeletesTz();
            $table->timestampsTz();
        });
    }

    public function down(): void {
        Schema::dropIfExists($this->table());
    }

    private function table(): string {
        return (new Transfer)->getTable();
    }

    private function walletsTable(): string {
        return (new Wallet)->getTable();
    }

    private function transactionTable(): string {
        return (new Transaction)->getTable();
    }
};
