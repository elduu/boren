<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;

class TenantController extends Controller
{
    public function index()
    {
        try {
            $tenants = Tenant::with(['building', 'category', 'floor'])->get();
            return response()->json(['success' => true, 'data' => $tenants], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created tenant.
     */
    public function store(Request $request)
    {
        try {
        // Validate input data
        $validatedData = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'category_id' => 'required|exists:categories,id',
            'floor_id' => 'required|exists:floors,id',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'phone_number' => ['required', 'unique:tenants,phone_number', 'regex:/^(\+251|0)[1-9]\d{8}$/'],  // Ethiopian phone number format
            'email' => 'required|email|unique:tenants,email',
            'room_number' => 'required|string|max:255|unique:tenants,room_number',
            'type' => 'required|in:buyer,tenant',
        ]);
    
      
            // Attempt to create a new tenant
            $tenant = Tenant::create(array_merge(
                $validatedData, 
                ['tenant_number' => uniqid('TEN-')]  // Generate unique tenant number
            ));
    
            return response()->json([
                'success' => true, 
                'message' => 'Tenant created successfully.', 
                'data' => $tenant
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database-related errors such as constraint violations
            return response()->json([
                'success' => false, 
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            // Handle all other errors
            return response()->json([
                'success' => false, 
                'message' => 'An unexpected error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific tenant.
     */
    public function show($id)
    {
        try {
            $tenant = Tenant::with(['building', 'category', 'floor'])->findOrFail($id);
            return response()->json(['success' => true, 'data' => $tenant], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * Update a specific tenant.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'building_id' => 'exists:buildings,id',
            'category_id' => 'exists:categories,id',
            'floor_id' => 'exists:floors,id',
            'name' => 'string|max:255',
            'gender' => 'in:male,female,other',
            'phone_number' => ['unique:tenants,phone_number,' . $id, 'regex:/^(\+251|0)[1-9]\d{8}$/'],
            'email' => 'email|unique:tenants,email,' . $id,
            'room_number' => 'string|max:255|unique:tenants,room_number'.$id,
            'type' => 'in:buyer,tenant',
        ]);

        try {
            $tenant = Tenant::findOrFail($id);
            $tenant->update($request->all());

            return response()->json(['success' => true, 'data' => $tenant], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Soft delete a tenant.
     */
    public function destroy($id)
    {
        try {
            $tenant = Tenant::findOrFail($id);
            $tenant->delete();

            return response()->json(['success' => true, 'message' => 'Tenant deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Restore a soft-deleted tenant.
     */
    public function restore($id)
    {
        try {
            $tenant = Tenant::withTrashed()->findOrFail($id);
            $tenant->restore();

            return response()->json(['success' => true, 'message' => 'Tenant restored successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Search for tenants by name or room number.
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        try {
            $tenants = Tenant::where('name', 'like', "%{$query}%")
                ->orWhere('room_number', 'like', "%{$query}%")
                ->orWhere('tenant_number', 'like', "%{$query}%")
                ->orWhere('phone_number', 'like', "%{$query}%")
                ->get();

            return response()->json(['success' => true, 'data' => $tenants], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    public function updateStatus(Request $request, $id)
{
    // Validate the status
    $request->validate([
        'status' => 'required|in:active,inactive',  // Only allow 'active' or 'inactive' statuses
    ]);

    try {
        // Find the tenant by ID
        $tenant = Tenant::findOrFail($id);

        // Update the status
        $tenant->status = $request->status;
        $tenant->save();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Tenant status updated successfully.',
            'data' => $tenant
        ], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // Handle case where tenant is not found
        return response()->json([
            'success' => false,
            'message' => 'Tenant not found.'
        ], 404);
    } catch (\Exception $e) {
        // Handle any other exception
        return response()->json([
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage()
        ], 500);
    }
}
}
