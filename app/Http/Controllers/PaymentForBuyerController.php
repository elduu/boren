<?php



namespace App\Http\Controllers;

use App\Models\PaymentForBuyer;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;
use Carbon\Carbon;

use App\Models\Document;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class PaymentForBuyerController extends Controller
{
    /**
     * Display a listing of all payments.
     */
    public function index(Request $request)
    {   try {
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
    $paymentsQuery = PaymentForBuyer::with('tenant', 'documents');

    if ($floorId) {
        // Apply filter if floor_id is provided
        $paymentsQuery->whereHas('tenant', function ($query) use ($floorId) {
            $query->where('floor_id', $floorId);
        });
    }

    $payments = $paymentsQuery->get();

    // Map through each payment to include tenant details and documents
    $data = $payments->map(function ($payment) {
        return [
            'payment' => $payment,
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

                         // Create a new Document record
                         Document::create([
                             'documentable_id' => $validatedData['tenant_id'],
                             'documentable_type' => Tenant::class,
                             'document_type' => $documentType,
                             'document_format' => $documentFormat,
                             'file_path' => $documentPath,
                             'payment_for_tenant_id'=>$payment->id,
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



    /**
     * Update the specified payment.
     */
    public function update(Request $request, $id)
    {
        try {
            $payment = PaymentForBuyer::findOrFail($id);

            $request->validate([
                'property_price' => 'sometimes|required|numeric|min:0',
                'utility_fee' => 'sometimes|required|numeric|min:0',
                'start_date' => 'sometimes|required|date',
            ]);

            $payment->update($request->all());
            return response()->json(['message' => 'Payment updated successfully', 'payment' => $payment]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Payment not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error updating payment', 'error' => $e->getMessage()], 500);
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
            return response()->json(['message' => 'Payment not found'], 404);
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
                return response()->json(['message' => 'No payments found for this tenant'], 404);
            }

            return response()->json($payments);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching payments', 'error' => $e->getMessage()], 500);
        }
    }
}
