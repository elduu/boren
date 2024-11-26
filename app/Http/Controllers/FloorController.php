<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Floor;
use App\Models\Building;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\PaymentForBuyer;
use App\Models\PaymentForTenant;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Models\Contract;

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
        // Validate the request data
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',  // Ensure building exists
            'name' => 'required|string|max:255',              // Floor name is required, max length 255 characters
        ]);
    
        try {
            // Retrieve the category_id from the specified building
            $building = Building::findOrFail($validated['building_id']);
            $categoryId = $building->category_id;
    
            // Create a new floor record, automatically setting the category_id from the building
            $floor = Floor::create([
                'building_id' => $validated['building_id'],
                'category_id' => $categoryId,
                'name' => $validated['name']
            ]);
    
            return response()->json([
                'message' => 'Floor created successfully',
                'data' => $floor
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while creating the floor. Please try again later.'
            ], 500);
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


public function listPaymentsInFloor(Request $request)
{
    DB::beginTransaction();
    try {
        // Validate the floor ID
        $validated = $request->validate([
            'floor_id' => 'required|exists:floors,id',
        ]);

        // Get the floor data
        $floor = Floor::findOrFail($validated['floor_id']);

        // Get the tenants in the floor (tenant_type and payment relationships should be defined)
        $tenants = Tenant::where('floor_id', $floor->id)->get();

        $tenantPayments = [];
        $buyerPayments = [];

        foreach ($tenants as $tenant) {
            // Check tenant type and assign payment
            if ($tenant->tenant_type === 'tenant') {
                $paymentfortenant = PaymentForTenant::where('tenant_id', $tenant->id)->get();
                $tenantPayments = array_merge($tenantPayments, $paymentfortenant->toArray());
            } elseif ($tenant->tenant_type === 'buyer') {
                $paymentforbuyer = PaymentForBuyer::where('buyer_id', $tenant->id)->get();
                $buyerPayments = array_merge($buyerPayments, $paymentforbuyer->toArray());
            }
        }

        DB::commit();

        // Return the payments data
        return response()->json([
            'success' => true,
            'tenant_payments' => $tenantPayments,
            'buyer_payments' => $buyerPayments
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error retrieving payments for floor:', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while fetching payments.'
        ], 500);
    }
}
public function listDocumentsInFloor(Request $request)
{
    DB::beginTransaction();
    try {
        // Validate the floor ID
        $validated = $request->validate([
            'floor_id' => 'required|exists:floors,id',
        ]);

        // Get the floor data
        $floor = Floor::findOrFail($validated['floor_id']);

        // Get all tenants in the floor
        $tenants = Tenant::where('floor_id', $floor->id)->get();

        $documents = [];

        foreach ($tenants as $tenant) {
            // Get documents for each tenant
            $tenantDocuments = Document::where('documentable_type', Tenant::class)
                                        ->where('documentable_id', $tenant->id)
                                        ->get();
            $documents = array_merge($documents, $tenantDocuments->toArray());
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'documents' => $documents
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error retrieving documents for floor:', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while fetching documents.'
        ], 500);
    }
}
public function listContractsInFloor(Request $request)
{
    DB::beginTransaction();
    try {
        // Validate the floor ID
        $validated = $request->validate([
            'floor_id' => 'required|exists:floors,id',
        ]);

        // Get the floor data
        $floor = Floor::findOrFail($validated['floor_id']);

        // Get all tenants in the floor
        $tenants = Tenant::where('floor_id', $floor->id)->get();

        $contracts = [];

        foreach ($tenants as $tenant) {
            // Get contracts for each tenant (Ensure Tenant has a 'contracts' relationship defined)
            $tenantContracts = Contract::where('tenant_id', $tenant->id)->get();
            $contracts = array_merge($contracts, $tenantContracts->toArray());
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'contracts' => $contracts
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error retrieving contracts for floor:', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while fetching contracts.'
        ], 500);
    }
}
}
