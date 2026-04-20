<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Transfer extends Model
{
    protected $fillable = [
        'product_id', 'product_company_id',
        'from_branch_id', 'to_branch_id',
        'user_id', 'quantity', 'status', 'notes',
    ];
 
    public function product()        { return $this->belongsTo(Product::class); }
    public function productCompany() { return $this->belongsTo(ProductCompany::class); }
    public function fromBranch()     { return $this->belongsTo(Branch::class, 'from_branch_id'); }
    public function toBranch()       { return $this->belongsTo(Branch::class, 'to_branch_id'); }
    public function user()           { return $this->belongsTo(User::class); }
}