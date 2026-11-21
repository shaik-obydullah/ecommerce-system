<?php

declare(strict_types=1);

namespace App\Core\Support\Database;

use Illuminate\Support\Str;

/**
 * Adds a public ULID alongside the internal numeric primary key.
 *
 * Public identifiers never expose auto-incrementing IDs and remain stable if
 * the internal key strategy changes.
 */
trait HasPublicId
{
    protected static function bootHasPublicId(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->getAttribute('public_id'))) {
                $model->setAttribute('public_id', (string) Str::ulid());
            }
        });
    }
}
