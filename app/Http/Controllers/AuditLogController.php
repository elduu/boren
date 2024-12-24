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
            // Retrieve all audit logs with the associated user
            $auditLogs = AuditLog::with('user')->get();

            // Check if there are no audit logs
            if ($auditLogs->isEmpty()) {
                return response()->json([
                    'message' => 'No audit logs found.',
                    'data' => []
                ], 200); // 404 Not Found status
            }

            return response()->json([
                'message' => 'Audit logs retrieved successfully.',
                'data' => $auditLogs
            ], 200); // 200 OK status

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