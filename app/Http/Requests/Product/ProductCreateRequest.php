<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductCreateRequest extends FormRequest
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
            'sku' => 'required|unique:products|max:255',
            'description' => 'sometimes|string',
            'product_image' => 'required|array|min:1',
            'product_image.*' => 'required|file',
            'product_variant' => 'sometimes|array|min:1',
            'product_variant_prices' => 'required_if:product_variant,present|array|min:1',
        ];
    }
}
