<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|max:255',
            'sku' => ['required', 'max:255', Rule::unique('products')->ignore($this->product->id, 'id')],
            'description' => 'sometimes|string',
            'product_image' => 'sometimes|array|min:1', // Update to allow for the product image to be optional
            'product_image.*' => 'required_if:product_image,present|file', // Update to only require the image file if the product image array is present
            'product_variant' => 'sometimes|array|min:1', // Update to allow for the product variant to be optional
            'product_variant_prices' => 'required_if:product_variant,present|array|min:1', // Update to only require the variant prices if the product variant array is present
        ];
    }
}
