<?php



namespace App\Http\Controllers;

use App\Models\PaymentForBuyer;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;
use Carbon\Carbon;

class PaymentForBuyerController extends Controller
{
    /**
     * Display a listing of all payments.
     */
    public function index()
    {
        try {
            $payments = PaymentForBuyer::all();
            return response()->json($payments);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching payments', 'error' => $e->getMessage()], 500);
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
        try {
            $request->validate([
                'tenant_id' => 'required|exists:tenants,id',
                'property_price' => 'required|numeric|min:0',
                'utility_fee' => 'required|numeric|min:0',
                'start_date' => 'required|date',
            ]);

            $payment = PaymentForBuyer::create($request->all());
            return response()->json(['message' => 'Payment created successfully', 'payment' => $payment], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error creating payment', 'error' => $e->getMessage()], 500);
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
