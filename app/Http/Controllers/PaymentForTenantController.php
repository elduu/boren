<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentForTenant;

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
            $validator = $request->validate([
                'tenant_id' => 'required|exists:tenants,id',
                'unit_price' => 'required|numeric|min:0',
                'monthly_paid' => 'required|numeric|min:0',
                'area_m2' => 'required|numeric|min:0',
                'utility_fee' => 'required|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'payment_made_until' => 'nullable|date|before_or_equal:end_date',
            ]);
    
            try {
                $payment = PaymentForTenant::create($request->all());
                return response()->json(['success' => true, 'data' => $payment], 201);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Failed to create payment: ' . $e->getMessage()], 500);
            }
        }
    
        /**
         * Update a payment
         */
        public function update(Request $request, $id)
        {
            $payment = PaymentForTenant::find($id);
            if (!$payment) {
                return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
            }
    
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
                $payment->update($request->all());
                return response()->json(['success' => true, 'data' => $payment], 200);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Failed to update payment: ' . $e->getMessage()], 500);
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
    }
    

