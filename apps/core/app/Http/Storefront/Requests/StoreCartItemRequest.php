<?php

declare(strict_types=1);

namespace App\Http\Storefront\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCartItemRequest extends FormRequest
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
            'product_id' => ['required', 'string', 'size:26'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function productId(): string
    {
        return (string) $this->validated('product_id');
    }

    public function quantity(): int
    {
        $quantity = $this->validated('quantity');

        return is_numeric($quantity) ? (int) $quantity : 1;
    }
}
