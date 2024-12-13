<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Building;
use App\Models\Category;
use App\Models\Utility;
use App\Models\Tenant;
use App\Models\Floor;
use Carbon\Carbon;

class UtilityController extends Controller
{public function index(Request $request)
    {
        try {
            // Validate that building_id is provided and is a valid ID in the buildings table
            $request->validate([
                'building_id' => 'required|exists:buildings,id',
            ], [
                'building_id.required' => 'Building ID is required.',
                'building_id.exists' => 'The selected building ID does not exist.',
            ]);
    
            // Fetch utilities filtered by the provided building_id with related models
           
                // Determine the building IDs to include based on the requested building_id
                $buildingIds = match ($request->building_id) {
                    1=> [1, 3],
                    2=> [2, 4],
                    default => [$request->building_id],
                };
        
                // Fetch utilities filtered by the determined building IDs with related models
                $utilities = Utility::with(['tenant', 'category', 'floor', 'building'])
                    ->whereHas('tenant', function ($query) use ($buildingIds) {
                        $query->whereIn('building_id', $buildingIds);
                    })
                    ->get();
        
                // Check if no utilities were found
                // if ($utilities->isEmpty()) {
                //     return response()->json([
                //         'success' => false,
                //         'message' => 'No utilities found for the specified building(s).',
                //     ], 200);
                // }
    
            // Check if no utilities were found
            // if ($utilities->isEmpty()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'No utilities found for the specified building.',
            //     ], 200); // You can use 404 if you want to indicate no records found
            // }
    
            // Map the utilities into a structured response
            $data = $utilities->map(function ($utility) {
                return [
                    'id' => $utility->id,
                    'tenant_id' => $utility->tenant_id,
                    'tenant_name' => $utility->tenant->name ?? 'Unknown Tenant',
                    'category_name' => $utility->tenant->category->name ?? 'Unknown Category',
                    'floor_name' => $utility->tenant->floor->name ?? 'Unknown Floor',
                    'tenant_type' => $utility->tenant->tenant_type ?? 'Unknown Type',
                   // 'utility_payment' => $utility->utility_fee,
                   
                    'other_fee'=>$utility->other_fee,
                    'electric_bill_fee'=>$utility->electric_bill_fee,
                    'generator_bill'=>$utility->generator_bill,
                    'water_bill'=>$utility->water_bill,
                    //'payment_status' => $utility->payment_status,
                    'start_date' => $utility->start_date,
                    'end_date' => $utility->end_date,
                    'due_date' => $utility->due_date,
                    'reason' => $utility->reason,
                    'utililty_type' => $utility->utility_type,
                    'status' => $utility->utility_status,
                    'created_at' => $utility->created_at->format('Y-m-d H:i:s'),
                ];
            });
    
            // Return the response with the formatted data
            return response()->json($data);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return a JSON response with validation error messages
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Return a JSON response with the error message
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch utilities: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            // Validate the request with custom error messages
            $request->validate([
                'tenant_id' => 'required|exists:tenants,id',
              //  'utility_payment' => 'required|numeric|min:0',
              'other_fee'=>'nullable|numeric|min:0',
              'electric_bill_fee'=>'nullable|numeric|min:0',
              
              'generator_bill'=>'nullable|numeric|min:0',
              'water_bill'=>'required|numeric|min:0',
              //  'payment_status' => 'required|string|in:pending,paid,overdue',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'reason' => 'nullable|string',
                'utility_type' => 'nullable|in:electric_bill,water,Generator,other',
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
                'reason.string' => 'Reason must be a valid string.',
                'utility_type.required' => 'Utility type is required.',
                'utility_type.in' => 'Utility type must be one of: electric_bill, water, Generator, or other.',
            ]);
    
            // Calculate the due date as one week before the end date
            $dueDate = $request->end_date ? Carbon::parse($request->end_date)->subWeek() : null;
    
            // Create the utility record with the calculated due_date
            $utility = Utility::create(array_merge($request->all(), ['due_date' => $dueDate]));
    
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

    public function update(Request $request, $id)
{
    try {
        // Validate the request with custom error messages
        $request->validate([
            'tenant_id' => 'nullable|exists:tenants,id',
           
           
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'utility_type' => 'nullable|in:electric_bill,water,Generator,total',
            'other_fee'=>'nullable|numeric|min:0',
            'electric_bill_fee'=>'nullable|numeric|min:0',
            'generator_bill'=>'nullable|numeric|min:0',
            'water_bill'=>'required|numeric|min:0',
        ], [
            'tenant_id.exists' => 'The selected tenant ID does not exist.',
            'utility_payment.numeric' => 'Utility payment must be a number.',
            'utility_payment.min' => 'Utility payment must be at least 0.',
            'utility_fee.numeric' => 'Utility fee must be a number.',
            'utility_fee.min' => 'Utility fee must be at least 0.',
            'payment_status.in' => 'Payment status must be one of: pending, paid, or overdue.',
            'start_date.date' => 'Start date must be a valid date.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after_or_equal' => 'End date must be after or equal to the start date.',
            'reason.string' => 'Reason must be a valid string.',
            'utility_type.in' => 'Utility type must be one of: electric_bill, water, Generator, total.',
        ]);

        // Find the utility record by ID
        $utility = Utility::findOrFail($id);
        if (!$utility) {
            return response()->json([
                'message' => 'Utility record not found.',
            ], 200);
        }

        // Check if the end date is provided to calculate the due date
        if ($request->has('end_date')) {
            $dueDate = Carbon::parse($request->end_date)->subWeek(); // Calculate due date (one week before end_date)
            $request->merge(['due_date' => $dueDate]); // Merge the due_date into the request
        }

        // Update only the provided values (leave others intact)
        $utility->update($request->only([
            'tenant_id',
         
            'other_fee',
        'electric_bill_fee',
        'generator_bill',
        'water_bill',
            'payment_status',
            'start_date',
            'end_date',
            'due_date',
            'reason',
            'utility_type',
        ]));

        return response()->json([
            'message' => 'Utility record updated successfully.',
            'data' => $utility,
        ], 200);

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
public function listDeleted()
{
    try {
        // Fetch all soft deleted utilities
        $deletedUtilities = Utility::onlyTrashed()->get();

        if ($deletedUtilities->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No deleted utilities found.'], 200);
        }

        return response()->json([
            'success' => true,
            'data' => $deletedUtilities
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch deleted utilities: ' . $e->getMessage(),
        ], 500);
    }
}
public function restore($id)
{
    try {
        // Restore the soft deleted utility record
        $utility = Utility::withTrashed()->findOrFail($id);
        $utility->restore();

        return response()->json([
            'success' => true,
            'message' => 'Utility restored successfully',
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to restore utility: ' . $e->getMessage(),
        ], 500);
    }
}
public function delete($id)
{
    try {
        // Find the utility
        $utility = Utility::findOrFail($id);

        // Soft delete the utility record
        $utility->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utility deleted successfully',
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete utility: ' . $e->getMessage(),
        ], 500);
    }
}
    
}