<?php

declare(strict_types=1);

namespace App\Core\Support\Settings;

use Illuminate\Database\Eloquent\Model;

/**
 * A persisted store setting. Values are stored as JSONB so scalars, arrays,
 * booleans, and null all round-trip without ad-hoc serialization.
 */
final class Setting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
