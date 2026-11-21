<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog\Requests;

use App\Core\Support\Money\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ShowProductRequest extends FormRequest
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
        ];
    }

    public function currency(): Currency
    {
        $code = $this->validated('currency');

        return is_string($code) ? Currency::fromCode($code) : Currency::default();
    }
}
