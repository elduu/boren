<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PaymentForBuyer;
use App\Models\PaymentForTenant;
use App\Models\Utility;
use App\Models\Tenant;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuditLogController extends Controller
{
    public function index()
    {
        try {
            // Fetch all audit logs with user relationship
            $auditLogs = AuditLog::with('user')->get();
    
            // Format the response as a single array with extra data
            $formattedLogs = $auditLogs->map(function ($log) {
                $extraData = 'N/A'; // Default extra data
    
                // Determine the extra data based on auditable_type
                switch ($log->auditable_type) {
                    case 'App\Models\Tenant':
                        $tenant = Tenant::find($log->auditable_id);
                        $extraData = $tenant ? $tenant->tenant_number : 'N/A';
                        break;
                    case 'App\Models\Contract':
                        $contract = Contract::find($log->auditable_id);
                        $extraData = $contract ? $contract->contract_number : 'N/A';
                        break;
                    case 'App\Models\PaymentForBuyer':
                        $payment = PaymentForBuyer::find($log->auditable_id);
                        $extraData = $payment ? $payment->payment_number : 'N/A';
                        break;
                    case 'App\Models\PaymentForTenant':
                        $payment = PaymentForTenant::find($log->auditable_id);
                        $extraData = $payment ? $payment->payment_number : 'N/A';
                        break;
                    case 'App\Models\Utility':
                        $utility = Utility::find($log->auditable_id);
                        $extraData = $utility ? $utility->utility_number : 'N/A';
                        break;
                }
    
                return [
                    'user_id' => $log->user_id,
                    'user_name' => $log->user->name ?? 'N/A', // Fallback if user is missing
                    'event' => $log->event,
                    'document_for' => $log->document_for,
                    'auditable_id' => $log->auditable_id,
                    'auditable_type' => $log->auditable_type,
                    'extra_data' => $extraData, // Include extra data as a string
                    'created_at' => $log->created_at->toDateTimeString(),
                ];
            });
    
            return response()->json([
                'success' => true,
                'data' => $formattedLogs,
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Handle cases where the model is not found
            return response()->json([
                'error' => 'Audit log model not found.',
                'details' => $e->getMessage(),
            ], 404); // 404 Not Found status
        } catch (\Exception $e) {
            // Handle other unexpected errors
            return response()->json([
                'error' => 'An error occurred while retrieving audit logs.',
                'details' => $e->getMessage(),
            ], 500); // 500 Internal Server Error status
        }
    }
    }
