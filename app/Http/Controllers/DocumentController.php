<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Tenant;
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
        try {
            $documentType = $request->input('document_type');
            
            if (!$documentType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document type is required for filtering.'
                ], 400);
            }

            $documents = Document::where('document_type', $documentType)->get();

            return response()->json([
                'success' => true,
                'data' => $documents
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to filter documents by document type: ' . $e->getMessage()
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
}
