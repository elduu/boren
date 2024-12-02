<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Tenant;
use Illuminate\Support\Facades\Validator;
class DocumentController extends Controller
{
    public function listAllDocuments()
    {
        try {
            $documents = Document::all()->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $documents
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve documents: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Filter documents by document type.
     */
    public function filterByDocumentType(Request $request)
    {
            // Get the input values
           
            try {
                // Validate the request
                $validator = Validator::make($request->all(), [
                    'floor_id' => 'required|exists:floors,id',
                    'document_type' =>'required|exists:documents,document_type',
                ]);
        
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors(),
                    ], 422);
                }
        
                // Fetch tenants on the specified floor with their related documents
                $tenants = Tenant::where('floor_id', $request->floor_id)
                    ->with(['documents']) // Assuming a `documents` relationship exists on the Tenant model
                    ->get();
        
                // Check if tenants exist on the specified floor
                if ($tenants->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tenants or documents found on the specified floor.',
                    ], 404);
                }
        
                // Prepare the response data
                $documentsData = $tenants->map(function ($tenant) {
                    return [
                       
                        'documents' => $tenant->documents->map(function ($document) {
                            return [
                                'tenant_name' => $document->tenant->name,
                                'document_id' => $document->id,
                                'document_path' => $document->file_path,
                                'document_type' => $document->document_type,
                                'document_format'=> $document->document_format,
                                'created_at' => $document->created_at->format('Y-m-d H:i:s'),
                            ];
                        }),
                    ];
                });
        
                return response()->json([
                    'success' => true,
                    'data' => $documentsData,
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch documents: ' . $e->getMessage(),
                ], 500);
            }
        }
    

    /**
     * Filter documents by tenant ID.
     */
    public function filterByTenantId($tenantId)
    {
        try {
            $documents = Document::where('documentable_id', $tenantId)
                                 ->where('documentable_type', 'App\Models\Tenant') // Adjust based on the model namespace
                                 ->orderBy('created_at', 'desc')->get();
            if ($documents->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No documents found for the specified tenant.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $documents
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to filter documents by tenant ID: ' . $e->getMessage()
            ], 500);
        }
    }
    public function filterByTenantName(Request $request)
{
    try {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'tenant_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the tenant by name
        $tenant = Tenant::where('name', 'like', '%' . $request->tenant_name . '%')->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No tenant found with the specified name.',
            ], 404);
        }

        // Fetch documents related to the tenant
        $documents = Document::where('documentable_id', $tenant->id)
                             ->where('documentable_type', 'App\Models\Tenant') // Adjust based on your namespace
                             ->orderBy('created_at', 'desc') // Most recent documents first
                             ->get();

        if ($documents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No documents found for the specified tenant.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tenant_name' => $tenant->name,
                'documents' => $documents,
            ],
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to filter documents by tenant name: ' . $e->getMessage(),
        ], 500);
    }
}
    public function getDocumentsByFloor(Request $request)
{
    try {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'floor_id' => 'required|exists:floors,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Fetch tenants on the specified floor with their related documents
        $tenants = Tenant::where('floor_id', $request->floor_id)
            ->with(['documents']) // Assuming a `documents` relationship exists on the Tenant model
            ->get();

        // Check if tenants exist on the specified floor
        if ($tenants->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No tenants or documents found on the specified floor.',
            ], 404);
        }

        // Prepare the response data
        $documentsData = $tenants->map(function ($tenant) {
            return [
               
                'documents' => $tenant->documents->map(function ($document) {
                    return [
                        'tenant_name' => $document->tenant->name,
                        'document_id' => $document->id,
                        'document_path' => $document->file_path,
                        'document_type' => $document->document_type,
                        'document_format'=> $document->document_format,
                        'created_at' => $document->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $documentsData,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch documents: ' . $e->getMessage(),
        ], 500);
    }
}
}
