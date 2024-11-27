<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\PaymentForBuyerController;
use App\Http\Controllers\PaymentForTenantController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\AuthController;

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

Route::get('user-info', [AuthController::class, 'getUserInfo']);

Route::post('tenants', [TenantController::class, 'store']);

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::post('refresh-token', [AuthController::class, 'refreshToken']);


Route::middleware(['jwt.auth'])->group(function () {
    Route::middleware(['auth:api', 'role:admin'])->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('edituser/{id}', [AuthController::class, 'update']);
        Route::post('filter-by-phone', [AuthController::class, 'filterByPhone']);
        Route::post('users/{id}/status', [AuthController::class, 'updateStatus']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::get('users', [AuthController::class, 'listAllUsers']);


    });
    Route::middleware(['permission:manage categories'])->group(function () {

Route::post('categories', [CategoryController::class, 'store']); // Create a new category

Route::post('categories/{id}', [CategoryController::class, 'update']); // Update a category by ID
Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

});
// Delete a category by ID
Route::get('categories/{id}', [CategoryController::class, 'show']);
Route::get('categories', [CategoryController::class, 'index']); // List all categories
Route::get('category_search/{name}', [CategoryController::class, 'search']);
Route::patch('categories_restore/{id}', [CategoryController::class, 'restore']);
Route::get('categories/trashed', [CategoryController::class, 'trashed']);
Route::get('categoriesinbuildings/{id}', [CategoryController::class, 'buildingsInCategoryid']);
Route::get('buildingsInCategory', [CategoryController::class, 'buildingsInCategory']);


Route::middleware(['permission:manage buildings'])->group(function () {
    Route::post('buildings', [BuildingController::class, 'store']);
    Route::post('buildings/{id}', [BuildingController::class, 'update']);
    Route::delete('buildings/{id}', [BuildingController::class, 'destroy']);
    Route::patch('buildings_restore/{id}', [BuildingController::class, 'restore']);
});

Route::get('buildings', [BuildingController::class, 'index']);
Route::get('buildings/{id}', [BuildingController::class, 'show']);
Route::get('buildings_trashed', [BuildingController::class, 'trashed']);
Route::get('building_search', [BuildingController::class, 'search']); // Get all soft-deleted buildings


Route::middleware(['permission:manage floors'])->group(function () {
    Route::post('floors', [FloorController::class, 'store']);
    Route::delete('floors/{id}', [FloorController::class, 'destroy']);
    Route::patch('floors_restore/{id}', [FloorController::class, 'restore']);
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

Route::prefix('tenants')->middleware(['permission:manage tenants'])->group(function () {

    Route::get('/', [TenantController::class, 'index'])->middleware('permission:view tenants');;
    Route::get('{id}', [TenantController::class, 'show'])->middleware('permission:view tenants');;
    Route::post('{id}', [TenantController::class, 'update']);
    Route::delete('{id}', [TenantController::class, 'destroy']);
    Route::patch('{id}/restore', [TenantController::class, 'restore']);
    Route::post('/{id}/deactivate', [TenantController::class, 'deactivateTenant']);

    Route::get('trashed', [TenantController::class, 'trashed']);
    Route::post('tenants/{id}/status', [TenantController::class, 'updateStatus']);
});
Route::get('search', [TenantController::class, 'search'])->middleware('permission:view tenants');;
Route::post('buyer', [TenantController::class, 'storeBuyer'])->middleware('permission:manage tenants');;

Route::middleware(['permission:manage contracts'])->group(function () {
    Route::post('contracts', [ContractController::class, 'store']);
    Route::post('contracts/{id}', [ContractController::class, 'update']);
    Route::post('contracts_renew/{id}', [ContractController::class, 'renew']);
    Route::delete('contracts/{id}', [ContractController::class, 'destroy']);
    Route::patch('contracts/{id}/restore', [ContractController::class, 'restore']);
    Route::post('contracts/{id}/status', [ContractController::class, 'updateStatus']);
});

Route::post('/contractsfilter', [ContractController::class, 'filterByType']);
Route::post('contracts', [ContractController::class, 'index']);
Route::get('tenantcontracts/{tenantId}', [ContractController::class, 'getTenantContracts']);  // List all contracts
Route::get('contracts/{id}', [ContractController::class, 'show']);  // View contract

Route::post('getpayments', [PaymentForBuyerController::class, 'index']);
Route::get('payments/{id}', [PaymentForBuyerController::class, 'show']);
Route::get('/tenantpayments/{tenantId}', [PaymentForBuyerController::class, 'searchByTenantId']);

Route::middleware(['permission:manage payments'])->group(function () {
Route::post('/payments', [PaymentForBuyerController::class, 'store']);
Route::post('payments/{id}', [PaymentForBuyerController::class, 'update']);
Route::delete('/payments/{id}', [PaymentForBuyerController::class, 'destroy']);
Route::post('/payments/{id}/restore', [PaymentForBuyerController::class, 'restore']);
Route::post('/payments/{id}/renew', [PaymentForBuyerController::class, 'renew']);
});

Route::post('gettenantpayments', [PaymentForTenantController::class, 'index'])->name('payments.index');
Route::get('tenant_payments/{id}', [PaymentForTenantController::class, 'show'])->name('payments.show');

Route::middleware(['permission:manage payments'])->group(function () {
Route::post('tenantpayments', [PaymentForTenantController::class, 'store'])->name('payments.store');
Route::post('tenantpayments/{id}', [PaymentForTenantController::class, 'update'])->name('payments.update');
Route::delete('tenantpayments/{id}', [PaymentForTenantController::class, 'destroy'])->name('payments.destroy');
Route::patch('tenantpayments_restore/{id}', [PaymentForTenantController::class, 'restore'])->name('payments.restore');
Route::get('payments_tenant/{tenantId}', [PaymentForTenantController::class, 'searchByTenantId'])->name('payments.searchByTenantId');
});

Route::middleware(['permission:manage documents'])->group(function () {
    Route::post('/documents', [DocumentController::class, 'store']);  // Only manage documents permission can upload
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);  // Only manage documents permission can delete
});


Route::get('/documents', [DocumentController::class, 'listAllDocuments']);
Route::get('/filterdocbytype', [DocumentController::class, 'filterByDocumentType']);
Route::get('/filterdocbytenant/{tenantId}', [DocumentController::class, 'filterByTenantId']);
Route::post('/getdocuments', [DocumentController::class, 'getDocumentsByFloor']);

});
