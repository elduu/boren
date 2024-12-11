<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Building;
use App\Models\Category;
use App\Models\Utility;
use App\Models\Tenant;
use App\Models\Floor;

class UtilityController extends Controller
{
    public function store(Request $request)
    {
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
        ]);
    
        $utility = Utility::create($request->all());
    
        return response()->json([
            'message' => 'Utility record added successfully.',
            'data' => $utility,
        ]);
    }
}
