<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cart\Requests;

use App\Core\Support\Money\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCartRequest extends FormRequest
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
            'replace' => ['sometimes', 'boolean'],
        ];
    }

    public function shouldReplace(): bool
    {
        return (bool) $this->validated('replace', false);
    }
}
