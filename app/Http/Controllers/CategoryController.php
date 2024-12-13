<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Contract;
use App\Models\Building;


class CategoryController extends Controller
{
    public function index()
{
    $categories = Category::where('id', '!=', 3)->get();
    return response()->json($categories, 200);
}

    // Store a newly created category in storage.
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:categories,name',
            ], [
                'name.unique' => 'The category name has already been taken.', // Custom message for the unique rule
            ]);
    
            $category = Category::create(['name' => $request->name]);
    
            return response()->json($category, 201); // Successfully created
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation error',
                'details' => $e->errors(),
            ], 422); // Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred while creating the category',
                'message' => $e->getMessage(),
            ], 500); // Internal Server Error
        }
    }

    // Display the specified category.
    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            return response()->json($category, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
           return response()->json([
                'error' => 'Category not found'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while retrieving the category'
            ], 500);
        }
    }

    // Update the specified category in storage.
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|unique:categories,name,' . $id,
        ]);

        $category->update(['name' => $request->name]);

        return response()->json($category, 200);
    }

    // Remove the specified category from storage.
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

    // Soft delete the category
    $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
    public function search($name)
    {
        $categories = Category::where('name', 'LIKE', '%' . $name . '%')->get();

        if ($categories->isEmpty()) {
            return response()->json(['message' => 'No categories found'], 200);
        }

        return response()->json($categories, 200);
    }

    public function restore($id)
{
    $category = Category::onlyTrashed()->findOrFail($id);

    $category->restore();

    return response()->json(['message' => 'Category restored successfully'], 200);
}

public function trashed()
{
    $deletedCategories = Category::onlyTrashed()->get();

    if ($deletedCategories->isEmpty()) {
        return response()->json(['message' => 'No soft-deleted categories found'], 200);
    }

    return response()->json($deletedCategories, 200);
} public function buildingsInCategoryid($categoryId)
{
    $category = Category::find($categoryId);

    if (!$category) {
        return response()->json(['success' => false, 'message' => 'Category not found'], 404);
    }

    try {
        $buildings = $category->buildings;
        $data = $buildings->map(function ($building) use ($category) {
           return [
               'category_name' => $category->name,
               'category_id' => $category->id,
               'building_name' => $building->name,
               'id' => $building->id,
           ];
       });
         // Assuming 'buildings' relationship exists in Category model
        return response()->json(['success' => true, 'data'=>$data,
       ], 200);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Failed to fetch buildings in category: ' . $e->getMessage()], 500);
    }
}
    public function buildingsInCategory(Request $request)
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

        // Fetch buildings related to the category
        $buildings = $category->buildings;

        return response()->json(['success' => true, 'data' => $buildings], 200);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Failed to fetch buildings: ' . $e->getMessage()], 500);
    }
}

 
}
