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
        Schema::create('discounts', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('type', 20);
            $table->unsignedBigInteger('value');
            $table->char('currency', 3)->nullable();
            $table->bigInteger('min_subtotal_amount')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('per_user_limit')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        DB::statement('ALTER TABLE discounts ADD CONSTRAINT discounts_value_non_negative CHECK (value >= 0)');
        DB::statement("ALTER TABLE discounts ADD CONSTRAINT discounts_percentage_within_range CHECK (type <> 'percentage' OR value <= 10000)");
        DB::statement("ALTER TABLE discounts ADD CONSTRAINT discounts_fixed_currency_required CHECK (type <> 'fixed' OR currency IS NOT NULL)");
        DB::statement('ALTER TABLE discounts ADD CONSTRAINT discounts_min_subtotal_non_negative CHECK (min_subtotal_amount IS NULL OR min_subtotal_amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
