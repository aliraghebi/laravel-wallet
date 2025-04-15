<?php

declare(strict_types=1);

use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create($this->table(), static function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->morphs('holder');
            $table->string('name');
            $table->string('slug')->index();
            $table->string('description')->nullable();
            $table->jsonb('meta')->nullable();
            $table->decimal('balance', 64, 0)->default(0);
            $table->decimal('frozen_amount', 64, 0)->default(0);
            $table->unsignedSmallInteger('decimal_places')->default(2);
            $table->string('checksum');
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
