<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductCompanyController extends Controller
{
    /**
     * GET /products/{productId}/companies
     * List all companies for a product.
     */
    public function index($productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product->companies()->get());
    }

    /**
     * POST /products/{productId}/companies
     * Add a company to a grocery product (max 4).
     */
    public function store(Request $request, $productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        if (!$product->isGrocery()) {
            return response()->json(['message' => 'Companies can only be added to Grocery products'], 422);
        }

        if ($product->companies()->count() >= 4) {
            return response()->json(['message' => 'A product can have a maximum of 4 companies'], 422);
        }

        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'price_per_unit' => 'required|numeric|min:0',
            'is_active'      => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company = $product->companies()->create([
            'name'           => $request->name,
            'price_per_unit' => $request->price_per_unit,
            'is_active'      => $request->input('is_active', true),
        ]);

        return response()->json([
            'message' => 'Company added successfully',
            'company' => $company,
        ], 201);
    }

    /**
     * PUT /products/{productId}/companies/{companyId}
     * Update a company's name or price.
     */
    public function update(Request $request, $productId, $companyId)
    {
        $company = ProductCompany::where('product_id', $productId)->find($companyId);
        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'           => 'sometimes|string|max:255',
            'price_per_unit' => 'sometimes|numeric|min:0',
            'is_active'      => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company->update($request->only('name', 'price_per_unit', 'is_active'));

        return response()->json([
            'message' => 'Company updated successfully',
            'company' => $company,
        ]);
    }

    /**
     * DELETE /products/{productId}/companies/{companyId}
     * Remove a company from a product.
     */
    public function destroy($productId, $companyId)
    {
        $company = ProductCompany::where('product_id', $productId)->find($companyId);
        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $company->delete();

        return response()->json(['message' => 'Company removed successfully']);
    }
}