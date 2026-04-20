<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCompany;
use App\Models\Purchase;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{
    // Get all purchases
    public function index(Request $request)
    {
        $query = Purchase::with(['branch', 'product', 'productCompany', 'user']);

        if ($request->has('branch_id'))  $query->where('branch_id', $request->branch_id);
        if ($request->has('product_id')) $query->where('product_id', $request->product_id);

        return response()->json($query->latest()->get());
    }

    // Get single purchase
    public function show($id)
    {
        $purchase = Purchase::with(['branch', 'product', 'productCompany', 'user'])->find($id);
        if (!$purchase) {
            return response()->json(['message' => 'Purchase not found'], 404);
        }
        return response()->json($purchase);
    }

    // Record a purchase
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id'          => 'required|exists:branches,id',
            'product_id'         => 'required|exists:products,id',
            'product_company_id' => 'nullable|exists:product_companies,id',
            'quantity'           => 'required|numeric|min:1',
            'unit_price'         => 'required|numeric|min:0',
            'supplier'           => 'nullable|string|max:255',
            'purchase_date'      => 'required|date',
            'reference'          => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate: grocery products MUST have a company selected
        $product = Product::findOrFail($request->product_id);
        if ($product->isGrocery() && !$request->product_company_id) {
            return response()->json([
                'message' => 'A company must be selected for Grocery products',
            ], 422);
        }

        // Validate: company must belong to the selected product
        if ($request->product_company_id) {
            $company = ProductCompany::find($request->product_company_id);
            if (!$company || $company->product_id !== $product->id) {
                return response()->json([
                    'message' => 'Selected company does not belong to this product',
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            $total = $request->quantity * $request->unit_price;

            $purchase = Purchase::create([
                'branch_id'          => $request->branch_id,
                'product_id'         => $request->product_id,
                'product_company_id' => $request->product_company_id,
                'user_id'            => auth()->id(),
                'quantity'           => $request->quantity,
                'unit_price'         => $request->unit_price,
                'total_amount'       => $total,
                'supplier'           => $request->supplier,
                'purchase_date'      => $request->purchase_date,
                'reference'          => $request->reference,
            ]);

            // Stock tracked per branch + product + company (company null for non-grocery)
            $stock = StockBalance::firstOrCreate(
                [
                    'branch_id'          => $request->branch_id,
                    'product_id'         => $request->product_id,
                    'product_company_id' => $request->product_company_id,
                ],
                ['quantity' => 0]
            );

            $stock->increment('quantity', $request->quantity);

            DB::commit();

            return response()->json([
                'message'  => 'Purchase recorded successfully',
                'purchase' => $purchase->load(['branch', 'product', 'productCompany', 'user']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to record purchase', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete purchase and reverse stock
    public function destroy($id)
    {
        $purchase = Purchase::find($id);
        if (!$purchase) {
            return response()->json(['message' => 'Purchase not found'], 404);
        }

        DB::beginTransaction();

        try {
            $stock = StockBalance::where('branch_id', $purchase->branch_id)
                ->where('product_id', $purchase->product_id)
                ->where('product_company_id', $purchase->product_company_id)
                ->first();

            if ($stock) {
                $stock->decrement('quantity', $purchase->quantity);
            }

            $purchase->delete();
            DB::commit();

            return response()->json(['message' => 'Purchase deleted and stock reversed successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete purchase', 'error' => $e->getMessage()], 500);
        }
    }
}