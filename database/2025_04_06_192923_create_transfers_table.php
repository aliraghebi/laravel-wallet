<?php

use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Models\Transfer;
use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create($this->table(), function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('from_id')->constrained($this->walletsTable())->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('to_id')->constrained($this->walletsTable())->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('deposit_id')->constrained($this->transactionTable())->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('withdrawal_id')->constrained($this->transactionTable())->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('amount', 64, 0)->default(0);
            $table->decimal('fee', 64, 0)->default(0);
            $table->integer('decimal_places');
            $table->string('checksum')->nullable();
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
