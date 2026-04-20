<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class company extends Model
{
    protected $fillable=[
        'name',
        'is_active',
    ];

    public function products()
    {
        return $this->belongsToMany(product::class,'product_companies')
        ->withPivot( 'buying_price',
            'selling_price',
            'stock',
            'minimum_stock',
            'is_active')
        ->withTimestamps();
    }
    public function productcompanies()
    {
        return $this->hasMany(ProductCompany::class);
    }
}
