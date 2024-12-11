<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Building;
use App\Models\Category;
use App\Models\Utility;
use App\Models\Tenant;
use App\Models\Floor;

class UtilityController extends Controller
{public function index()
    {
        $utilities = Utility::with(['tenant', 'category', 'floor', 'tenantType'])->get();
    
        $data = $utilities->map(function ($utility) {
            return [
                'id' => $utility->id,
                'tenant_id' => $utility->tenant_id,
                'tenant_name' => $utility->tenant->name ?? 'Unknown Tenant',
                'category_name' => $utility->category->name ?? 'Unknown Category',
                'floor_name' => $utility->floor->name ?? 'Unknown Floor',
                'tenant_type' => $utility->tenantType->name ?? 'Unknown Type',
                'utility_payment' => $utility->utility_payment,
                'utility_fee' => $utility->utility_fee,
                'payment_status' => $utility->payment_status,
                'start_date' => $utility->start_date,
                'end_date' => $utility->end_date,
                'due_date' => $utility->due_date,
                'reason' => $utility->reason,
                'type' => $utility->type,
                'created_at' => $utility->created_at->format('Y-m-d H:i:s'),
            ];
        });
    
        return response()->json($data);
    }
    
    public function store(Request $request)
    {
        try {
            // Validate the request with custom error messages
            $request->validate([
                'tenant_id' => 'required|exists:tenants,id',
                'utility_payment' => 'required|numeric|min:0',
                'utility_fee' => 'required|numeric|min:0',
                'payment_status' => 'required|string|in:pending,paid,overdue',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'due_date' => 'nullable|date|after_or_equal:start_date',
                'reason' => 'nullable|string',
                'utility_type' => 'required|in:electric_bill,water,Generator,total',
            ], [
                'tenant_id.required' => 'The tenant ID is required.',
                'tenant_id.exists' => 'The selected tenant ID does not exist.',
                'utility_payment.required' => 'Utility payment is required.',
                'utility_payment.numeric' => 'Utility payment must be a number.',
                'utility_payment.min' => 'Utility payment must be at least 0.',
                'utility_fee.required' => 'Utility fee is required.',
                'utility_fee.numeric' => 'Utility fee must be a number.',
                'utility_fee.min' => 'Utility fee must be at least 0.',
                'payment_status.required' => 'Payment status is required.',
                'payment_status.in' => 'Payment status must be one of: pending, paid, or overdue.',
                'start_date.date' => 'Start date must be a valid date.',
                'end_date.date' => 'End date must be a valid date.',
                'end_date.after_or_equal' => 'End date must be after or equal to the start date.',
                'due_date.date' => 'Due date must be a valid date.',
                'due_date.after_or_equal' => 'Due date must be after or equal to the start date.',
                'reason.string' => 'Reason must be a valid string.',
                'utility_type.required' => 'Utility type is required.',
                'utility_type.in' => 'Utility type must be one of: electric_bill, water, Generator, total.',
            ]);
    
            // Create the utility record
            $utility = Utility::create($request->all());
    
            return response()->json([
                'message' => 'Utility record added successfully.',
                'data' => $utility,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
}