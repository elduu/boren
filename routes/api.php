<?php

// Set the application instance

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\BuildingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\PaymentForBuyerController;
use App\Http\Controllers\PaymentForTenantController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\AuthController;
use App\Models\Contract;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\AuditLogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
     return $request->user();
});



Route::post('tenants', [TenantController::class, 'store']);

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::post('refresh-token', [AuthController::class, 'refreshToken']);

//Route::get('contractsadd', [ContractController::class, 'storecontracts']);

Route::middleware(['jwt.auth','auth:api'])->group(function () {
 Route::middleware([ 'role:admin'])->group(function () {
 Route::post('register', [AuthController::class, 'register']);
 Route::post('edituser/{id}', [AuthController::class, 'update']);
Route::post('filter-by-phone', [AuthController::class, 'filterByPhone']);
 Route::post('users/{id}/status', [AuthController::class, 'updateStatus']);
 Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
 Route::post('reset-password', [AuthController::class, 'resetPassword']);
 Route::post('update_admin', [AuthController::class, 'updateAdminCredentials']);
 Route::get('users', [AuthController::class, 'listAllUsers']);
Route::get('user-info', [AuthController::class, 'getUserInfo']);
 Route::get('documents', [ReportController::class, 'getAllDocuments']);

Route::delete('contracts/{id}', [ContractController::class, 'destroy']);
 Route::patch('contracts/{id}/restore', [ContractController::class, 'restore']);
Route::get('audit-logs', [AuditLogController::class, 'index']);


Route::get('/deleted-floors', [FloorController::class, 'listDeletedFloors']);
Route::get('/deleted-contracts', [ContractController::class, 'listDeletedContracts']);
Route::get('/deleted-payments', [PaymentForTenantController::class, 'listDeletedPayments']);
Route::get('/deleted-payments-buyer', [PaymentForBuyerController::class, 'listDeletedPayments']);
Route::get('/deleted-tenants', [TenantController::class, 'listDeletedTenants']);
Route::get('/deleted-documents', [DocumentController::class, 'listDeletedDocuments']);
Route::get('/deleted-utilities', [UtilityController::class, 'listDeleted']);

Route::delete('/documents/{id}', [DocumentController::class, 'deleteDocument']);
Route::patch('/documents/{id}/restore', [DocumentController::class, 'recoverDocument']);
Route::delete('floors/{id}', [FloorController::class, 'destroy']);
Route::patch('floors_restore/{id}', [FloorController::class, 'restore']);

Route::prefix('tenants')->middleware(['permission:manage tenants'])->group(function () {
 Route::delete('{id}', [TenantController::class, 'destroy']);
 Route::patch('{id}/restore', [TenantController::class, 'restore']);
Route::post('/{id}/deactivate', [TenantController::class, 'deactivateTenant']);
 Route::get('trashed', [TenantController::class, 'trashed']);
Route::post('tenants/{id}/status', [TenantController::class, 'updateStatus']);
});
Route::delete('payments/{id}', [PaymentForBuyerController::class, 'destroy']);
Route::post('payments/{id}/restore', [PaymentForBuyerController::class, 'restore']);


Route::post('categories', [CategoryController::class, 'store']); // Create a new category

Route::post('categories/{id}', [CategoryController::class, 'update']); // Update a category by ID
Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
Route::get('alllist', [CategoryController::class, 'listCategoriesWithBuildingsAndFloors']);

// Delete a category by ID

Route::patch('categories_restore/{id}', [CategoryController::class, 'restore']);
Route::get('categories_trashed', [CategoryController::class, 'trashed']);

Route::post('buildings', [BuildingController::class, 'store']);
Route::post('buildings/{id}', [BuildingController::class, 'update']);
Route::delete('buildings/{id}', [BuildingController::class, 'destroy']);
Route::patch('buildings_restore/{id}', [BuildingController::class, 'restore']);
Route::get('buildings_trashed', [BuildingController::class, 'trashed']);
// Get all soft-deleted buildings

Route::post('floors', [FloorController::class, 'store']);
Route::post('editfloors/{id}', [FloorController::class, 'update']);
Route::post('contractadd', [FloorController::class, 'storeContract']);

});

Route::middleware([ 'role:writer|admin'])->group(function () {
Route::prefix('tenants')->middleware(['permission:manage tenants'])->group(function () {
Route::post('{id}', [TenantController::class, 'update']);
 });

 Route::post('/documents', [DocumentController::class, 'store']); 
Route::post('send-contract-renewal-emails', [EmailController::class, 'sendContractRenewalEmails']);
Route::post('send-payment-due-emails', [EmailController::class, 'sendPaymentDueEmails']); 
 Route::post('contracts/{id}', [ContractController::class, 'update']);
 Route::post('contracts_renew/{id}', [ContractController::class, 'renew']);
 Route::post('contracts/{id}/status', [ContractController::class, 'updateStatus']);

Route::post('utilities/{id}', [UtilityController::class, 'update']);

Route::delete('utilities/{id}', [UtilityController::class, 'delete']);
Route::patch('utilities/{id}', [UtilityController::class, 'restore']);
Route::post('utilities', [UtilityController::class, 'store']);

Route::post('payments', [PaymentForBuyerController::class, 'store']);
Route::post('payments/{id}', [PaymentForBuyerController::class, 'update']);

Route::post('payments/{id}/renew', [PaymentForBuyerController::class, 'renew']);

Route::post('tenantpayments', [PaymentForTenantController::class, 'store'])->name('payments.store');
Route::post('tenantpayments/{id}', [PaymentForTenantController::class, 'update'])->name('payments.update');
Route::delete('tenantpayments/{id}', [PaymentForTenantController::class, 'destroy'])->name('payments.destroy');
Route::patch('tenantpayments_restore/{id}', [PaymentForTenantController::class, 'restore'])->name('payments.restore');
Route::get('payments_tenant/{tenantId}', [PaymentForTenantController::class, 'searchByTenantId'])->name('payments.searchByTenantId');
});



Route::middleware(['auth:api', 'role:admin|writer|read_only'])->group(function () {
     Route::get('categories/{id}', [CategoryController::class, 'show']);
     Route::get('user-', [AuthController::class, 'getUserInfo']);
Route::get('users', [AuthController::class, ' listAllUsers']);
 Route::get('category_search/{name}', [CategoryController::class, 'search']);
Route::get('buildings', [BuildingController::class, 'index']);
 Route::get('buildings/{id}', [BuildingController::class, 'show']);
 Route::get('new-files-count', [ReportController::class, 'getNewFilesCount']);
Route::get('alltenants-count', [ReportController::class, 'getAllTenantsCount']);
 Route::get('alldocs-count', [ReportController::class, 'getAllDocumentsCount']);
 Route::get('building_search', [BuildingController::class, 'search']); 
 Route::get('categories', [CategoryController::class, 'index']);
 Route::get('categoriesinbuildings/{id}', [CategoryController::class, 'buildingsInCategoryid']);
 Route::get('buildingsInCategory', [CategoryController::class, 'buildingsInCategory']);
 
Route::post('getdatabuyer', [FloorController::class, 'getBuildingDataBuyer']);
Route::post('getfloordatabuyer', [FloorController::class, 'getFloorDataBuyer']);
 Route::get('expired-contracts', [ReportController::class, 'getExpiredContracts']);
 Route::get('overdue-contracts', [ReportController::class, 'getOverdueContracts']);
 Route::get('overdue-payments', [ReportController::class, 'getOverduePayments']);
 Route::get('duplicate-documents', [ReportController::class, 'getDuplicateDocuments']);
 Route::get('new-files', [ReportController::class, 'getNewFiles']);
  Route::get('alltenants', [ReportController::class, 'getAllTenants']);
 Route::get('alldocs', [ReportController::class, 'getAllDocuments']);
 Route::get('expired-contracts-count', [ReportController::class, 'getExpiredContractsCount']);
 Route::get('overdue-contracts-count', [ReportController::class, 'getOverdueContractsCount']);
Route::get('overdue-payments-count', [ReportController::class, 'getOverduePaymentsCount']);
 Route::get('utility-chart', [ReportController::class, 'getCurrentMonthUtilityCostReport']);
Route::get('search', [TenantController::class, 'search']);
 Route::post('buyer', [TenantController::class, 'storeBuyer']);
  Route::post('getutilites', [UtilityController::class, 'index']);
 Route::post('getcontracts', [ContractController::class, 'index']);
  Route::get('tenantcontracts/{tenantId}', [ContractController::class, 'getTenantContracts']);
 Route::post('/contractsfilter', [ContractController::class, 'filterByType']);
 Route::get('contracts/{id}', [ContractController::class, 'show']); // View contract
Route::get('/documents', [DocumentController::class, 'listAllDocuments']);
 Route::post('/filterdocbytype', [DocumentController::class, 'filterByDocumentType']);
Route::get('/filterdocbytenant/{tenantId}', [DocumentController::class, 'filterByTenantId']);
 Route::post('/getdocuments', [DocumentController::class, 'getDocumentsByFloor']);
 Route::post('/searchdoc', [DocumentController::class, 'filterByTenantName']);
Route::get('/documentsdownload/{id}', [DocumentController::class, 'download']);

Route::post('getpayments', [PaymentForBuyerController::class, 'index']);
Route::get('payments/{id}', [PaymentForBuyerController::class, 'show']);
Route::get('/tenantpayments/{tenantId}', [PaymentForBuyerController::class, 'searchByTenantId']);
 Route::post('gettenantpayments', [PaymentForTenantController::class, 'index'])->name('payments.index');
Route::get('tenant_payments/{id}', [PaymentForTenantController::class, 'show'])->name('payments.show');

Route::get('allnotifications', [NotificationController::class, 'index']);
Route::get('notifications', [NotificationController::class,'getUnreadNotifications']);
 Route::post('notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
Route::post('notifications_mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
Route::get('unread-notifications', [NotificationController::class, 'countUnreadNotifications']);
 Route::get('contract-renewal-notifications', [NotificationController::class, 'listContractRenewalNotifications']);
Route::get('payment-due-notifications', [NotificationController::class, 'listPaymentDueNotifications']);
 Route::post('contracts_update/{id}', [ContractController::class, 'updatecontract']);

 Route::post('searchpaymentbuyer', [PaymentForBuyerController::class, 'search']);
 Route::post('searchpaymenttenant', [PaymentForTenantController::class, 'search']);
 Route::post('searchcontracts', [ContractController::class, 'search']);

Route::prefix('tenants')->middleware(['permission:manage tenants'])->group(function () {

 Route::get('/', [TenantController::class, 'index'])->middleware('permission:view tenants');;
Route::get('{id}', [TenantController::class, 'show'])->middleware('permission:view tenants');
});
Route::post('/floorpayments', [FloorController::class, 'listPaymentsInFloor']);
Route::post('/floordocuments', [FloorController::class, 'listDocumentsInFloor']);
Route::post('/floorcontracts', [FloorController::class, 'listContractsInFloor']);
Route::get('floors', [FloorController::class, 'index']);
Route::get('floors/search', [FloorController::class, 'search']);
Route::get('floors/{floorId}/tenants', [FloorController::class, 'listTenantsInFloor']);
Route::get('floorsinbuilding/{id}', [BuildingController::class, 'listFloorsInBuilding']);
Route::get('buildings/filterFloorsInCategory', [BuildingController::class, 'filterFloorsInCategory']);
Route::post('getdata', [FloorController::class, 'getBuildingData']);
Route::post('getfloordata', [FloorController::class, 'getFloorData']);
});


});