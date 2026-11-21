<?php

declare(strict_types=1);

namespace App\Http\Storefront\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCartDiscountRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:64'],
        ];
    }

    public function code(): string
    {
        return (string) $this->validated('code');
    }
}
