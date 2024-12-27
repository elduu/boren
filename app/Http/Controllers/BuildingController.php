<?php

namespace App\Http\Controllers;

use App\Models\Building;

use App\Models\Category;
use App\Models\Floor;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

use App\Models\Contract;
use App\Models\Tenant;
use Carbon\Carbon;

use Illuminate\Http\UploadedFile;
use App\Models\Document;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\AuditLog;

class BuildingController extends Controller
{
    // List all buildings
    public function index()
    {
        try {
            $buildings = Building::all();
            return response()->json($buildings, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to fetch buildings', 'message' => $e->getMessage()], 500);
        }
    }

    // Create a new building
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
        ]);

        try {
            $building = Building::create($request->all());
            return response()->json($building, 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create building', 'message' => $e->getMessage()], 500);
        }
    }

    // Show a specific building by ID
    public function show($id)
    {
        try {
            $building = Building::findOrFail($id);
            return response()->json($building, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Building not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to fetch building', 'message' => $e->getMessage()], 500);
        }
    }

    // Update a building by ID
    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
        ]);

        try {
            $building = Building::findOrFail($id);
            $building->update($request->all());
            return response()->json($building, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Building not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update building', 'message' => $e->getMessage()], 500);
        }
    }

    // Soft delete a building by ID
    public function destroy($id)
    {
        try {
            $building = Building::findOrFail($id);
            $building->delete();
            return response()->json(['message' => 'Building soft-deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Building not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete building', 'message' => $e->getMessage()], 500);
        }
    }

    // Retrieve all soft-deleted buildings
    public function trashed()
    {
        try {
            $deletedBuildings = Building::onlyTrashed()->get();

            if ($deletedBuildings->isEmpty()) {
                return response()->json(['message' => 'No soft-deleted buildings found'], 200);
            }

            return response()->json($deletedBuildings, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to fetch soft-deleted buildings', 'message' => $e->getMessage()], 500);
        }
    }

    public function restore($id)
{
    try {
        $building = Building::onlyTrashed()->findOrFail($id);
        $building->restore();

        return response()->json(['message' => 'Building restored successfully', 'data' => $building], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['error' => 'Building not found in trash'], 404);
    } catch (Exception $e) {
        return response()->json(['error' => 'Failed to restore building', 'message' => $e->getMessage()], 500);
    }
}

public function search(Request $request)
    {
        $query = $request->input('query');
        if (empty($query)) {
            return response()->json(['success' => true, 'data' => [], 'message' => 'No search term provided.'], 200);
        }

        try {
            $buildings = Building::where('name', 'like', "%{$query}%")
                ->get();

            return response()->json(['success' => true, 'data' => $buildings], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to perform search: ' . $e->getMessage()], 500);
        }
    }


    public function listFloorsInBuilding($buildingId)
    {
        try {
            $building = Building::find($buildingId);
    
            if (!$building) {
                return response()->json(['success' => false, 'message' => 'Building not found.'], 404);
            }
    
            // Eager load floors with their category
            $floors = $building->floors()->with('category')->get();
    
            // Format the response to include category name
            $formattedFloors = $floors->map(function ($floor) {
                return [
                    'id' => $floor->id,
                    'name' => $floor->name,
                    'category_name' => $floor->category->name ?? null, // Safely access category name
                ];
            });
    
            return response()->json(['success' => true, 'data' => $formattedFloors], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch floors: ' . $e->getMessage()], 500);
        }
    }
public function filterFloorsInCategory(Request $request)
{
    $categoryName = $request->input('name');

    // Validate input
    if (empty($categoryName)) {
        return response()->json(['success' => false, 'message' => 'Category name is required.'], 400);
    }

    try {
        // Retrieve category by name
        $category = Category::where('name', $categoryName)->first();

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
        }

        // Fetch floors associated with buildings in the category
        $floors = Floor::whereHas('building', function ($query) use ($category) {
            $query->where('category_id', $category->id);
        })->get();

        return response()->json(['success' => true, 'data' => $floors], 200);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Failed to fetch floors: ' . $e->getMessage()], 500);
    }
}



}
