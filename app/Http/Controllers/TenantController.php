<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use App\Models\Tenant;
use App\Models\Contract;
use App\Models\PaymentForTenant;
use App\Models\PaymentForBuyer;
use App\Models\Document;
use App\Models\Floor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


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

    public function store(Request $request)
    {
        try {
            // Start DB transaction
            DB::beginTransaction();
    
            // Step 1: Validate the request data
            $validatedData = $this->validateTenantData($request);
    
            // Step 2: Find Floor to get building_id and category_id
            $floor = Floor::findOrFail($validatedData['floor_id']);
            $validatedData['building_id'] = $floor->building_id;
            $validatedData['category_id'] = $floor->category_id;
    
            // Step 3: Calculate due date (one month from expiring_date)
            $validatedData['due_date'] = date('Y-m-d', strtotime($validatedData['expiring_date'] . ' +1 month'));
    
            // Step 4: Create the Tenant record
            $tenant = Tenant::create(array_merge(
                $validatedData,
                ['tenant_number' => uniqid('TEN-')]
            ));
    
            // Step 5: Create the Contract for the Tenant
            $contract = $this->createContract($tenant->id, $validatedData);
    
            // Step 6: Create Payment for Tenant if tenant_type is 'tenant'
            $paymentForTenant = null;
            if ($tenant->tenant_type === 'tenant') {
                $paymentForTenant = $this->createTenantPayment($tenant->id, $validatedData);
                Log::info('Created Payment for Tenant:', ['paymentForTenant' => $paymentForTenant]);
            }
    
            // Step 7: Store Document files and detect format
            if ($request->has('documents')) {
                foreach ($request->documents as $document) {
                    if (isset($document['file']) && isset($document['document_type'])) {
                        // Assign foreign keys based on document type
                        $contractId = null;
                        $paymentId = null;
    
                        if ($document['document_type'] === 'payment_receipt') {
                            $paymentId =  $paymentForTenant->id ;
                        }
    
                        if ($document['document_type'] === 'lease_agreement') {
                            $contractId = $contract->id;
                        }
    
                        Log::info('Assigned IDs:', [
                            'contract_id' => $contractId,
                            'payment_for_tenant_id' => $paymentId,
                            'document_type' => $document['document_type']
                        ]);
    
                        // Store the document file
                        $documentPath = $this->storeDocumentFile($document['file'], $tenant->id);
    
                        // Detect the format for each file
                        $documentFormat = $this->detectDocumentFormat($document['file']);
    
                        // Create a new Document record for each file
                        $documentRecord = Document::create([
                            'documentable_id' => $tenant->id,
                            'documentable_type' => Tenant::class,
                            'document_type' => $document['document_type'],
                            'document_format' => $documentFormat,
                            'file_path' => $documentPath,
                            'contract_id' => $contractId,
                            'payment_for_tenant_id' => $paymentId,
                        ]);
    
                        Log::info('Document created successfully:', $documentRecord->toArray());
                    }
                }
            }
    
            // Commit the transaction
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Tenant, contract, payment, and document created successfully.',
                'data' => $tenant,
                'contract_id' => $contract->id,
            ], 201);
    
        } catch (\Exception $e) {
            // Roll back the transaction in case of error
            DB::rollBack();
            Log::error('An error occurred:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    private function storeDocumentFile(UploadedFile $file, $tenantId)
    {
        $directory = "documents/tenants/{$tenantId}";
        return $file->store($directory, 'public');
    }
    
    private function validateTenantData(Request $request)
    {
        return $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'phone_number' => ['required', 'unique:tenants,phone_number', 'regex:/^(\+251|0)[1-9]\d{8}$/'],
            'email' => 'required|email|unique:tenants,email',
            'room_number' => 'required|string|max:255|unique:tenants,room_number',
            'tenant_type' => 'required|in:buyer,tenant',
            'contract_type' => 'required|string|max:255',
            'contract_status' => 'required|string|max:255',
            'signing_date' => 'required|date',
            'expiring_date' => 'required|date',
            'unit_price' => 'required_if:tenant_type,tenant|numeric',
            'monthly_paid' => 'required_if:tenant_type,tenant|numeric',
            'area_m2' => 'required_if:tenant_type,tenant|numeric',
            'utility_fee' => 'required_if:tenant_type,tenant|numeric',
            'payment_made_until' => 'required_if:tenant_type,tenant|date',
            'start_date' => 'required|date',
            'documents' => 'required|array|min:1',
            'documents.*.file' => 'required|file|mimes:pdf,doc,docx,xlsx,xls,jpg,jpeg,png|max:2048',
            'documents.*.document_type' => 'required|in:payment_receipt,lease_agreement,tenant_info',
        ]);
    }
    
    private function createContract($tenantId, $validatedData)
    {
        try {
            $dueDate = Carbon::parse($validatedData['expiring_date'])->subMonth()->format('Y-m-d');
    
            $contract = Contract::create([
                'tenant_id' => $tenantId,
                'type' => $validatedData['contract_type'],
                'status' => $validatedData['contract_status'],
                'signing_date' => $validatedData['signing_date'],
                'expiring_date' => $validatedData['expiring_date'],
                'due_date' => $dueDate,
            ]);
    
            Log::info('Contract Created', ['contract_id' => $contract->id]);
            return $contract;
    
        } catch (\Exception $e) {
            Log::error('Error creating contract:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function createTenantPayment($tenantId, $validatedData)
    {
        $payment = PaymentForTenant::create([
            'tenant_id' => $tenantId,
            'unit_price' => $validatedData['unit_price'],
            'monthly_paid' => $validatedData['monthly_paid'],
            'area_m2' => $validatedData['area_m2'],
            'utility_fee' => $validatedData['utility_fee'],
            'payment_made_until' => $validatedData['payment_made_until'],
            'start_date' => $validatedData['start_date'],
            'due_date' => $validatedData['due_date'],
        ]);
    
        Log::info('Payment Created', ['payment_id' => $payment->id]);
        return $payment;
    }

/**
 * Store the uploaded document file.
 */


/**
 * Detect the document format based on the file extension.
 */
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

private function validateBuyerData(Request $request)
{
    return $request->validate([
        'floor_id' => 'required|exists:floors,id',
        'name' => 'required|string|max:255',
        'gender' => 'required|in:male,female,other',
        'phone_number' => ['required', 'unique:tenants,phone_number', 'regex:/^(\+251|0)[1-9]\d{8}$/'],  // Ethiopian phone number format
        'email' => 'required|email|unique:tenants,email',
        'room_number' => 'required|string|max:255|unique:tenants,room_number',
        'tenant_type' => 'required|in:buyer,tenant',
        'contract_type' => 'required|string|max:255',
        'contract_status' => 'required|string|max:255',
        'signing_date' => 'required|date',
        'expiring_date' => 'nullable|date',  // Make expiring_date nullable
        'unit_price' => 'required_if:type,tenant|numeric',
        //'monthly_paid' => 'required_if:type,tenant|numeric',
       // 'area_m2' => 'required_if:type,tenant|numeric',
        //'utility_fee' => 'required_if:type,tenant|numeric',
        'utility_fee' => 'required_if:type,buyer|numeric',  // Required for buyers
        'property_price' => 'required_if:type,buyer|numeric',
        //'payment_made_until' => 'required_if:type,tenant|date',
        'start_date' => 'required_if:type,buyer|date',
 
        'documents' => 'required|array|min:1', // Ensure that documents is an array
        'documents.*.file' => 'required|file|mimes:pdf,doc,docx,xlsx,xls,jpg,jpeg,png|max:2048', // Validate each file
        // Optional path for each file
        'documents.*.document_type' => 'required|in:payment_receipt,lease_agreement,tenant_info', // Validate each document type
    ]);
}

public function storeBuyer(Request $request)
{   
    DB::beginTransaction();

    try {
        // Step 1: Validate the request data
        $validatedData = $this->validateBuyerData($request);

        // Step 2: Find Floor to get building_id and category_id
        $floor = Floor::findOrFail($validatedData['floor_id']);
        $validatedData['building_id'] = $floor->building_id;
        $validatedData['category_id'] = $floor->category_id;

        // Step 3: If tenant type is buyer, set the status to "active" and make expiring_date nullable
        if ($validatedData['tenant_type'] === 'buyer') {
            $validatedData['status'] = 'active';  // Default status for buyer
            // // Make expiring_date nullable for buyers
        }

        // Step 4: Calculate due date (one month from expiring_date), but only for non-buyers
        if ($validatedData['tenant_type'] !== 'buyer' && isset($validatedData['expiring_date'])) {
            $validatedData['due_date'] = date('Y-m-d', strtotime($validatedData['expiring_date'] . ' +1 month'));
        }

        // Step 5: Create the Tenant record
        $tenant = Tenant::create(array_merge(
            $validatedData,
            ['tenant_number' => uniqid('TEN-')]
        ));

        // Step 6: Create a Contract for the Tenant
        $contract = $this->createBuyerContract($tenant->id, $validatedData);

        // Step 7: Create Payment for Buyer (only if type is buyer)
      
        $paymentForBuyer= null;
        if ($tenant->tenant_type === 'buyer') {
            $paymentForBuyer = $this->createBuyerPayment($tenant->id, $validatedData);
            Log::info('Created Payment for buyer:', ['paymentForbuyer' => $paymentForBuyer]);
        }

        // Step 8: Store Document files and detect format
            // Step 3: Iterate over each file and corresponding document type
            if ($request->has('documents')) {
                foreach ($request->documents as $document) {
                    if (isset($document['file']) && isset($document['document_type'])) {
                        // Assign foreign keys based on document type
                        $contractId = null;
                        $paymentId = null;
    
                        if ($document['document_type'] === 'payment_receipt') {
                            $paymentId =  $paymentForBuyer->id ;
                        }
    
                        if ($document['document_type'] === 'lease_agreement') {
                            $contractId = $contract->id;
                        }
    
                        Log::info('Assigned IDs:', [
                            'contract_id' => $contractId,
                            'payment_for_buyer_id' => $paymentId,
                            'document_type' => $document['document_type']
                        ]);
    
                        // Store the document file
                        $documentPath = $this->storeDocumentFile($document['file'], $tenant->id);
    
                        // Detect the format for each file
                        $documentFormat = $this->detectDocumentFormat($document['file']);
    
                        // Create a new Document record for each file
                        $documentRecord = Document::create([
                            'documentable_id' => $tenant->id,
                            'documentable_type' => Tenant::class,
                            'document_type' => $document['document_type'],
                            'document_format' => $documentFormat,
                            'file_path' => $documentPath,
                            'contract_id' => $contractId,
                            'payment_for_buyer_id' => $paymentId,
                        ]);
    
                        Log::info('Document created successfully:', $documentRecord->toArray());
                    }
                }
            }
    
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Tenant, contract, payment (if buyer), and document created successfully.',
            'data' => $tenant,
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'An unexpected error occurred: ' . $e->getMessage(),
        ], 500);
    }
}

/**
 * Create a Contract for the Tenant.
 */
private function createBuyerContract($tenantId, $validatedData)
{
    try {
        // Calculate the due date (one month before the expiring date)
        $dueDate = Carbon::parse($validatedData['expiring_date'] ?? now())->subMonth()->format('Y-m-d');

        // Create contract
        $contract = Contract::create([
            'tenant_id' => $tenantId,
            'type' => $validatedData['contract_type'],
            'status' => $validatedData['contract_status'],
            'signing_date' => $validatedData['signing_date'],
            'expiring_date' => $validatedData['expiring_date'] ?? null, // Nullable expiring date for buyers
            'due_date' => $dueDate, // Add calculated due date
        ]);

        if (!$contract) {
            throw new \Exception("Contract not created successfully.");
        }

        return $contract;  // Return the created contract

    } catch (\Exception $e) {
        // Handle exception and return an error message
        throw new \Exception("Contract creation error: " . $e->getMessage());
    }
}

/**
 * Create Payment for Buyer.
 */
private function createBuyerPayment($tenantId, $validatedData)
{
    try {
        // Check if property_price and utility_fee are set
        if (!isset($validatedData['property_price']) || !isset($validatedData['utility_fee'])) {
            throw new \Exception("Missing property_price or utility_fee in validated data.");
        }

        // Create payment
        $payment = PaymentForBuyer::create([
            'tenant_id' => $tenantId,
            'property_price' => $validatedData['property_price'],  // Price of the purchased property
            'utility_fee' => $validatedData['utility_fee'],  // Utility fee for the buyer
            'start_date' => $validatedData['start_date'],  // Start date
        ]);

        if (!$payment) {
            throw new \Exception("Payment not created successfully.");
        }

        return $payment;

    } catch (\Exception $e) {
        throw new \Exception("Payment creation error: " . $e->getMessage());
    }
}


    /**
     * Show a specific tenant.
     */
    public function show($id)
    {
        try {
            // Load tenant with related building, category, floor, contract, payment, and document information
            $tenant = Tenant::with([
                'building',
                'category',
                'floor',
                'contracts' => function ($query) {
                    $query->orderBy('created_at', 'desc'); // Order contracts by created_at descending
                },
               
                'documents' => function ($query) {
                    $query->orderBy('created_at', 'desc'); // Order documents by created_at descending
                }   // Assuming a relationship is defined in Tenant model for documents
            ])->findOrFail($id);
              // Conditionally load payments based on tenant type
        if ($tenant->tenant_type === 'tenant') {
            $tenant->load(['paymentsForTenant' => function ($query) {
                $query->orderBy('created_at', 'desc'); // Order payments by created_at descending
            }]);
        } elseif ($tenant->tenant_type === 'buyer') {
            $tenant->load(['paymentsForBuyer' => function ($query) {
                $query->orderBy('created_at', 'desc'); // Order payments by created_at descending
            }]);
        }
    
            return response()->json([
                'success' => true,
                'data' => $tenant
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update a specific tenant.
     */
    public function update(Request $request, $id)
    {
        // Custom validation rules
        $validator = Validator::make($request->all(), [
            'building_id' => 'exists:buildings,id',
            'category_id' => 'exists:categories,id',
            'floor_id' => 'exists:floors,id',
            'name' => 'string|max:255',
            'gender' => 'in:male,female,other',
            'phone_number' => ['unique:tenants,phone_number,' . $id, 'regex:/^(\+251|0)[1-9]\d{8}$/'],
            'email' => 'email|unique:tenants,email,' . $id,
            'room_number' => 'string|max:255|unique:tenants,room_number,' . $id,
            'tenant_type' => 'in:buyer,tenant',
        ]);
    
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        try {
            // Retrieve the tenant
            $tenant = Tenant::findOrFail($id);
    
            // Check and retrieve building_id and category_id from floor_id
            if ($request->filled('floor_id')) {
                $floor = Floor::findOrFail($request->floor_id);
                $request->merge([
                    'building_id' => $floor->building_id,
                    'category_id' => $floor->category_id,
                ]);
            }
    
            // Update tenant data
            $tenant->update($request->all());
    
            return response()->json([
                'success' => true,
                'data' => $tenant,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
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


public function deactivateTenant($id)
{
    try {
        // Find the tenant by ID
        $tenant = Tenant::findOrFail($id);

        // Update tenant's status to inactive
        $tenant->status = 'inactive';
        $tenant->save();

        // Return a success response
        return response()->json([
            'success' => true,
            'message' => 'Tenant status updated to inactive successfully.',
            'data' => $tenant
        ], 200);
    } catch (\Exception $e) {
        // Handle errors and return an appropriate error response
        return response()->json([
            'success' => false,
            'message' => 'Failed to update tenant status: ' . $e->getMessage()
        ], 500);
    }
}
}