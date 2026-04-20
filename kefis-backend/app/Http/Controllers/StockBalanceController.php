<?php

namespace App\Http\Controllers;

use App\Models\StockBalance;
use Illuminate\Http\Request;

class StockBalanceController extends Controller
{
    /**
     * GET /stock-balances
     * Supports filters: branch_id, product_id, product_company_id
     * Returns stock broken down per product + company.
     */
    public function index(Request $request)
    {
        $query = StockBalance::with(['branch', 'product', 'productCompany']);

        if ($request->has('branch_id'))          $query->where('branch_id', $request->branch_id);
        if ($request->has('product_id'))         $query->where('product_id', $request->product_id);
        if ($request->has('product_company_id')) $query->where('product_company_id', $request->product_company_id);

        return response()->json($query->get());
    }

    /**
     * GET /stock-balances/{id}
     */
    public function show($id)
    {
        $stock = StockBalance::with(['branch', 'product', 'productCompany'])->find($id);
        if (!$stock) {
            return response()->json(['message' => 'Stock balance not found'], 404);
        }
        return response()->json($stock);
    }
}