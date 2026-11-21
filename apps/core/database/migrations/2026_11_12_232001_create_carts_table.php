<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->char('token_hash', 64)->nullable()->unique();
            $table->char('currency', 3);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });

        // An authenticated customer has at most one active cart. Guest carts
        // are keyed by the hashed token stored in the httpOnly cart cookie.
        DB::statement('CREATE UNIQUE INDEX carts_user_id_unique ON carts (user_id) WHERE user_id IS NOT NULL');
        DB::statement('ALTER TABLE carts ADD CONSTRAINT carts_owner_required CHECK (user_id IS NOT NULL OR token_hash IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
