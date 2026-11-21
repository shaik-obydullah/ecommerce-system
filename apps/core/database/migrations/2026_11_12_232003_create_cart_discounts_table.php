<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_discounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')
                ->constrained('carts')
                ->cascadeOnDelete();
            $table->foreignId('discount_id')
                ->constrained('discounts')
                ->cascadeOnDelete();
            $table->string('code', 64);
            $table->timestamps();

            // MVP rule: one coupon per cart. Stacking is a later feature.
            $table->unique('cart_id');
            $table->index('discount_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_discounts');
    }
};
