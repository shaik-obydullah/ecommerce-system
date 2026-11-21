<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog\Resources;

use App\Core\Catalog\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Category $category */
        $category = $this->resource;

        return [
            'id' => $category->public_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'parent_id' => $this->whenLoaded('parent', fn (): ?string => $category->parent?->public_id),
            'product_count' => $this->when(
                isset($category->products_count),
                fn (): int => (int) $category->products_count,
            ),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
