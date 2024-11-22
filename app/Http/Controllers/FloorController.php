<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Floor;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FloorController extends Controller
{
    public function index()
    {
        try {
            $floors = Floor::with(['building', 'category'])->get();
            return response()->json(['data' => $floors], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching floors: ' . $e->getMessage()], 500);
        }
    }

    // Store a new floor
    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
        ]);

        try {
            $floor = Floor::create($validated);
            return response()->json(['message' => 'Floor created successfully', 'data' => $floor], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error creating floor: ' . $e->getMessage()], 500);
        }
    }

    // Soft delete a floor
    public function destroy($id)
    {
        try {
            $floor = Floor::findOrFail($id);
            $floor->delete();
            return response()->json(['message' => 'Floor deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting floor: ' . $e->getMessage()], 500);
        }
    }

    // Restore a soft-deleted floor
    public function restore($id)
    {
        try {
            $floor = Floor::withTrashed()->findOrFail($id);
            $floor->restore();
            return response()->json(['message' => 'Floor restored successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error restoring floor: ' . $e->getMessage()], 500);
        }
    }

    // Search floors by name
    public function search(Request $request)
    {
        $searchTerm = $request->query('name');
        try {
            $floors = Floor::where('name', 'like', '%' . $searchTerm . '%')->get();
            return response()->json(['data' => $floors], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error searching floors: ' . $e->getMessage()], 500);
        }
    }
    public function listTenantsInFloor($floorId)
{
    try {
        // Retrieve the floor with its tenants using the `floorId`
        $floor = Floor::with('tenants')->findOrFail($floorId);

        // Return the tenants related to the specified floor
        return response()->json([
            'success' => true,
            'data' => $floor->tenants,
        ], 200);
    } catch (ModelNotFoundException $e) {
        // Handle case where the floor ID is not found
        return response()->json([
            'success' => false,
            'message' => 'Floor not found.',
        ], 404);
    } catch (\Exception $e) {
        // Handle any other errors
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while retrieving tenants.',
        ], 500);
    }
}
}
