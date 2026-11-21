<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Core\Catalog\Models\Category;
use App\Http\Controllers\Api\V1\Catalog\Resources\CategoryResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->with('parent:id,public_id')
            ->withCount(['products' => fn ($query) => $query->active()])
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(Category $category): CategoryResource
    {
        abort_unless($category->is_active, 404);

        $category->load([
            'parent:id,public_id',
            'children' => fn ($query) => $query->getQuery()->active()->ordered(),
        ])->loadCount(['products' => fn ($query) => $query->active()]);

        return CategoryResource::make($category);
    }
}
