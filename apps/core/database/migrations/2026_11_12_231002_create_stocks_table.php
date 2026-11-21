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
        Schema::create('stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->unsignedInteger('qty_on_hand')->default(0);
            $table->unsignedInteger('qty_reserved')->default(0);
            $table->bigInteger('buy_price_amount');
            $table->bigInteger('sale_price_amount');
            $table->char('currency', 3);
            $table->timestamp('received_at');
            $table->string('reference')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'received_at']);
            $table->index(['product_id', 'sale_price_amount']);
            $table->index(['product_id', 'deleted_at']);
        });

        DB::statement('ALTER TABLE stocks ADD CONSTRAINT stocks_qty_on_hand_non_negative CHECK (qty_on_hand >= 0)');
        DB::statement('ALTER TABLE stocks ADD CONSTRAINT stocks_qty_reserved_non_negative CHECK (qty_reserved >= 0)');
        DB::statement('ALTER TABLE stocks ADD CONSTRAINT stocks_reserved_not_above_on_hand CHECK (qty_reserved <= qty_on_hand)');
        DB::statement('ALTER TABLE stocks ADD CONSTRAINT stocks_buy_price_non_negative CHECK (buy_price_amount >= 0)');
        DB::statement('ALTER TABLE stocks ADD CONSTRAINT stocks_sale_price_non_negative CHECK (sale_price_amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
