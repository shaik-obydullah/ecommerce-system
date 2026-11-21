<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('discount_id')
                ->constrained('discounts')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('cart_id')
                ->nullable()
                ->constrained('carts')
                ->nullOnDelete();
            $table->string('email')->nullable();
            $table->bigInteger('amount');
            $table->char('currency', 3);
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamp('used_at');
            $table->timestamps();

            $table->index(['discount_id', 'user_id']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_usages');
    }
};
