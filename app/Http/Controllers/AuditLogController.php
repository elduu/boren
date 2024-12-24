<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuditLogController extends Controller
{
        public function index()
        {
            try {
                // Fetch all audit logs with user relationship
                $auditLogs = AuditLog::with('user')->get();
    
                // Format the response as a single array
                $formattedLogs = $auditLogs->map(function ($log) {
                    return [
                        'user_id' => $log->user_id,
                        'user_name' => $log->user->name ?? 'N/A', // Fallback if user is missing
                        'event' => $log->event,
                        'document_for' => $log->document_for,
                        'auditable_id' => $log->auditable_id,
                        'auditable_type' => $log->auditable_type,
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
                'details' => $e->getMessage()
            ], 200); // 404 Not Found status

        } catch (\Exception $e) {
            // Handle other unexpected errors
            return response()->json([
                'error' => 'An error occurred while retrieving audit logs.',
                'details' => $e->getMessage()
            ], 500); // 500 Internal Server Error status
        }
    }
}