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
use Carbon\Carbon;
use App\Models\AuditLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;


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
    public function update(Request $request, $id)
{
    // Validate the request data
    $validated = $request->validate([
        'building_id' => 'nullable|exists:buildings,id', // Ensure building exists
        'name' => 'nullable|string|max:255',            // Floor name is required, max length 255 characters
    ]);

    try {
        // Find the existing floor record
        $floor = Floor::findOrFail($id);

        // Retrieve the category_id from the specified building
        $building = Building::findOrFail($validated['building_id']);
        $categoryId = $building->category_id;

        // Update the floor record
        $floor->update([
            'building_id' => $validated['building_id'],
            'category_id' => $categoryId,
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Floor updated successfully',
            'data' => $floor
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'error' => 'Floor not found.'
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'An error occurred while updating the floor. Please try again later.'
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
                        $paymentData=$tenant->paymentsForTenant->sortByDesc('created_at')->first();

                        // Get the latest contract from the loaded collection
                        $contractData = $tenant->contracts->sortByDesc('created_at')->first();// Single contract per tenant
                        return [
                            'tenant_id' => $tenant->id,
                            'tenant_name' => $tenant->name,
                            'tenant_phone' => $tenant->phone_number,
                            'room_number' => $contractData->room_number ?? 'N/A',
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
            ], 200);
        }

        // Prepare the response data
        $floorData = [
            'floor_name' => $floor->name,
            'building_name' => $floor->building->name,
         'tenants' => $floor->tenants->map(function ($tenant) {
            $paymentData=$tenant->paymentsForTenant->sortByDesc('created_at')->first();

            // Get the latest contract from the loaded collection
            $contractData = $tenant->contracts->sortByDesc('created_at')->first();// Single contract per tenant
             // Get the most recent contract

    return [
        'tenant_id' => $tenant->id,
        'tenant_name' => $tenant->name,
        'tenant_phone' => $tenant->phone_number,
        'room_number' => $contractData->room_number ?? 'N/A',
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
            ], 200);
        }

        // Prepare the response data
        $floorData = [
            'floor_name' => $floor->name,
            'building_name' => $floor->building->name,
         'tenants' => $floor->tenants->map(function ($tenant) {
            $paymentData=$tenant->paymentsForBuyer->sortByDesc('created_at')->first();

            // Get the latest contract from the loaded collection
            $contractData = $tenant->contracts->sortByDesc('created_at')->first();/// Get the most recent contract

    return [
        'tenant_id' => $tenant->id,
        'tenant_name' => $tenant->name,
        'tenant_phone' => $tenant->phone_number,
        'room_number' => $contractData->room_number ?? null,
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
            ], 200);
        }

        // Prepare the response data
        $buildingData = [
            'building_name' => $building->name,
            'floors' => $building->floors->map(function ($floor) {
                return [
                    'floor_name' => $floor->name,
                    'tenants' => $floor->tenants->map(function ($tenant) {
                        $paymentData=$tenant->paymentsForBuyer->sortByDesc('created_at')->first();

                        // Get the latest contract from the loaded collection
                        $contractData = $tenant->contracts->sortByDesc('created_at')->first();/// Single contract per tenant
                        return [
                            'tenant_id' => $tenant->id,
                            'tenant_name' => $tenant->name,
                            'tenant_phone' => $tenant->phone_number,
                            'room_number' => $contractData->room_number ?? null,
                            //'area_m2' => $paymentData?->area_m2 ?? null,
                            'property_price' => $paymentData?->property_price ?? null,
                           // 'monthly_paid' => $paymentData?->monthly_paid ?? null,
                            'utility_fee' => $paymentData?->utility_fee ?? null,
                           // 'payment_made_until' => $paymentData?->payment_made_until ?? null,
                            'start_date' => $paymentData?->start_date ?? null,
                           'expiring_date' => $contractData?->expiring_date ?? null,
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
        return response()->json(['message' => 'No deleted floors found'], 200);
    }

    // Return the deleted floors
    return response()->json([
        'status' => 'success',
        'deleted_floors' => $deletedFloors
    ], 200);
}
public function storeContract(Request $request)
{
    // Custom validation error messages
    $messages = [
        'tenant_id.required' => 'Tenant ID is required.',
        'tenant_id.exists' => 'The specified Tenant ID does not exist.',
        'type.required' => 'The contract type is required.',
        'type.in' => 'The contract type must be either rental or purchased.',
        'status.required' => 'The contract status is required.',
        'status.in' => 'The contract status must be either active or expired.',
        'signing_date.required' => 'The signing date is required.',
        'signing_date.date' => 'The signing date must be a valid date.',
        'expiring_date.required' => 'The expiring date is required.',
        'expiring_date.date' => 'The expiring date must be a valid date.',
        'expiring_date.after' => 'The expiring date must be after the signing date.',
        'documents.array' => 'Documents must be an array.',
        'documents.*.file.required' => 'Each document must have a file.',
        'documents.*.file.file' => 'Each document must be a valid file.',
        'documents.*.document_type.string' => 'Document type must be a string.',
    ];

    // Validate form-data inputs
    $validator = Validator::make($request->all(), [
        'tenant_id' => 'required|exists:tenants,id',
        'type' => 'required|in:rental,purchased',
        'room_number' => 'required|string|max:255',
       // 'status' => 'required|in:active,expired',
        'signing_date' => 'required|date',
        'expiring_date' => 'required|date|after:signing_date',
        'documents' => 'nullable|array',
        'documents.*.file' => 'required_with:documents|file',
        'documents.*.document_type' => 'nullable|string',
    ], $messages);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    try {
        $validatedData = $validator->validated();

        // Calculate due date (one month before expiring date)
        $dueDate = Carbon::parse($validatedData['expiring_date'])->subMonth()->format('Y-m-d');

        $tenant = Tenant::findOrFail($validatedData['tenant_id']);
        $floorId = $tenant->floor_id; // Fetch the floor ID if required

        // Create the contract
        $contract = Contract::create(array_merge(
            $validatedData,
            [
                'due_date' => $dueDate,
                'contract_number' => uniqid('CON-'),
            ]
        ));

        // Update contract status based on the current date
        $currentDate = Carbon::now()->format('Y-m-d');
        // $expiringDate = Carbon::parse($validatedData['expiring_date']);

        // $contract->status = $expiringDate->gte($currentDate) ? 'active' : 'expired';
        // $contract->save();

        if ($request->has('documents')) {
            foreach ($request->documents as $document) {
                if (isset($document['file']) ){
                    // Store the file and retrieve the path
                    $documentPath = $this->storeDocumentFile($document['file'], $validatedData['tenant_id']);

                    // Detect the format for each file
                    $documentFormat = $this->detectDocumentFormat($document['file']);
                    $documentType = $document['document_type'] ?? 'lease_agreement';
                    
                $documentName = $document['file']->getClientOriginalName();
                $documentSize = $document['file']->getSize();                 
                    // Create a new Document record
                    Document::create([
                        'documentable_id' => $validatedData['tenant_id'],
                        'documentable_type' => Tenant::class,
                        'document_type' => $documentType,
                        'document_format' => $documentFormat,
                        'file_path' => $documentPath,
                        'contract_id'=>$contract->id,
                       'doc_name' => $documentName,
                'doc_size'=>$documentSize,
            'uploaded_by' => auth()->id(),
                    ]);
                }
            }
        }

        AuditLog::create([
            'auditable_id' => $contract->id,
            'auditable_type' => Contract::class,
            'user_id' => auth()->id(),
            'event' => 'created',
            'document_for' => 'contract',
        ]);

        return response()->json(['success' => true, 'data' => $contract], 201);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create contract and documents: ' . $e->getMessage()
        ], 500);
    }
}

// Helper method to store the document file
private function storeDocumentFile(UploadedFile $file, $tenantId)
{try {
    // Define the directory path where documents will be stored
    $directory = "documents/tenants/{$tenantId}";

    // Store the file and get the path
    $path = $file->store($directory, 'public');

    // Return the full URL
    return Storage::url($path);
    
} catch (\Exception $e) {
    // Handle errors gracefully
    throw new \Exception("Failed to store document: " . $e->getMessage());
}
}       
private function detectDocumentFormat(UploadedFile $file)
{
$mimeType = $file->getClientMimeType();

switch ($mimeType) {
case 'application/pdf':
    return 'pdf';
case 'application/msword':
case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
    return 'word';
case 'application/vnd.ms-excel':
case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
    return 'excel';
case 'image/jpeg':
case 'image/png':
    return 'image';
default:
    return 'unknown';
}
}
}