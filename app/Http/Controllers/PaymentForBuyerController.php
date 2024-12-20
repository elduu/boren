<?php



namespace App\Http\Controllers;

use App\Models\PaymentForBuyer;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;
use Carbon\Carbon;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class PaymentForBuyerController extends Controller
{
    /**
     * Display a listing of all payments.
     */
    public function index(Request $request)
    {
        try {
            // Validation for floor_id
            $validator = Validator::make($request->all(), [
                'floor_id' => 'nullable|integer|exists:floors,id',
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
            $paymentsQuery = PaymentForBuyer::with(['tenant.documents']);
    
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
                    'payment_id' => $payment->id ?? null, // Assuming tenant has a floor_id
                    'tenant_name' => $payment->tenant->name ?? 'N/A',
                    'tenant_id' => $payment->tenant->id ?? null,
                    'property_price' => $payment->property_price,
                    'utility_fee' => $payment->utility_fee,
                    'room_number'=>$payment->room_number,
                    'start_date' => $payment->start_date,
                    'documents' => $payment->documents->map(function ($document) {
                        return [
                            'id' => $document->id,
                            'document_type' => $document->document_type,
                            'document_format' => $document->document_format,
                            'file_path' =>url($document->file_path),
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
    /**
     * Display the specified payment by ID.
     */
    public function show($id)
    {
        try {
            $payment = PaymentForBuyer::findOrFail($id);
            return response()->json($payment);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Payment not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching payment', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new payment.
     */

     public function store(Request $request)
     {
         // Validation
         $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|exists:tenants,id',
                'property_price' => 'required|numeric|min:0',
                'utility_fee' => 'required|numeric|min:0',
                'start_date' => 'required|date',
                'room_number' => 'required|string|max:255',

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
            //  $endDate = Carbon::parse($validatedData['end_date']);
            //  $validatedData['due_date'] = $endDate->subWeek()->format('Y-m-d');
     
             // Create payment record
             $payment = PaymentForBuyer::create($validatedData);
     
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
                             'payment_for_buyer_id'=>$payment->id,
                             'doc_name' => $documentName,
                             'doc_size'=>$documentSize,
                         
                         ]);
                     }
                 }
             }
     
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
    /**
     * Update the specified payment.
     */
    public function update(Request $request, $id)
    {
        try {
            // Validate input data
            $validator = Validator::make($request->all(), [
                'tenant_id' => 'sometimes|exists:tenants,id',
                'property_price' => 'sometimes|numeric|min:0',
                'utility_fee' => 'sometimes|numeric|min:0',
                'start_date' => 'sometimes|date',
                'room_number'=> 'nullable',
                // 'documents' => 'array',
                // 'documents.*.file' => 'sometimes|file',
                // 'documents.*.document_type' => 'sometimes|string',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
    
            // Find the payment record
            $payment = PaymentForBuyer::find($id);
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found.',
                ], 404);
            }
    
            // Merge validated data with existing data
            $validatedData = array_merge($payment->toArray(), $validator->validated());
    
            // Update the payment record
            $payment->update([
                'tenant_id' => $validatedData['tenant_id'] ?? $payment->tenant_id,
                'property_price' => $validatedData['property_price'] ?? $payment->property_price,
                'utility_fee' => $validatedData['utility_fee'] ?? $payment->utility_fee,
                'start_date' => $validatedData['start_date'] ?? $payment->start_date,
            ]);
    
            // Handle document updates
            // if ($request->has('documents')) {
            //     foreach ($request->documents as $document) {
            //         if (isset($document['file'])) {
            //             // Store the new file and retrieve the path
            //             $documentPath = $this->storeDocumentFile($document['file'], $validatedData['tenant_id'] ?? $payment->tenant_id);
    
            //             // Detect the format for the new file
            //             $documentFormat = $this->detectDocumentFormat($document['file']);
            //             $documentType = $document['document_type'] ?? 'payment_receipt';
    
            //             // Create a new Document record
            //             Document::create([
            //                 'documentable_id' => $payment->tenant_id,
            //                 'documentable_type' => Tenant::class,
            //                 'document_type' => $documentType,
            //                 'document_format' => $documentFormat,
            //                 'file_path' => $documentPath,
            //                 'payment_for_buyer_id' => $payment->id,
            //             ]);
            //         }
            //     }
            // }
    
            return response()->json(['success' => true, 'data' => $payment->fresh()], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment and documents: ' . $e->getMessage(),
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
            $paymentsQuery = PaymentForBuyer::whereHas('tenant', function ($q) use ($query, $floorId) {
                $q->where('floor_id', $floorId)
                    ->where(function ($subQuery) use ($query) {
                        $subQuery->where('name', 'like', "%{$query}%");
                          
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
                    'payment_id' => $payment->id ?? null, // Assuming tenant has a floor_id
                    'tenant_name' => $payment->tenant->name ?? 'N/A',
                    'tenant_id' => $payment->tenant->id ?? null,
                    'property_price' => $payment->property_price,
                    'utility_fee' => $payment->utility_fee,
                    'start_date' => $payment->start_date,
                    'room_number'=>$payment->room_number,
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
     * Soft delete the specified payment.
     */
    public function destroy($id)
    {
        try {
            $payment = PaymentForBuyer::findOrFail($id);
            $payment->delete();
            return response()->json(['message' => 'Payment deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Payment not found'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error deleting payment', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Restore a soft-deleted payment.
     */
    public function restore($id)
    {
        try {
            $payment = PaymentForBuyer::withTrashed()->findOrFail($id);

            if ($payment->trashed()) {
                $payment->restore();
                return response()->json(['message' => 'Payment restored successfully']);
            }

            return response()->json(['message' => 'Payment is not deleted'], 400);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Payment not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error restoring payment', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Renew the payment by extending the start date.
     */
    public function renew(Request $request, $id)
    {
        try {
            $payment = PaymentForBuyer::findOrFail($id);

            $request->validate([
                'new_start_date' => 'required|date|after:today',
            ]);

            $payment->start_date = $request->new_start_date;
            $payment->save();

            return response()->json(['message' => 'Payment renewed successfully', 'payment' => $payment]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Payment not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error renewing payment', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Search payments by tenant ID.
     */
    public function searchByTenantId($tenantId)
    {
        try {
            $payments = PaymentForBuyer::where('tenant_id', $tenantId)->get();

            if ($payments->isEmpty()) {
                return response()->json(['message' => 'No payments found for this tenant'], 200);
            }

            return response()->json($payments);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching payments', 'error' => $e->getMessage()], 500);
        }
    }

    public function listDeletedPayments()
{
    // Retrieve only deleted payments
    $deletedPayments = PaymentForBuyer::onlyTrashed()->get();

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
