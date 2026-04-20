<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['name', 'location', 'phone', 'is_active'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function transfersFrom()
    {
        return $this->hasMany(Transfer::class, 'from_branch_id');
    }

    public function transfersTo()
    {
        return $this->hasMany(Transfer::class, 'to_branch_id');
    }
}