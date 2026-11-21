<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $healthy = ! in_array('error', array_column($checks, 'status'), true);

        return response()->json(
            [
                'data' => [
                    'status' => $healthy ? 'ok' : 'degraded',
                    'checks' => $checks,
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
            $healthy ? 200 : 503,
            [],
            JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * @return array{status: string, latency_ms?: int, message?: string}
     */
    private function checkDatabase(): array
    {
        $startedAt = hrtime(true);

        try {
            DB::connection()->getPdo()->query('select 1');

            return [
                'status' => 'ok',
                'latency_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            ];
        } catch (Throwable $exception) {
            return $this->failed($startedAt, $exception);
        }
    }

    /**
     * @return array{status: string, latency_ms?: int, message?: string}
     */
    private function checkCache(): array
    {
        $startedAt = hrtime(true);

        try {
            $key = 'health-check:'.now()->format('YmdHi');
            Cache::store()->put($key, 'ok', 60);

            if (Cache::store()->get($key) !== 'ok') {
                throw new \RuntimeException('Cache read did not return the written value.');
            }

            return [
                'status' => 'ok',
                'latency_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            ];
        } catch (Throwable $exception) {
            return $this->failed($startedAt, $exception);
        }
    }

    /**
     * @return array{status: string, latency_ms: int, message?: string}
     */
    private function failed(int $startedAt, Throwable $exception): array
    {
        $result = [
            'status' => 'error',
            'latency_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
        ];

        if (config('app.debug')) {
            $result['message'] = $exception->getMessage();
        }

        return $result;
    }
}
