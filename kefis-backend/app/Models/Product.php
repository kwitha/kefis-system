<?php

namespace App\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'sku', 'category', 'unit',
          'is_active',
        
    ];

    protected $casts = [
        'unit' => 'integer',
        'is_active'     => 'boolean',
        
    ];

    public function productCompanies()
    {
        return $this->hasMany(ProductCompany::class);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'product_companies')
            ->withPivot( 'buying_price',
            'selling_price',
            'stock',
            'minimum_stock',
            'is_active')
            ->withTimestamps();
    }
 
    public function isGrocery():bool
    {
        return $this->category === 'Groceries';
    }
    public function activeCompanies()
    {
        return $this->hasMany(ProductCompany::class)->where('is_active', true);
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

    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }

    // ─── Stock helpers (per branch) ───────────────────────────────

    /**
     * Total stock across all companies at a branch.
     */
    public function stockAtBranch(int $branchId): float
    {
        return (float) $this->stockBalances()
            ->where('branch_id', $branchId)
            ->sum('quantity');
    }

    /**
     * Stock at a branch for a specific company (Grocery only).
     */
    public function stockAtBranchForCompany(int $branchId, int $companyId): float
    {
        $balance = $this->stockBalances()
            ->where('branch_id', $branchId)
            ->where('product_company_id', $companyId)
            ->first();
        return $balance ? (float) $balance->quantity : 0;
    }

    public function isLowStockAtBranch(int $branchId): bool
    {
        $qty = $this->stockAtBranch($branchId);
        return $qty > 0 && $qty < $this->minimum_stock;
    }

    public function isOutOfStockAtBranch(int $branchId): bool
    {
        return $this->stockAtBranch($branchId) <= 0;
    }

    public function stockStatusAtBranch(int $branchId): string
    {
        if ($this->isOutOfStockAtBranch($branchId)) return 'out_of_stock';
        if ($this->isLowStockAtBranch($branchId))   return 'low';
        return 'ok';
    }
}