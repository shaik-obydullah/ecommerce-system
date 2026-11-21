<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class VersionController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(
            [
                'data' => [
                    'name' => (string) config('app.name'),
                    'version' => (string) config('ecommerce.version'),
                    'commit' => config('ecommerce.commit'),
                    'environment' => app()->environment(),
                    'storefront_mode' => (string) config('ecommerce.storefront_mode'),
                    'api_version' => (string) config('ecommerce.api.version'),
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                ],
            ],
            200,
            [],
            JSON_UNESCAPED_SLASHES,
        );
    }
}
