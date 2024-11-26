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
use Illuminate\Http\UploadedFile;

class PaymentForTenantController extends Controller
{  /**
         * List all payments
         */
        public function index()
        {
            try {
                $payments = PaymentForTenant::with('tenant')->get();
                return response()->json(['success' => true, 'data' => $payments], 200);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Failed to fetch payments: ' . $e->getMessage()], 500);
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
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'payment_made_until' => 'nullable|date|before_or_equal:end_date',
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
                $endDate = Carbon::parse($validatedData['end_date']);
                $validatedData['due_date'] = $endDate->subWeek()->format('Y-m-d');
        
                // Create payment record
                $payment = PaymentForTenant::create($validatedData);
        
                // Check if documents are provided
                if ($request->has('documents')) {
                    foreach ($request->documents as $document) {
                        if (isset($document['file']) ){
                            // Store the file and retrieve the path
                            $documentPath = $this->storeDocumentFile($document['file'], $validatedData['tenant_id']);
        
                            // Detect the format for each file
                            $documentFormat = $this->detectDocumentFormat($document['file']);
                            $documentType = $document['document_type'] ?? 'payment_receipt';

                            // Create a new Document record
                            Document::create([
                                'documentable_id' => $validatedData['tenant_id'],
                                'documentable_type' => Tenant::class,
                                'document_type' => $documentType,
                                'document_format' => $documentFormat,
                                'file_path' => $documentPath,
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
        
            return $path;
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
    $payment = PaymentForTenant::find($id);
    if (!$payment) {
        return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
    }

    // Validation
    $validator = $request->validate([
        'unit_price' => 'nullable|numeric|min:0',
        'monthly_paid' => 'nullable|numeric|min:0',
        'area_m2' => 'nullable|numeric|min:0',
        'utility_fee' => 'nullable|numeric|min:0',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'payment_made_until' => 'nullable|date|before_or_equal:end_date',
    ]);

    try {
        $updatedData = $request->all();

        // Check if end_date is provided and calculate due_date based on it
        if ($request->has('end_date')) {
            $endDate = Carbon::parse($request->input('end_date'));
            $updatedData['due_date'] = $endDate->subWeek()->format('Y-m-d');
        }

        // Update payment record
        $payment->update($updatedData);

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

            return response()->json($payments);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching payments', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
{
    try {
        // Find the payment by ID
        $payment = PaymentForTenant::findOrFail($id);

        // Fetch the tenant along with documents specific to this payment
        $documents = Document::where('documentable_id', $payment->tenant_id)
            ->where('documentable_type', Tenant::class)
            ->where('payment_id', $id) // Filter by this specific payment ID
            ->get();

        // Combine the payment details with the filtered documents in the response
        return response()->json([
            'success' => true,
            'data' => [
                'payment' => $payment,
                'documents' => $documents, // Only documents for this payment
            ]
        ], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['success' => false, 'message' => 'Payment or Tenant not found'], 404);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}
    }
    

