<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    // Get all products — include active companies for grocery products
public function index(Request $request)
{
    $branchId = $request->branch_id ? (int) $request->branch_id : null;

    $products = Product::with([
        'stockBalances',
        'companies' => function ($q) {
            $q->wherePivot('is_active', true)
              ->withPivot('buying_price', 'selling_price', 'stock', 'is_active');
        },
    ])->get();

    $formatted = $products->map(function ($product) use ($branchId) {

        $base = [
            'id'           => $product->id,
            'name'         => $product->name,
            'sku'          => $product->sku,
            'unit'         => $product->unit,
            'category'     => $product->category,
            'stock_status' => $branchId
                ? $product->stockStatusAtBranch($branchId)
                : 'ok',
        ];

        if ($product->category === 'Groceries') {
            // Multiple suppliers — each with real stock from stock_balances
            $base['companies'] = $product->companies->map(function ($company) use ($product, $branchId) {

                $pivotId = \App\Models\ProductCompany::where('product_id', $product->id)
                    ->where('company_id', $company->id)
                    ->value('id');

                $stock = ($branchId && $pivotId)
                    ? \App\Models\StockBalance::where('product_company_id', $pivotId)
                        ->where('branch_id', $branchId)
                        ->sum('quantity')
                    : 0;

                return [
                    'id'            => $company->pivot->id,
                    'company_id'    => $company->id,
                    'name'          => $company->name,
                    'buying_price'  => $company->pivot->buying_price,
                    'selling_price' => $company->pivot->selling_price,
                    'stock'         => $stock,
                ];
            });

            $base['buying_price']  = null;
            $base['selling_price'] = null;

        } else {
            // Single General Supplier — expose its prices directly on the product
            $general = $product->companies->first();

            $base['buying_price']  = $general?->pivot->buying_price ?? 0;
            $base['selling_price'] = $general?->pivot->selling_price ?? 0;
            $base['companies']     = []; // non-grocery never shows company dropdown
        }

        return $base;
    });

    return response()->json($formatted);
}
           
    // Get single product with companies
    public function show($id)
    {
        $product = Product::with('activeCompanies')->find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        return response()->json($product);
    }

    // Create product
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255|unique:products',
            'sku'           => 'nullable|string|max:100|unique:products',
            'category'      => 'nullable|string|max:100',
            'unit'          => 'required|string|max:50',
            'buying_price'  => 'required|numeric|min:0',
            'selling_price' => 'required_if:category,grocery|nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::create($request->only(
            'name', 'sku', 'category', 'unit', 'buying_price', 'selling_price'
        ));

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
        ], 201);
    }

    // Update product
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'sometimes|string|max:255|unique:products,name,'.$id,
            'sku'           => 'sometimes|string|max:100|unique:products,sku,'.$id,
            'category'      => 'nullable|string|max:100',
            'unit'          => 'sometimes|string|max:50',
            'buying_price'  => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|nullable|numeric|min:0',
            'is_active'     => 'sometimes|boolean',
            'minimum_stock' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product->update($request->only(
            'name', 'sku', 'category', 'unit',
            'buying_price', 'selling_price', 'is_active', 'minimum_stock'
        ));

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->load('activeCompanies'),
        ]);
    }

    // Delete product
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }
}