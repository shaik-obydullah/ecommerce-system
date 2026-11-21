<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Core\Catalog\Models\Product;
use App\Core\Catalog\Services\CatalogQuery;
use App\Core\Pricing\Services\ProductPricing;
use App\Http\Controllers\Api\V1\Catalog\Requests\IndexProductsRequest;
use App\Http\Controllers\Api\V1\Catalog\Requests\ShowProductRequest;
use App\Http\Controllers\Api\V1\Catalog\Resources\ProductResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProductController extends Controller
{
    public function index(
        IndexProductsRequest $request,
        CatalogQuery $catalog,
        ProductPricing $pricing,
    ): AnonymousResourceCollection {
        $currency = $request->currency();
        $products = $catalog->products($request->filters(), $request->perPage());

        $products->through(
            fn (Product $product): ProductResource => ProductResource::for($product, $currency, $pricing),
        );

        return ProductResource::collection($products);
    }

    public function show(
        ShowProductRequest $request,
        Product $product,
        ProductPricing $pricing,
    ): ProductResource {
        abort_unless($product->is_active, 404);

        $product = Product::query()
            ->withStockSummary()
            ->with(['category' => fn ($query) => $query->select('id', 'public_id', 'name', 'slug')])
            ->findOrFail($product->getKey());

        return ProductResource::for($product, $request->currency(), $pricing);
    }
}
