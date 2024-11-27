<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contract;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use App\Models\Document;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
class ContractController extends Controller
{
    public function show($id)
    {
        try {
            $contract = Contract::findOrFail($id);
            return response()->json(['success' => true, 'data' => $contract], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Contract not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to retrieve contract.'], 500);
        }
    }

    /**
     * List all contracts.
     */
    public function index(Request $request)
    {
        try {
            // Validate floor_id if provided
            $validator = Validator::make($request->all(), [
                'floor_id' => 'required|integer|exists:floors,id',
            ], [
                'floor_id.integer' => 'The floor ID must be an integer.',
                'floor_id.exists' => 'The provided floor ID does not exist.',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
    
            // Fetch floor_id from request
            $floorId = $request->input('floor_id');
    
            // Query contracts with optional filtering by floor_id
            $contractsQuery = Contract::with(['tenant:id,name,floor_id', 'documents']);
    
            if ($floorId) {
                // Filter by floor_id through the tenant relationship
                $contractsQuery->whereHas('tenant', function ($query) use ($floorId) {
                    $query->where('floor_id', $floorId);
                });
            }
    
            $contracts = $contractsQuery->orderBy('created_at', 'desc')->get();
    
            if ($contracts->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No contracts found.'], 404);
            }
    
            // Format the response to include tenant names and documents
            $data = $contracts->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'tenant_id' => $contract->tenant->id,
                    'tenant_name' => $contract->tenant->name ?? null, 
                    // Include tenant name
                    'type' => $contract->type,
                    'status' => $contract->status,
                    'signing_date' => $contract->signing_date,
                    'expiring_date' => $contract->expiring_date,
                    'due_date' => $contract->due_date,
                    'created_at' => $contract->created_at,
                    'updated_at' => $contract->updated_at,
                    'documents' => $contract->documents->map(function ($document) { // Include related documents
                        return [
                            'id' => $document->id,
                            'document_type' => $document->document_type,
                            'document_format' => $document->document_format,
                            'file_path' => $document->file_path,
                            'created_at' => $document->created_at,
                            'updated_at' => $document->updated_at,
                        ];
                    }),
                ];
            });
    
            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve contracts: ' . $e->getMessage(),
            ], 500);
        }
    }
    private function storeDocumentFile(UploadedFile $file, $tenantId)
    {
        // Define the directory path where documents will be stored
        $directory = "documents/tenants/{$tenantId}";
    
        // Store the file and get the path
        $path = $file->store($directory, 'public');
    
        return Storage::url($path);
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
    public function store(Request $request)
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
    
        // Validate the incoming request
        $validatedData = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'type' => 'required|in:rental,purchased',
            'status' => 'required|in:active,expired',
            'signing_date' => 'required|date',
            'expiring_date' => 'required|date|after:signing_date',
            'documents' => 'array',
            'documents.*.file' => 'required|file',
            'documents.*.document_type' => 'sometimes|string',
        ], $messages);
    
        try {
            // Calculate due date as one month before expiring_date
            $dueDate = Carbon::parse($validatedData['expiring_date'])->subMonth()->format('Y-m-d');
    
            // Create the contract
            $contract = Contract::create(array_merge(
                $validatedData,
                ['due_date' => $dueDate]
            ));
    
            // Check if documents are provided
            if ($request->has('documents')) {
                foreach ($request->documents as $document) {
                    if (isset($document['file'])) {
                        // Store the file and retrieve the path
                        $documentPath = $this->storeDocumentFile($document['file'], $validatedData['tenant_id']);
    
                        // Detect the format for each file
                        $documentFormat = $this->detectDocumentFormat($document['file']);
                        $documentType = $document['document_type'] ?? 'lease_agreement';
    
                        // Create a new Document record
                        Document::create([
                            'documentable_id' => $validatedData['tenant_id'],
                            'documentable_type' => Tenant::class,
                            'document_type' => $documentType,
                            'document_format' => $documentFormat,
                            'file_path' => $documentPath,
                            'contract_id' => $contract->id,
                        ]);
                    }
                }
            }
    
            return response()->json(['success' => true, 'data' => $contract], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create contract and documents: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Update an existing contract.
     */
    public function update(Request $request, $id)
    {
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
        ];

        // Validate the incoming request
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'type' => 'nullable|in:rental,purchased',
            //'status' => 'required|in:active,expired',
            'signing_date' => 'required|date',
            'expiring_date' => 'required|date|after:signing_date',
        ], $messages);

        try {
            $contract = Contract::findOrFail($id);
            $dueDate = Carbon::parse($request->expiring_date)->subMonth()->format('Y-m-d');

            $contract->update(array_merge(
                $request->all(),
                ['due_date' => $dueDate]
            ));

            return response()->json(['success' => true, 'data' => $contract], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Contract not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Soft delete a contract.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            // Find the contract by ID
            $contract = Contract::findOrFail($id);

            // Soft delete the contract
            $contract->delete();

            return response()->json(['success' => true, 'message' => 'Contract deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Renew the contract by updating the start and end date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function renew(Request $request, $id)
    {
        $messages = [
            'start_date.required' => 'The start date is required for renewal.',
            'start_date.date' => 'The start date must be a valid date.',
            'end_date.required' => 'The end date is required for renewal.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after' => 'The end date must be after the start date.',
        ];

        // Validate the incoming request for the renewal
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ], $messages);

        try {
            $contract = Contract::findOrFail($id);

            $contract->update([
                'status' => 'active',
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'expiring_date' => $request->end_date, 
                'due_date' => Carbon::parse($request->end_date)->subMonth()->format('Y-m-d'),
            ]);

            return response()->json(['success' => true, 'data' => $contract], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Contract not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    // Other methods like show, index, and getTenantContracts...


    /**
     * Get all contracts for a specific tenant.
     *
     * @param  int  $tenantId
     * @return \Illuminate\Http\Response
     */
    public function getTenantContracts($tenantId)
    {
        try {
            // Get contracts for the given tenant
            $tenant = Tenant::findOrFail($tenantId);

            $contracts = $tenant->contracts()->orderBy('created_at', 'desc')->get();;

            return response()->json(['success' => true, 'data' => $contracts], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    public function restore($id)
{
    try {
        // Find the deleted contract using withTrashed
        $contract = Contract::withTrashed()->findOrFail($id);

        // Restore the deleted contract
        $contract->restore();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Contract restored successfully.',
            'data' => $contract
        ], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // Handle case where contract does not exist or is not deleted
        return response()->json([
            'success' => false,
            'message' => 'Contract not found or is not deleted.'
        ], 404);
    } catch (\Exception $e) {
        // Handle general exceptions
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while restoring the contract: ' . $e->getMessage()
        ], 500);
    }
}

public function filterByType(Request $request)
{
    try {
        // Validate request data
        $validated = $request->validate([
            'type' => 'required|string|in:rental,purchased' // Add your contract types
        ], [
            'type.required' => 'The contract type is required.',
            'type.string' => 'The contract type must be a valid string.',
            'type.in' => 'The contract type must be one of the following: lease, purchase, service, rent.'
        ]);

        // Fetch contracts of the specified type
        $contracts = Contract::where('type', $validated['type'])->get();

        // Return results
        if ($contracts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No contracts found for the specified type.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $contracts
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Return validation errors
        return response()->json([
            'success' => false,
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        // Handle general exceptions
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while filtering contracts: ' . $e->getMessage()
        ], 500);
    }
}
}

