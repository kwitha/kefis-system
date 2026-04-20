<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCompany;
use App\Models\Transfer;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransferController extends Controller
{
    // Get all transfers
    public function index(Request $request)
    {
        $query = Transfer::with(['product', 'productCompany', 'fromBranch', 'toBranch', 'user']);

        if ($request->has('from_branch_id')) $query->where('from_branch_id', $request->from_branch_id);
        if ($request->has('to_branch_id'))   $query->where('to_branch_id', $request->to_branch_id);
        if ($request->has('product_id'))     $query->where('product_id', $request->product_id);
        if ($request->has('status'))         $query->where('status', $request->status);

        return response()->json($query->latest()->get());
    }

    // Get single transfer
    public function show($id)
    {
        $transfer = Transfer::with(['product', 'productCompany', 'fromBranch', 'toBranch', 'user'])->find($id);
        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found'], 404);
        }
        return response()->json($transfer);
    }

    // Create a transfer
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id'         => 'required|exists:products,id',
            'product_company_id' => 'nullable|exists:product_companies,id',
            'from_branch_id'     => 'required|exists:branches,id',
            'to_branch_id'       => 'required|exists:branches,id|different:from_branch_id',
            'quantity'           => 'required|numeric|min:1',
            'notes'              => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::findOrFail($request->product_id);

        // Grocery must have company
        if ($product->isGrocery() && !$request->product_company_id) {
            return response()->json([
                'message' => 'A company must be selected for Grocery product transfers',
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
            // Check source stock (per branch + product + company)
            $fromStock = StockBalance::where('branch_id', $request->from_branch_id)
                ->where('product_id', $request->product_id)
                ->where('product_company_id', $request->product_company_id)
                ->first();

            if (!$fromStock || $fromStock->quantity < $request->quantity) {
                $label = $request->product_company_id
                    ? (ProductCompany::find($request->product_company_id)?->name ?? 'company')
                    : $product->name;

                return response()->json([
                    'message'   => "Insufficient stock for {$label} in source branch",
                    'available' => $fromStock ? $fromStock->quantity : 0,
                ], 422);
            }

            // Create transfer record
            $transfer = Transfer::create([
                'product_id'         => $request->product_id,
                'product_company_id' => $request->product_company_id,
                'from_branch_id'     => $request->from_branch_id,
                'to_branch_id'       => $request->to_branch_id,
                'user_id'            => auth()->id(),
                'quantity'           => $request->quantity,
                'status'             => 'completed',
                'notes'              => $request->notes,
            ]);

            // Deduct from source — same company stock
            $fromStock->decrement('quantity', $request->quantity);

            // Add to destination — same company stock
            $toStock = StockBalance::firstOrCreate(
                [
                    'branch_id'          => $request->to_branch_id,
                    'product_id'         => $request->product_id,
                    'product_company_id' => $request->product_company_id,
                ],
                ['quantity' => 0]
            );
            $toStock->increment('quantity', $request->quantity);

            DB::commit();

            return response()->json([
                'message'  => 'Transfer completed successfully',
                'transfer' => $transfer->load(['product', 'productCompany', 'fromBranch', 'toBranch', 'user']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to complete transfer', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete transfer and reverse stock
    public function destroy($id)
    {
        $transfer = Transfer::find($id);
        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found'], 404);
        }

        DB::beginTransaction();

        try {
            // Reverse source — add back same company stock
            $fromStock = StockBalance::where('branch_id', $transfer->from_branch_id)
                ->where('product_id', $transfer->product_id)
                ->where('product_company_id', $transfer->product_company_id)
                ->first();

            if ($fromStock) $fromStock->increment('quantity', $transfer->quantity);

            // Reverse destination — deduct same company stock
            $toStock = StockBalance::where('branch_id', $transfer->to_branch_id)
                ->where('product_id', $transfer->product_id)
                ->where('product_company_id', $transfer->product_company_id)
                ->first();

            if ($toStock) $toStock->decrement('quantity', $transfer->quantity);

            $transfer->delete();
            DB::commit();

            return response()->json(['message' => 'Transfer deleted and stock reversed successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete transfer', 'error' => $e->getMessage()], 500);
        }
    }
}