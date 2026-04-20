<?php

namespace App\Http\Controllers;

use App\Mail\LowStockAlert;
use App\Models\Product;
use App\Models\ProductCompany;
use App\Models\Sale;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SaleController extends Controller
{
    // Get all sales
    public function index(Request $request)
    {
        $query = Sale::with(['branch', 'product', 'productCompany', 'user']);

        if ($request->has('branch_id'))  $query->where('branch_id', $request->branch_id);
        if ($request->has('product_id')) $query->where('product_id', $request->product_id);

        return response()->json($query->latest()->get());
    }

    // Get single sale
    public function show($id)
    {
        $sale = Sale::with(['branch', 'product', 'productCompany', 'user'])->find($id);
        if (!$sale) {
            return response()->json(['message' => 'Sale not found'], 404);
        }
        return response()->json($sale);
    }

    // Record a sale
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id'          => 'required|exists:branches,id',
            'product_id'         => 'required|exists:products,id',
            'product_company_id' => 'nullable|exists:product_companies,id',
            'quantity'           => 'required|numeric|min:1',
            'unit_price'         => 'required|numeric|min:0',
            'customer_name'      => 'nullable|string|max:255',
            'sale_date'          => 'required|date',
            'reference'          => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::findOrFail($request->product_id);

        // Grocery must have company
        if ($product->isGrocery() && !$request->product_company_id) {
            return response()->json([
                'message' => 'A company must be selected for Grocery products',
            ], 422);
        }

        // Company must belong to this product
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
            // ── 1. Check stock for this branch + product + company ────
            $stock = StockBalance::where('branch_id', $request->branch_id)
                ->where('product_id', $request->product_id)
                ->where('product_company_id', $request->product_company_id)
                ->first();

            if (!$stock || $stock->quantity < $request->quantity) {
                $companyName = $request->product_company_id
                    ? (ProductCompany::find($request->product_company_id)?->name ?? 'selected company')
                    : $product->name;

                return response()->json([
                    'message'   => "Insufficient stock for {$companyName}",
                    'available' => $stock ? $stock->quantity : 0,
                ], 422);
            }

            // ── 2. Block if at or below minimum stock ─────────────────
            $afterSale = $stock->quantity - $request->quantity;

            if ($stock->quantity <= $product->minimum_stock) {
                return response()->json([
                    'message'       => "Cannot complete sale. Stock for '{$product->name}' is at or below the minimum threshold of {$product->minimum_stock} {$product->unit}.",
                    'current_stock' => $stock->quantity,
                    'minimum_stock' => $product->minimum_stock,
                ], 422);
            }

            // ── 3. Record sale ────────────────────────────────────────
            $total = $request->quantity * $request->unit_price;

            $sale = Sale::create([
                'branch_id'          => $request->branch_id,
                'product_id'         => $request->product_id,
                'product_company_id' => $request->product_company_id,
                'user_id'            => auth()->id(),
                'quantity'           => $request->quantity,
                'unit_price'         => $request->unit_price,
                'total_amount'       => $total,
                'customer_name'      => $request->customer_name,
                'sale_date'          => $request->sale_date,
                'reference'          => $request->reference,
            ]);

            // ── 4. Deduct stock ───────────────────────────────────────
            $stock->decrement('quantity', $request->quantity);
            $stock->refresh();

            DB::commit();

            // ── 5. Low stock alert (outside transaction) ──────────────
            if ($stock->quantity <= $product->minimum_stock) {
                $this->sendLowStockAlert($product, $stock);
            }

            return response()->json([
                'message'         => 'Sale recorded successfully',
                'sale'            => $sale->load(['branch', 'product', 'productCompany', 'user']),
                'stock_remaining' => $stock->quantity,
                'stock_status'    => $product->stockStatusAtBranch($request->branch_id),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to record sale', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete sale and reverse stock
    public function destroy($id)
    {
        $sale = Sale::find($id);
        if (!$sale) {
            return response()->json(['message' => 'Sale not found'], 404);
        }

        DB::beginTransaction();

        try {
            $stock = StockBalance::where('branch_id', $sale->branch_id)
                ->where('product_id', $sale->product_id)
                ->where('product_company_id', $sale->product_company_id)
                ->first();

            if ($stock) {
                $stock->increment('quantity', $sale->quantity);
            }

            $sale->delete();
            DB::commit();

            return response()->json(['message' => 'Sale deleted and stock reversed successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete sale', 'error' => $e->getMessage()], 500);
        }
    }

    private function sendLowStockAlert(Product $product, StockBalance $stock): void
    {
        $recipient = config('mail.stock_alert_email', config('mail.from.address'));
        try {
            Mail::to($recipient)->send(new LowStockAlert($product, $stock));
        } catch (\Exception $e) {
            logger()->error('Low stock alert email failed: ' . $e->getMessage());
        }
    }
}