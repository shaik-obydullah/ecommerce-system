<?php

declare(strict_types=1);

namespace App\Http\Storefront\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'version' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function quantity(): int
    {
        return (int) $this->validated('quantity');
    }

    public function version(): ?int
    {
        $version = $this->validated('version');

        return is_numeric($version) ? (int) $version : null;
    }
}
