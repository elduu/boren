<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Floor;
use App\Models\Building;
use Illuminate\Support\Facades\Validator;
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
            'name'=> $tenant->name,
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
            'name'=> $tenant->name,
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
            'name'=> $tenant->name,
            'contracts' => $contracts,
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
public function getBuildingData(Request $request)
{
    try {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'building_id' => 'required|exists:buildings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Fetch the building with related data
        $building = Building::where('id', $request->building_id)
            ->whereHas('category', function ($query) {
                $query->where('name', 'commercial');
            })
            ->with(relations: [
                'floors' => function ($query) {
                    $query->with([
                        'tenants' => function ($tenantQuery) {
                            $tenantQuery->with([
                                'paymentsForTenant',
                                'contracts',
                            ]);
                        },
                    ]);
                },
            ])
            ->first();

        if (!$building) {
            return response()->json([
                'success' => false,
                'message' => 'Building not found or not in the commercial category.',
            ], 404);
        }

        // Prepare the response data
        $buildingData = [
            'building_name' => $building->name,
            'floors' => $building->floors->map(function ($floor) {
                return [
                    'floor_name' => $floor->name,
                    'tenants' => $floor->tenants->map(function ($tenant) {
                        $paymentData = $tenant->paymentsForTenant->first(); // Assuming one payment per tenant
                        $contractData = $tenant->contract; // Single contract per tenant
                        return [
                            'tenant_id' => $tenant->id,
                            'tenant_name' => $tenant->name,
                            'tenant_phone' => $tenant->phone_number,
                            'room_number' => $tenant->room_number,
                            'area_m2' => $paymentData?->area_m2 ?? null,
                            'unit_price' => $paymentData?->unit_price ?? null,
                            'monthly_paid' => $paymentData?->monthly_paid ?? null,
                            'utility_fee' => $paymentData?->utility_fee ?? null,
                            'payment_made_until' => $paymentData?->payment_made_until ?? null,
                            'signing_date' => $contractData?->signing_date ?? null,
                            'expiring_date' => $contractData?->expiring_date ?? null,
                            'due_date' => $contractData?->due_date ?? null,
                        ];
                    }),
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $buildingData,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch building data: ' . $e->getMessage(),
        ], 500);
    }
}
public function getFloorData(Request $request)
{
    try {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'floor_id' => 'required|exists:floors,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Fetch the floor with related data
        $floor = Floor::where('id', $request->floor_id)
            ->with([
                'building.category' => function ($query) {
                    $query->where('name', 'commercial'); // Ensure the building's category is 'commercial'
                },
                'tenants' => function ($tenantQuery) {
                    $tenantQuery->with([
                        'paymentsForTenant',
                        'contracts',
                    ]);
                },
            ])
            ->first();

        if (!$floor || !$floor->building || !$floor->building->category) {
            return response()->json([
                'success' => false,
                'message' => 'Floor not found or not part of a commercial building.',
            ], 404);
        }

        // Prepare the response data
        $floorData = [
            'floor_name' => $floor->name,
            'building_name' => $floor->building->name,
         'tenants' => $floor->tenants->map(function ($tenant) {
    $paymentData = $tenant->paymentsForTenant->first(); // Assuming one payment per tenant
    $contractData = $tenant->contracts
        ->sortByDesc('signing_date') // Sort contracts by signing_date in descending order
        ->first(); // Get the most recent contract

    return [
        'tenant_id' => $tenant->id,
        'tenant_name' => $tenant->name,
        'tenant_phone' => $tenant->phone_number,
        'room_number' => $tenant->room_number,
        'area_m2' => $paymentData?->area_m2 ?? null,
        'unit_price' => $paymentData?->unit_price ?? null,
        'monthly_paid' => $paymentData?->monthly_paid ?? null,
        'utility_fee' => $paymentData?->utility_fee ?? null,
        'payment_made_until' => $paymentData?->payment_made_until ?? null,
        'signing_date' => $contractData?->signing_date ?? null,
        'expiring_date' => $contractData?->expiring_date ?? null,
        'due_date' => $contractData?->due_date ?? null,
    ];
}),
            
        ];

        return response()->json([
            'success' => true,
            'data' => $floorData,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch floor data: ' . $e->getMessage(),
        ], 500);
    }
}
public function getFloorDataBuyer(Request $request)
{
    try {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'floor_id' => 'required|exists:floors,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Fetch the floor with related data
        $floor = Floor::where('id', $request->floor_id)
            ->with([
                'building.category' => function ($query) {
                    $query->where('name', 'Apartment_buyer'); // Ensure the building's category is 'commercial'
                },
                'tenants' => function ($tenantQuery) {
                    $tenantQuery->with([
                        'paymentsForBuyer',
                        'contracts',
                    ]);
                },
            ])
            ->first();

        if (!$floor || !$floor->building || !$floor->building->category) {
            return response()->json([
                'success' => false,
                'message' => 'Floor not found or not part of a buyer building.',
            ], 404);
        }

        // Prepare the response data
        $floorData = [
            'floor_name' => $floor->name,
            'building_name' => $floor->building->name,
         'tenants' => $floor->tenants->map(function ($tenant) {
    $paymentData = $tenant->paymentsForBuyer->first(); // Assuming one payment per tenant
    $contractData = $tenant->contracts
        ->sortByDesc('start_date') // Sort contracts by signing_date in descending order
        ->first(); // Get the most recent contract

    return [
        'tenant_id' => $tenant->id,
        'tenant_name' => $tenant->name,
        'tenant_phone' => $tenant->phone_number,
        'room_number' => $tenant->room_number,
       // 'area_m2' => $paymentData?->area_m2 ?? null,
        'property_price' => $paymentData?->property_price ?? null,
       // 'monthly_paid' => $paymentData?->monthly_paid ?? null,
        'utility_fee' => $paymentData?->utility_fee ?? null,
       // 'payment_made_until' => $paymentData?->payment_made_until ?? null,
        'start_date' => $paymentData?->start_date ?? null,
      //  'expiring_date' => $contractData?->expiring_date ?? null,
       // 'due_date' => $contractData?->due_date ?? null,
    ];
}),
            
        ];

        return response()->json([
            'success' => true,
            'data' => $floorData,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch floor data: ' . $e->getMessage(),
        ], 500);
    }
}
public function getBuildingDataBuyer(Request $request)
{
    try {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'building_id' => 'required|exists:buildings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Fetch the building with related data
        $building = Building::where('id', $request->building_id)
            ->whereHas('category', function ($query) {
                $query->where('name', 'Apartment_buyer');
            })
            ->with(relations: [
                'floors' => function ($query) {
                    $query->with([
                        'tenants' => function ($tenantQuery) {
                            $tenantQuery->with([
                                'paymentsForBuyer',
                                'contracts',
                            ]);
                        },
                    ]);
                },
            ])
            ->first();

        if (!$building) {
            return response()->json([
                'success' => false,
                'message' => 'Building not found or not in the buyer category.',
            ], 404);
        }

        // Prepare the response data
        $buildingData = [
            'building_name' => $building->name,
            'floors' => $building->floors->map(function ($floor) {
                return [
                    'floor_name' => $floor->name,
                    'tenants' => $floor->tenants->map(function ($tenant) {
                        $paymentData = $tenant->paymentsForBuyert->first(); // Assuming one payment per tenant
                        $contractData = $tenant->contract; // Single contract per tenant
                        return [
                            'tenant_id' => $tenant->id,
                            'tenant_name' => $tenant->name,
                            'tenant_phone' => $tenant->phone_number,
                            'room_number' => $tenant->room_number,
                            //'area_m2' => $paymentData?->area_m2 ?? null,
                            'property_price' => $paymentData?->property_price ?? null,
                           // 'monthly_paid' => $paymentData?->monthly_paid ?? null,
                            'utility_fee' => $paymentData?->utility_fee ?? null,
                           // 'payment_made_until' => $paymentData?->payment_made_until ?? null,
                            'start_date' => $paymentData?->start_date ?? null,
                           // 'expiring_date' => $contractData?->expiring_date ?? null,
                           // 'due_date' => $contractData?->due_date ?? null,
                        ];
                    }),
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $buildingData,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch building data: ' . $e->getMessage(),
        ], 500);
    }
}

public function listDeletedFloors()
{
    // Retrieve only deleted floors using the `onlyTrashed` method
    $deletedFloors = Floor::onlyTrashed()->get();

    // Check if there are any deleted floors
    if ($deletedFloors->isEmpty()) {
        return response()->json(['message' => 'No deleted floors found'], 404);
    }

    // Return the deleted floors
    return response()->json([
        'status' => 'success',
        'deleted_floors' => $deletedFloors
    ], 200);
}
}