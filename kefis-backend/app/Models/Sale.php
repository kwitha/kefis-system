<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Sale extends Model
{
    protected $fillable = [
        'branch_id', 'product_id', 'product_company_id', 'user_id',
        'quantity', 'unit_price', 'total_amount',
        'customer_name', 'sale_date', 'reference',
    ];
 
    public function branch()         { return $this->belongsTo(Branch::class); }
    public function product()        { return $this->belongsTo(Product::class); }
    public function productCompany() { return $this->belongsTo(ProductCompany::class); }
    public function user()           { return $this->belongsTo(User::class); }
}