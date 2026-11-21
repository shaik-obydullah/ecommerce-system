<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog\Requests;

use App\Core\Support\Money\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexProductsRequest extends FormRequest
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
            'currency' => ['sometimes', 'string', 'size:3', Rule::in(Currency::supportedCodes())],
            'category' => ['sometimes', 'string', 'max:120'],
            'q' => ['sometimes', 'string', 'max:120'],
            'featured' => ['sometimes', 'boolean'],
            'in_stock' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'string', Rule::in(['newest', 'name', 'price_asc', 'price_desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function currency(): Currency
    {
        $code = $this->validated('currency');

        return is_string($code) ? Currency::fromCode($code) : Currency::default();
    }

    /**
     * @return array{category: string|null, q: string|null, featured: bool, in_stock: bool, sort: string}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'category' => isset($validated['category']) ? (string) $validated['category'] : null,
            'q' => isset($validated['q']) ? (string) $validated['q'] : null,
            'featured' => (bool) ($validated['featured'] ?? false),
            'in_stock' => (bool) ($validated['in_stock'] ?? false),
            'sort' => isset($validated['sort']) ? (string) $validated['sort'] : 'newest',
        ];
    }

    public function perPage(): ?int
    {
        $value = $this->validated('per_page');

        return is_numeric($value) ? (int) $value : null;
    }
}
