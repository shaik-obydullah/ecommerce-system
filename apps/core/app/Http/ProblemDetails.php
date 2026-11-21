<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RFC 9457 problem-details response builder.
 *
 * Every API error uses the same shape so clients can handle failures without
 * inspecting human-readable messages.
 */
final class ProblemDetails
{
    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, string>  $headers
     */
    public static function make(
        Request $request,
        int $status,
        ?string $title = null,
        ?string $detail = null,
        array $errors = [],
        ?string $type = null,
        array $headers = [],
    ): JsonResponse {
        $payload = [
            'type' => $type ?? 'https://httpstatuses.com/'.$status,
            'title' => $title ?? (Response::$statusTexts[$status] ?? 'Error'),
            'status' => $status,
            'detail' => $detail ?? (Response::$statusTexts[$status] ?? 'Request failed.'),
            'instance' => $request->getPathInfo(),
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json(
            $payload,
            $status,
            array_merge(['Content-Type' => 'application/problem+json'], $headers),
            JSON_UNESCAPED_SLASHES,
        );
    }
}
