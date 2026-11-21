<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignId('stock_id')
                ->constrained('stocks')
                ->cascadeOnDelete();
            $table->string('type', 20);
            $table->integer('quantity');
            $table->bigInteger('unit_buy_price_amount')->nullable();
            $table->bigInteger('unit_sale_price_amount')->nullable();
            $table->char('currency', 3);
            $table->string('reference')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['product_id', 'occurred_at']);
            $table->index(['stock_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
