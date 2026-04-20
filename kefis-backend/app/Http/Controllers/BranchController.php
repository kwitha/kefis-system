<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    // Get all branches
    public function index()
    {
        $branches = Branch::withCount('users')->get();
        return response()->json($branches);
    }

    // Get single branch
    public function show($id)
    {
        $branch = Branch::withCount('users')->find($id);
        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }
        return response()->json($branch);
    }

    // Create branch
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255|unique:branches',
            'location' => 'nullable|string',
            'phone'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $branch = Branch::create($request->only('name', 'location', 'phone'));

        return response()->json([
            'message' => 'Branch created successfully',
            'branch'  => $branch,
        ], 201);
    }

    // Update branch
    public function update(Request $request, $id)
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'      => 'sometimes|string|max:255|unique:branches,name,'.$id,
            'location'  => 'nullable|string',
            'phone'     => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $branch->update($request->only('name', 'location', 'phone', 'is_active'));

        return response()->json([
            'message' => 'Branch updated successfully',
            'branch'  => $branch,
        ]);
    }

    // Delete branch
    public function destroy($id)
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        $branch->delete();
        return response()->json(['message' => 'Branch deleted successfully']);
    }
}