<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentForTenant;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Document;
use App\Models\Tenant;
use App\Models\AuditLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PaymentForTenantController extends Controller
{  /**
         * List all payments
         */
        public function index(Request $request)
        {   try {
        // Validation for floor_id
        $validator = Validator::make($request->all(), [
            'floor_id' => 'required|integer|exists:floors,id',
        ], [
            'floor_id.integer' => 'The floor ID must be an integer.',
            'floor_id.exists' => 'The provided floor ID does not exist.',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Fetch floor_id from the request
        $floorId = $request->input('floor_id');

        // Fetch payments filtered by floor_id if provided
        $paymentsQuery = PaymentForTenant::with(['tenant:id,name,floor_id', 'documents']);
    
        if ($floorId) {
            // Apply filter if floor_id is provided
            $paymentsQuery->whereHas('tenant', function ($query) use ($floorId) {
                $query->where('floor_id', $floorId);
            });
        }

        $payments = $paymentsQuery->orderBy('created_at', 'desc')->get();

        // Map through each payment to include tenant details and documents
        $data = $payments->map(function ($payment) {
            return [
                'payment_id' => $payment->id,
                'tenant_id' => $payment->tenant->id,
                'tenant_name'=>$payment->tenant->name,
                'room_number'=>$payment->room_number,
                'unit_price' => $payment->unit_price,
                'monthly_paid' => $payment->monthly_paid,
                'area_m2' => $payment->area_m2,
                'payment_status'=>$payment->payment_status,
            'payment_number'=>$payment->payment_number,
                'activate_status'=>$payment->activate_status,
                'utility_fee' => $payment->utility_fee,
                'start_date' => $payment->start_date,
                'payment_made_until' => $payment->payment_made_until,
                'documents' => $payment->documents->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'document_type' => $document->document_type,
                        'document_format' => $document->document_format,
                        'file_path' =>  url($document->file_path),
                        'created_at' => $document->created_at,
                        'updated_at' => $document->updated_at,
                        'doc_name' => $document->doc_name,
                        'doc_size'=>$document->doc_size,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch payments: ' . $e->getMessage(),
        ], 500);
    }
}

public function search(Request $request)
{
    try {
        // Validate inputs
        $validator = Validator::make($request->all(), [
            'query' => 'required|string',
            'floor_id' => 'required|integer|exists:floors,id',
        ], [
            'query.required' => 'The query parameter is required.',
            'floor_id.required' => 'The floor ID is required.',
            'floor_id.integer' => 'The floor ID must be an integer.',
            'floor_id.exists' => 'The provided floor ID does not exist.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = $request->input('query');
        $floorId = $request->input('floor_id');

        // Search payments filtered by tenant name and floor ID
        $paymentsQuery = PaymentForTenant::whereHas('tenant', function ($q) use ($query, $floorId) {
            $q->where('floor_id', $floorId)
                ->where(function ($subQuery) use ($query) {
                    $subQuery->where('name', 'like', "%{$query}%")
                        ->orWhere('room_number', 'like', "%{$query}%")
                        ->orWhere('tenant_number', 'like', "%{$query}%")
                        ->orWhere('phone_number', 'like', "%{$query}%")
                        ->orWhere('payment_number', 'like', "%{$query}%");
                });
        })->with(['tenant:id,name,floor_id', 'documents']) // Include tenant and documents relationships
          ->orderBy('created_at', 'desc');

        $payments = $paymentsQuery->get();

        if ($payments->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No payments found for the given query and floor.',
                'data' => [],
            ], 200);
        }

        // Map through each payment to structure the response
        $data = $payments->map(function ($payment) {
            return [
                'payment_id' => $payment->id,
                'tenant_id' => $payment->tenant->id,
                'tenant_name' => $payment->tenant->name,
                'room_number'=>$payment->room_number,
                'unit_price' => $payment->unit_price,
                'monthly_paid' => $payment->monthly_paid,
                'area_m2' => $payment->area_m2,
                'payment_number'=>$payment->payment_number,
                'payment_status' => $payment->payment_status,
                'activate_status'=>$payment->activate_status,
                'utility_fee' => $payment->utility_fee,
                'start_date' => $payment->start_date,
                'payment_made_until' => $payment->payment_made_until,
                'documents' => $payment->documents->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'document_type' => $document->document_type,
                        'document_format' => $document->document_format,
                        'file_path' => url($document->file_path),
                        'created_at' => $document->created_at,
                        'updated_at' => $document->updated_at,
                        'doc_name' => $document->doc_name,
                        'doc_size' => $document->doc_size,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while searching payments: ' . $e->getMessage(),
        ], 500);
    }
}
        /**
         * Store a new payment
         */
        public function store(Request $request)
        {
            // Validation
            $validator = Validator::make($request->all(), [
                'tenant_id' => 'required|exists:tenants,id',
                'unit_price' => 'required|numeric|min:0',
                'monthly_paid' => 'required|numeric|min:0',
                'area_m2' => 'required|numeric|min:0',
                'utility_fee' => 'required|numeric|min:0',
                'room_number' => 'required|string|max:255',
                'start_date' => 'required|date',
              //  'end_date' => 'required|date|after_or_equal:start_date',
                'payment_made_until' => 'nullable|date|after_or_equal:start_date',
                'documents' => 'array',
                'documents.*.file' => 'required|file',
                'documents.*.document_type' => 'sometimes|string'
            ]);
        
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
        
            try {
                $validatedData = $validator->validated();
        
                // Calculate due_date as one week before end_date
                $endDate = Carbon::parse($validatedData['payment_made_until']);
                $validatedData['due_date'] = $endDate->subWeek()->format('Y-m-d');
                
                $currentDate = Carbon::now()->format('Y-m-d');
                
             
                // Create payment record
               
                $payment = PaymentForTenant::create(array_merge(
                    $validatedData,
                    [
                'payment_number' => uniqid('PAY-')],
                    
                ));
                // PaymentForTenant::whereDate('payment_made_until', '>', $currentDate)
                // ->update(['payment_status' => 'paid']);
            
                // PaymentForTenant::whereDate('payment_made_until', '<=', $currentDate)
                // ->update(['payment_status' => 'unpaid']);
                
                    // Set the payment status based on 'payment_made_until'
                    $currentDate = Carbon::now()->format('Y-m-d');
                    // if (Carbon::parse($payment->payment_made_until)->gte($currentDate)) {
                    //     $payment->payment_status = 'paid';
                    // } else {
                    //     $payment->payment_status = 'unpaid';
                    // }
        
                    // // Save the payment status after updating
                    // $payment->save();
            
        
                // Check if documents are provided
                if ($request->has('documents')) {
                    foreach ($request->documents as $document) {
                        if (isset($document['file']) ){
                            // Store the file and retrieve the path
                            $documentPath = $this->storeDocumentFile($document['file'], $validatedData['tenant_id']);
      
                            // Detect the format for each file
                            $documentFormat = $this->detectDocumentFormat($document['file']);
                            $documentType = $document['document_type'] ?? 'payment_receipt';
                            
                        $documentName = $document['file']->getClientOriginalName();
                        $documentSize = $document['file']->getSize();                 
                            // Create a new Document record
                            Document::create([
                                'documentable_id' => $validatedData['tenant_id'],
                                'documentable_type' => Tenant::class,
                                'document_type' => $documentType,
                                'document_format' => $documentFormat,
                                'file_path' => $documentPath,
                                'payment_for_tenant_id'=>$payment->id,
                               'doc_name' => $documentName,
                        'doc_size'=>$documentSize,
                        'uploaded_by' => auth()->id(),
                            ]);
                        }
                    }
                }

                AuditLog::create([
                    'auditable_id' => $payment->id,
                    'auditable_type' => PaymentForTenant::class,
                    'user_id' => auth()->id(),
                    'event' => 'created',
                    'document_for' => 'payment for tenant',
                ]);
        
                return response()->json(['success' => true, 'data' => $payment], 201);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create payment and documents: ' . $e->getMessage()
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
        
        

public function update(Request $request, $id)
{
    // Find the payment record
    $payment = PaymentForTenant::find($id);
    if (!$payment) {
        return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
    }

    // Validate the input fields
    $validator = $request->validate([
        'unit_price' => 'nullable|numeric|min:0',
        'monthly_paid' => 'nullable|numeric|min:0',
        'area_m2' => 'nullable|numeric|min:0',
        'utility_fee' => 'nullable|numeric|min:0',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'room_number'=>'nullable',
        'payment_made_until' => 'nullable|date|after_or_equal:start_date',
        'activate_status'=>'nullable|in:active,inactive'
    
    ]);

    try {
        // Extract only the fields present in the request
        $updatedData = $request->only([
            'unit_price',
            'monthly_paid',
            'area_m2',
            'utility_fee',
            'start_date',
            'end_date',
            'payment_made_until',
            'room_number',
            'activate_status',
        ]);
        $currentDate = Carbon::now()->format('Y-m-d');

// Update all payments where the current date is not equal to payment_made_until
//  PaymentForTenant::whereDate('payment_made_until', '>', $currentDate)
//     ->update(['status' => 'paid']);

//     PaymentForTenant::whereDate('payment_made_until', '<=', $currentDate)
//     ->update(['status' => 'unpaid']);

        // If `end_date` is provided, calculate `due_date` based on it
        if ($request->filled('paymment_made_until')) {
            $endDate = Carbon::parse($request->input('end_date'));
            $updatedData['due_date'] = $endDate->subWeek()->format('Y-m-d');
        }
        else {
            // Keep the previous `due_date`
            $updatedData['due_date'] = $payment->due_date;
        }

        // Update only the provided fields in the database
        $payment->fill($updatedData);
        $payment->save();

        AuditLog::create([
            'auditable_id' => $payment->id,
            'auditable_type' => PaymentForTenant::class,
            'user_id' => auth()->id(),
            'event' => 'updated',
            'document_for' => 'payment for tenant',
        ]);

        return response()->json(['success' => true, 'data' => $payment], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update payment: ' . $e->getMessage()
        ], 500);
    }
}
    
        /**
         * Delete a payment (soft delete)
         */
        public function destroy($id)
        {
            $payment = PaymentForTenant::find($id);
            if (!$payment) {
                return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
            }
    
            try {
                $payment->delete();

                AuditLog::create([
                    'auditable_id' => $payment->id,
                    'auditable_type' => PaymentForTenant::class,
                    'user_id' => auth()->id(),
                    'event' => 'deleted',
                    'document_for' => 'payment for tenant',
                ]);
                return response()->json(['success' => true, 'message' => 'Payment deleted successfully'], 200);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Failed to delete payment: ' . $e->getMessage()], 500);
            }
        }
    
        /**
         * Restore a soft-deleted payment
         */
        public function restore($id)
        {
            $payment = PaymentForTenant::withTrashed()->find($id);
            if (!$payment) {
                return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
            }
    
            try {
                $payment->restore();

                AuditLog::create([
                    'auditable_id' => $payment->id,
                    'auditable_type' => PaymentForTenant::class,
                    'user_id' => auth()->id(),
                    'event' => 'restored',
                    'document_for' => 'payment for tenant',
                ]);
                return response()->json(['success' => true, 'message' => 'Payment restored successfully'], 200);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Failed to restore payment: ' . $e->getMessage()], 500);
            }
        }


        public function searchByTenantId($tenantId)
    {
        try {
            $payments = PaymentForTenant::where('tenant_id', $tenantId)->get();
        
            if ($payments->isEmpty()) {
                return response()->json(['message' => 'No payments found for this tenant'], 404);
            }
        
            $tenant = Tenant::findOrFail($tenantId); // Fetch tenant details based on the tenant ID
            $documents = Document::whereIn('payment_for_tenant_id', $payments->pluck('id')) // Filter documents by payment IDs
                ->get();
        
            return response()->json([
                'data' => [
                    'payment' => $payments,
                    'name' => $tenant->name,
                    'documents' => $documents, // All documents related to the tenant's payments
                ]
            ], 200);
        
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching payments', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
{
    try {
        // Find the payment by ID
        $payment = PaymentForTenant::findOrFail($id);
        $tenant = Tenant::findOrFail($payment->tenant_id);

        // Fetch the tenant along with documents specific to this payment
        $documents = Document::where('payment_for_tenant_id', $id) // Filter by this specific payment ID
            ->get();

        // Combine the payment details with the filtered documents in the response
        return response()->json([
            'success' => true,
            'data' => [
                'payment' => $payment,
                'name'=>$tenant->name,
                'documents' => $documents, // Only documents for this payment
            ]
        ], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['success' => false, 'message' => 'Payment or Tenant not found'], 404);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}

// public function search(Request $request)
// {
//     try {
//         $query = $request->input('query');

//         if (!$query) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Query parameter is required.',
//             ], 422);
//         }

//         // Search payments based on tenant's name
//         $payments = PaymentForTenant::whereHas('tenant', function ($q) use ($query) {
//             $q->where('name', 'like', "%{$query}%");
//         })
//         ->with('tenant') // Load the related tenant
//         ->orderBy('created_at', 'desc') // Order by the most recent payments
//         ->get();

//         if ($payments->isEmpty()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'No payments found for the given tenant name.',
//             ], 200);
//         }

//         // Format the response
//         $data = $payments->map(function ($payment) {
//             return [
//                 'id' => $payment->id,
//                 'tenant_id' => $payment->tenant->id,
//                 'tenant_name' => $payment->tenant->name,
//                 'amount' => $payment->monthly_paid,
//                 'payment_date' => $payment->payment_made_until,
//                 'status' => $payment->payment_status,
//                 'created_at' => $payment->created_at,
//                 'updated_at' => $payment->updated_at,
//             ];
//         });

//         return response()->json(['success' => true, 'data' => $data], 200);
//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'An error occurred: ' . $e->getMessage(),
//         ], 500);
//     }
// }
public function listDeletedPayments()
{
    // Retrieve only deleted payments
    $deletedPayments = PaymentForTenant::onlyTrashed()->get();

    // Check if there are any deleted payments
    if ($deletedPayments->isEmpty()) {
        return response()->json(['message' => 'No deleted payments found'], 200);
    }

    // Return the deleted payments
    return response()->json([
        'status' => 'success',
        'deleted_payments' => $deletedPayments
    ], 200);
}
    }
    

