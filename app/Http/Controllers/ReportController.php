<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\PaymentForTenant;
use App\Models\Tenant;
use App\Models\Utility;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get all documents list.
     */
    public function getAllDocuments()
    {
        $allDocuments = Document::all();
        $Filescount=$allDocuments->count();               

        return response()->json([
            'total_file'=> $Filescount,
            'data'=>$allDocuments,
        ],
    );
    }
    
    public function getAllDocumentsCount()
    {
        $allDocuments = Document::all();
        $Filescount=$allDocuments->count();               

        return response()->json([
            'total_file'=> $Filescount,
        
        ],
    );
    }
    

       
    

    /**
     * Get all contracts that are expired in the current month.
     */
    public function getExpiredContracts()
    {
        try {
            // Current month and year
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;
    
            // Query contracts that expired in the current month and year
            $expiredContracts = Contract::with(['tenant:id,name,floor_id','floor', 'category', 'documents'])
               // ->whereYear('expiring_date', $currentYear)
               // ->whereMonth('expiring_date', $currentMonth)
               ->where('expiring_date', '<=', Carbon::now())
               // ->where('status', '', 'expired')
                ->orderBy('created_at', 'desc')
                ->get();
    
            // if ($expiredContracts->isEmpty()) {
            //     return response()->json(['success' => false, 'data' => []], 200);
            // }
    
            // Format the response
            $data = $expiredContracts->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'tenant_id' => $contract->tenant->id,
                    'tenant_name' => $contract->tenant->name ?? null,
                    'floor_name' => $contract->tenant->floor->name ?? null, // Include floor name
                    'category_name' => $contract->category->name ?? null,  // Include category name
                    'type' => $contract->type,
                    'status' => $contract->contract_status,
                    'signing_date' => $contract->signing_date,
                    'expiring_date' => $contract->expiring_date,
                    'due_date' => $contract->due_date,
                    'created_at' => $contract->created_at,
                    'updated_at' => $contract->updated_at,
                    'documents' => $contract->documents->map(function ($document) {
                        return [
                            'id' => $document->id,
                            'document_type' => $document->document_type,
                            'document_format' => $document->document_format,
                            'file_path' => url($document->file_path),
                            'created_at' => $document->created_at,
                            'updated_at' => $document->updated_at,
                            'doc_name' => $document->doc_name,
                            'doc_size' => $document->doc_size,
                        ];
                    }),
                ];
            });
            $expiredContractscount = $expiredContracts->count();
            return response()->json(['success' => true, 
             'expired_count' => $expiredContractscount,'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expired contracts: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function getExpiredContractsCount()
    {
        try {
            // Current date
            $currentDate = Carbon::now();
        
            // Query to count contracts that expired before or on the current date
            $expiredContractsCount = Contract::where('expiring_date', '<=', $currentDate)
                // You can add more conditions like the 'status' check here if needed
                // ->where('status', 'expired')
                ->count();
        
            // if ($expiredContractsCount == 0) {
            //     return response()->json(['success' => false, 'message' => 'No expired contracts found.'], 200);
            // }
        
            return response()->json([
                'success' => true,
                'expired_count' => $expiredContractsCount,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expired contracts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all contracts that are overdue.
     */
    public function getOverdueContracts(Request $request)
    {
        try {
            // Current month and year
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;
    
            // Query contracts with overdue status
            $contracts = Contract::with(['tenant:id,name,floor_id','floor', 'category', 'documents'])
                ->whereYear('due_date', $currentYear)
                ->whereMonth('due_date', $currentMonth)
                ->where('due_date', '<=', Carbon::now())
                ->where('expiring_date', '>=', Carbon::now())
             //   ->where('status', '', 'overdue')
                ->orderBy('created_at', 'desc')
                ->get();
    
            // if ($contracts->isEmpty()) {
            //     return response()->json(['success' => false, 'data' => 'No overdue contracts found.'], 200);
            // }
    
            // Format the response
            $data = $contracts->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'tenant_id' => $contract->tenant->id,
                    'tenant_name' => $contract->tenant->name ?? null,
                    'floor_name' => $contract->tenant->floor->name ?? null, // Include floor name
                    'category_name' => $contract->category->name ?? null,  // Include category name
                    'type' => $contract->type,
                    'status' => $contract->contract_status,
                    'signing_date' => $contract->signing_date,
                    'expiring_date' => $contract->expiring_date,
                    'due_date' => $contract->due_date,
                    'created_at' => $contract->created_at,
                    'updated_at' => $contract->updated_at,
                    'documents' => $contract->documents->map(function ($document) {
                        return [
                            'id' => $document->id,
                            'document_type' => $document->document_type,
                            'document_format' => $document->document_format,
                            'file_path' => url($document->file_path),
                            'created_at' => $document->created_at,
                            'updated_at' => $document->updated_at,
                            'doc_name' => $document->doc_name,
                            'doc_size' => $document->doc_size,
                        ];
                    }),
                ];
            });
    
            // Count of overdue contracts
            $overdueCount = $contracts->count();
    
            return response()->json([
                'success' => true,
                'overdue_count' => $overdueCount,
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve overdue contracts: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function getOverdueContractsCount(Request $request)
{
    try {
        // Current date
        $currentDate = Carbon::now();
    
        // Count contracts that are overdue
        $overdueCount = Contract::whereYear('due_date', $currentDate->year)
            ->whereMonth('due_date', $currentDate->month)
            ->where('due_date', '<=', $currentDate)
            ->where('expiring_date', '>=', $currentDate)
            // You can add a status check if needed
            // ->where('status', 'overdue')
            ->count();
    
        // if ($overdueCount === 0) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'No overdue contracts found.',
        //     ], 200);
        // }
    
        return response()->json([
            'success' => true,
            'overdue_count' => $overdueCount,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve overdue contracts: ' . $e->getMessage(),
        ], 500);
    }
}
    /**
     * Get all overdue payments in the current month.
     */public function getOverduePayments()
{
    try {
        // Query overdue payments with related tenant and documents
        $overduePayments = PaymentForTenant::with(['tenant:id,name,floor_id', 'documents'])
           // ->where('status', 'overdue')
            ->where('due_date', '<=', Carbon::now())
            ->where('payment_made_until', '>=', Carbon::now())
            ->orderBy('due_date', 'desc')
            ->get();

            // if ($overduePayments->isEmpty()) {
            //     return response()->json(['success' => false, 'message' => 'No overdue payments  found.'], 200);
            // }
        // Map results to include tenant details, documents, and additional metadata
        $data = $overduePayments->map(function ($payment) {
            return [
                'payment_id' => $payment->id,
                'tenant_id' => $payment->tenant->id,
                'tenant_name' => $payment->tenant->name,
                'floor_name' => $payment->tenant->floor->name ?? null, // Include floor name
                'category_name' => $payment->tenant->category->name ?? null, // Include category name
                'unit_price' => $payment->unit_price,
                'monthly_paid' => $payment->monthly_paid,
                'area_m2' => $payment->area_m2,
                'payment_status' => $payment->payment_status,
                'utility_fee' => $payment->utility_fee,
                'start_date' => $payment->start_date,
                'payment_made_until' => $payment->payment_made_until,
                'due_date' => $payment->due_date,
                'created_at' => $payment->created_at,
                'updated_at' => $payment->updated_at,
                'documents' => $payment->documents->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'document_type' => $document->document_type,
                        'document_format' => $document->document_format,
                        'file_path' => url($document->file_path),
                        'created_at' => $document->created_at,
                        'updated_at' => $document->updated_at,
                        'doc_name' => $document->doc_name,
                        'doc_size' => $document->doc_size,
                    ];
                }),
            ];
        });

        // Count the total number of results
        $totalCount = $overduePayments->count();

        return response()->json([
            'success' => true,
            'total_count' => $totalCount,
            'data' => $data,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch overdue payments: ' . $e->getMessage(),
        ], 500);
    }
}
public function getOverduePaymentsCount()
{
    try {
        // Current date
        $currentDate = Carbon::now();
    
        // Count overdue payments
        $overduePaymentsCount = PaymentForTenant::where('due_date', '<=', $currentDate)
            ->where('payment_made_until', '>=', $currentDate)
            // Add status check if necessary
            // ->where('status', 'overdue')
            ->count();
    
        // if ($overduePaymentsCount === 0) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'No overdue payments found.',
        //     ], 200);
        // }
    
        return response()->json([
            'success' => true,
            'total_count' => $overduePaymentsCount,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch overdue payments: ' . $e->getMessage(),
        ], 500);
    }
}
    /**
     * Get all duplicate documents.
     */public function getDuplicateDocuments()
{
    // Query to get documents with the same name and count greater than 1 (duplicates)
    $duplicateDocuments = Document::select('doc_name', DB::raw('COUNT(*) as count'))
                                   ->groupBy('doc_name')
                                   ->having('count', '>', 1)
                                   ->get();

    // Initialize an array to hold the formatted document data
    $documentsData = [];

    // Loop through each group of duplicate documents
    foreach ($duplicateDocuments as $documentGroup) {
        // Find all documents with the same doc_name
        $duplicates = Document::where('doc_name', $documentGroup->doc_name)->get();

        // Loop through the duplicate documents and format them
        foreach ($duplicates as $duplicate) {
            $documentsData[] = [
                'tenant_name' => $duplicate->tenant->name ?? 'Unknown Tenant',
                'document_id' => $duplicate->id,
                'document_path' => url($duplicate->file_path),
                'document_type' => $duplicate->document_type,
                'document_format' => $duplicate->document_format,
                'doc_name' => $duplicate->doc_name,
                'doc_size' => $duplicate->doc_size,
                'created_at' => $duplicate->created_at->format('Y-m-d H:i:s'),
            ];
        }
    }

    return response()->json([
        'total_duplicate_documents' => $documentsData ? count($documentsData) : 0,
        'data' => $documentsData,
    ]);
}

    /**
     * Get all new files added in the current month.
     */
    public function getNewFiles()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
    
        // Get the documents created in the current month and year
        $newFiles = Document::whereYear('created_at', $currentYear)
                            ->whereMonth('created_at', $currentMonth)
                            ->get();
    
        // Map the documents to the desired structure
        $documentsData = $newFiles->map(function ($document) {
            return [
                'tenant_name' => $document->tenant->name ?? 'Unknown Tenant',
                'document_id' => $document->id,
                'document_path' => url($document->file_path),
                'document_type' => $document->document_type,
                'document_format' => $document->document_format,
                'doc_name' => $document->doc_name,
                'doc_size' => $document->doc_size,
                'created_at' => $document->created_at->format('Y-m-d H:i:s'),
            ];
        });
    
        $newFilesCount = $newFiles->count();
    
        return response()->json([
            'total_new_file' => $newFilesCount,
            'data' => $documentsData,
        ]);
    }
    public function getNewFilesCount()
    {
        try {
            // Get the current date
            $currentDate = Carbon::now();
    
            // Count documents created in the current month and year
            $newFilesCount = Document::whereYear('created_at', $currentDate->year)
                ->whereMonth('created_at', $currentDate->month)
                ->count();
    
            return response()->json([
                'success' => true,
                'total_new_file' => $newFilesCount,
            ], 200);
        } catch (\Exception $e) {
            // Handle potential errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch new files count: ' . $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Get all tenants list.
     */
    public function getAllTenants()
{
    // Fetch tenants with their related floor and category
    $tenants = Tenant::with(['floor', 'category'])->get();

    // Map the tenant data to include all tenant fields and the names of floor and category
    $tenantsData = $tenants->map(function ($tenant) {
        return [
            'tenant_id' => $tenant->id,
            'name' => $tenant->name,
            'tenant_number' => $tenant->tenant_number,
            'gender' => $tenant->gender,
            'phone_number' => $tenant->phone_number,
            'email' => $tenant->email,
            'room_number' => $tenant->room_number,
            'tenant_type' => $tenant->tenant_type,
            'tenant_status' => $tenant->status,
            'created_at' => $tenant->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $tenant->updated_at->format('Y-m-d H:i:s'),

            // Adding floor and category names
            'floor_name' => $tenant->floor->name ?? 'Unknown Floor',
            'category_name' => $tenant->category->name ?? 'Unknown Category',
        ];
    });

    // Get the total number of tenants
    $totalTenants = $tenants->count();

    return response()->json([
        'total_tenants' => $totalTenants,
        'data' => $tenantsData,
    ]);
}
public function getAllTenantsCount()
{
    // Fetch tenants with their related floor and category
    $tenants = Tenant::with(['floor', 'category'])->get();

    // Map the tenant data to include all tenant fields and the names of floor and category
   


    // Get the total number of tenants
    $totalTenants = $tenants->count();

    return response()->json([
        'total_tenants' => $totalTenants,
       
    ]);
}

public function getCurrentMonthUtilityCostReport()
{
    try {
        // Get the current year and month
        $currentYear = now()->year;
        $currentMonth = now()->month;

        // Aggregate utility costs for the current month
        $utilityReport = Utility::selectRaw("
                SUM(electric_bill_fee) as electric_cost,
                SUM(water_bill) as water_cost,
                SUM(generator_bill) as generator_cost,
                SUM(other_fee) as other_cost
            ")
            ->whereYear('start_date', $currentYear)
            ->whereMonth('start_date', $currentMonth)
            ->first();

        // Check if no data found for the current month
        if (
            $utilityReport->electric_cost == 0 &&
            $utilityReport->water_cost == 0 &&
            $utilityReport->generator_cost == 0 &&
            $utilityReport->other_cost == 0
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No utility data found for the current month.',
            ], 200);
        }

        // Format the data for pie chart (cost comparison)
        $totalCost = $utilityReport->electric_cost + $utilityReport->water_cost + $utilityReport->generator_cost + $utilityReport->other_cost;

        // Calculate the percentage for each category
        $electricPercentage = $totalCost > 0 ? ($utilityReport->electric_cost / $totalCost) * 100 : 0;
        $waterPercentage = $totalCost > 0 ? ($utilityReport->water_cost / $totalCost) * 100 : 0;
        $generatorPercentage = $totalCost > 0 ? ($utilityReport->generator_cost / $totalCost) * 100 : 0;
        $otherPercentage = $totalCost > 0 ? ($utilityReport->other_cost / $totalCost) * 100 : 0;
    
        // Format the data for the pie chart (cost comparison)
        $chartData = [
            'month' => now()->format('F'),
            'year' => $currentYear,
            'data' => [
                'Electric Cost' => [
                    'cost' => $utilityReport->electric_cost,
                    'percentage' => $electricPercentage,
                ],
                'Water Cost' => [
                    'cost' => $utilityReport->water_cost,
                    'percentage' => $waterPercentage,
                ],
                'Generator Cost' => [
                    'cost' => $utilityReport->generator_cost,
                    'percentage' => $generatorPercentage,
                ],
                'Other Cost' => [
                    'cost' => $utilityReport->other_cost,
                    'percentage' => $otherPercentage,
                ],
            ],
        ];
    
        return response()->json([
            'success' => true,
            'chart_data' => $chartData,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch utility report: ' . $e->getMessage(),
        ], 500);
    }
}
}