<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    protected $guarded = [];

    public function p_v_one()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_one');
    }
    public function p_v_two()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_two');
    }
    public function p_v_three()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_three');
    }
}
