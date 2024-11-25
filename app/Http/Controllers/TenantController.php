<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
{   DB::beginTransaction();

    try {
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

        // Step 5: Create a Contract for the Tenant
        $contract = $this->createContract($tenant->id, $validatedData);

        // Step 6: Create Payment for Tenant (only if type is tenant)
        if ($tenant->type === 'tenant') {
            $this->createTenantPayment($tenant->id, $validatedData);
        }

        // Step 7: Store Document file and detect format
       
    // Step 3: Iterate over each file and corresponding document type
    foreach ($request->file('file') as $index => $file) {
        // Store each file and retrieve the path
        $documentPath = $this->storeDocumentFile($file, $tenant->id);

        // Detect the format for each file
        $documentFormat = $this->detectDocumentFormat($file);

        // Create a new Document record for each file
        Document::create([
            'documentable_id' => $tenant->id,
            'documentable_type' => Tenant::class,
            'document_type' => $request->input('document_type')[$index], // Get the corresponding document type
            'document_format' => $documentFormat,
            'date' => $validatedData['date'],
            'file_path' => $documentPath,
        ]);
    }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Tenant, contract, payment, and document created successfully.',
            'data' => $tenant,
        ], 201);

      
    
        // Return response with success message
      

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'An unexpected error occurred: ' . $e->getMessage(),
        ], 500);
    }
}

private function storeDocumentFile(UploadedFile $file, $tenantId)
{
    // Define the directory path where documents will be stored
    $directory = "documents/tenants/{$tenantId}";

    // Store the file and get the path
    $path = $file->store($directory, 'public');

    return $path;
}
/**
 * Validate the Tenant data.
 */
private function validateTenantData(Request $request)
{
    return $request->validate([
        'floor_id' => 'required|exists:floors,id',
        'name' => 'required|string|max:255',
        'gender' => 'required|in:male,female,other',
        'phone_number' => ['required', 'unique:tenants,phone_number', 'regex:/^(\+251|0)[1-9]\d{8}$/'],  // Ethiopian phone number format
        'email' => 'required|email|unique:tenants,email',
        'room_number' => 'required|string|max:255|unique:tenants,room_number',
        'type' => 'required|in:buyer,tenant',
        'contract_type' => 'required|string|max:255',
        'contract_status' => 'required|string|max:255',
        'signing_date' => 'required|date',
        'expiring_date' => 'required|date',
        'unit_price' => 'required_if:type,tenant|numeric',
        'monthly_paid' => 'required_if:type,tenant|numeric',
        'area_m2' => 'required_if:type,tenant|numeric',
        'utility_fee' => 'required_if:type,tenant|numeric',
        'payment_made_until' => 'required_if:type,tenant|date',
        'start_date' => 'required|date',
       // 'document_type' => 'required|in:payment_receipt,lease_agreement,tenant_info',
    //'uploaded_by' => 'required|string|max:255',
        'date' => 'required|date',
       // 'file' => 'required|file|mimes:pdf,doc,docx,xlsx,xls,jpg,jpeg,png|max:2048', // Document file validation
    'file' => 'required|array|min:1', // Ensure that files are an array
        'file.*' => 'file|mimes:pdf,doc,docx,xlsx,xls,jpg,jpeg,png|max:2048', // Validate each file
        'document_type' => 'required|array|min:1', // Ensure document_type is also an array
        'document_type.*' => 'required|in:payment_receipt,lease_agreement,tenant_info', // Validate each document type
    
    
    ]);
}

/**
 * Create a Contract for the Tenant.
 */
private function createContract($tenantId, $validatedData)
{
    try {
        // Calculate the due date (one month before the expiring date)
        $dueDate = Carbon::parse($validatedData['expiring_date'])->subMonth()->format('Y-m-d');

        // Create contract
        $contract = Contract::create([
            'tenant_id' => $tenantId,
            'type' => $validatedData['contract_type'],
            'status' => $validatedData['contract_status'],
            'signing_date' => $validatedData['signing_date'],
            'expiring_date' => $validatedData['expiring_date'],
            'due_date' => $dueDate, // Add calculated due date
        ]);

        return $contract;  // Return the created contract

    } catch (\Exception $e) {
        // Handle exception and return an error message
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}
/**
 * Create Payment for Tenant.
 */
private function createTenantPayment($tenantId, $validatedData)
{
    return PaymentForTenant::create([
        'tenant_id' => $tenantId,
        'unit_price' => $validatedData['unit_price'],
        'monthly_paid' => $validatedData['monthly_paid'],
        'area_m2' => $validatedData['area_m2'],
        'utility_fee' => $validatedData['utility_fee'],
        'payment_made_until' => $validatedData['payment_made_until'],
        'start_date' => $validatedData['start_date'],
        'due_date' => $validatedData['due_date'],
    ]);
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
        'type' => 'required|in:buyer,tenant',
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
        'date' => 'required|date',
        'file' => 'required|array|min:1', // Ensure that files are an array
        'file.*' => 'file|mimes:pdf,doc,docx,xlsx,xls,jpg,jpeg,png|max:2048', // Validate each file
        'document_type' => 'required|array|min:1', // Ensure document_type is also an array
        'document_type.*' => 'required|in:payment_receipt,lease_agreement,tenant_info', // Validate each document type
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
        if ($validatedData['type'] === 'buyer') {
            $validatedData['status'] = 'active';  // Default status for buyer
            // // Make expiring_date nullable for buyers
        }

        // Step 4: Calculate due date (one month from expiring_date), but only for non-buyers
        if ($validatedData['type'] !== 'buyer' && isset($validatedData['expiring_date'])) {
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
        if ($tenant->type === 'buyer') {
            $this->createBuyerPayment($tenant->id, $validatedData);
        }

        // Step 8: Store Document files and detect format
        foreach ($request->file('file') as $index => $file) {
            $documentPath = $this->storeDocumentFile($file, $tenant->id);
            $documentFormat = $this->detectDocumentFormat($file);

            // Create a new Document record for each file
            Document::create([
                'documentable_id' => $tenant->id,
                'documentable_type' => Tenant::class,
                'document_type' => $request->input('document_type')[$index], // Get the corresponding document type
                'document_format' => $documentFormat,
                'date' => $validatedData['date'],
                'file_path' => $documentPath,
            ]);
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
                'contracts',     // Assuming a relationship is defined in Tenant model for contracts
                'payments',      // Assuming a relationship is defined in Tenant model for payments
                'documents'      // Assuming a relationship is defined in Tenant model for documents
            ])->findOrFail($id);
    
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
