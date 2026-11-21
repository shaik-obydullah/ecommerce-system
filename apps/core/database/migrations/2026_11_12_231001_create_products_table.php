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
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_active']);
            $table->index(['is_active', 'is_featured']);
            $table->index('created_at');
        });

        // PostgreSQL full-text search over the public product copy. The column
        // is generated so it can never drift from the source fields.
        DB::statement(<<<'SQL'
            ALTER TABLE products
            ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (
                to_tsvector(
                    'simple',
                    coalesce(name, '') || ' ' ||
                    coalesce(short_description, '') || ' ' ||
                    coalesce(description, '')
                )
            ) STORED
        SQL);

        DB::statement('CREATE INDEX products_search_vector_index ON products USING GIN (search_vector)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
