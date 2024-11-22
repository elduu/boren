<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\ContractController;

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



Route::get('categories', [CategoryController::class, 'index']); // List all categories
Route::post('categories', [CategoryController::class, 'store']); // Create a new category
Route::get('categories/{id}', [CategoryController::class, 'show']); // Show a specific category by ID
Route::post('categories/{id}', [CategoryController::class, 'update']); // Update a category by ID
Route::delete('categories/{id}', [CategoryController::class, 'destroy']); // Delete a category by ID
Route::get('category_search/{name}', [CategoryController::class, 'search']);
Route::patch('categories_restore/{id}', [CategoryController::class, 'restore']);
Route::get('categories/trashed', [CategoryController::class, 'trashed']);
Route::get('categoriesinbuildings/{id}', [CategoryController::class, 'buildingsInCategory']);
Route::get('buildingsInCategory', [CategoryController::class, 'buildingsInCategory']);


Route::get('buildings', [BuildingController::class, 'index']); // List all buildings
Route::post('buildings', [BuildingController::class, 'store']); // Create a new building
Route::get('buildings/{id}', [BuildingController::class, 'show']); // Show a specific building
Route::post('buildings/{id}', [BuildingController::class, 'update']); // Update a building
Route::delete('buildings/{id}', [BuildingController::class, 'destroy']); // Soft delete a building
Route::get('buildings_trashed', [BuildingController::class, 'trashed']); // Get all soft-deleted buildings
Route::patch('buildings_restore/{id}',[BuildingController::class, 'restore']);
Route::get('building_search', [BuildingController::class, 'search']); // Get all soft-deleted buildings 


Route::get('floors', [FloorController::class, 'index']);
Route::post('floors', [FloorController::class, 'store']);
Route::delete('floors/{id}', [FloorController::class, 'destroy']);
Route::patch('floors_restore/{id}', [FloorController::class, 'restore']);
Route::get('floors/search', [FloorController::class, 'search']);
Route::get('/floorsinbuilding/{id}', [BuildingController::class, 'listFloorsInBuilding']);
Route::get('/buildings/filterFloorsInCategory', [BuildingController::class, 'filterFloorsInCategory']);
Route::get('/floors/{floorId}/tenants', [FloorController::class, 'listTenantsInFloor']);
// Route group for tenant-related actions
Route::prefix('tenants')->group(function() {
    
    Route::post('/', [TenantController::class, 'store']);
    Route::get('/', [TenantController::class, 'index']);
    Route::get('{id}', [TenantController::class, 'show']);
    Route::post('{id}', [TenantController::class, 'update']);
    Route::delete('{id}', [TenantController::class, 'destroy']);
    Route::patch('{id}/restore', [TenantController::class, 'restore']);
    
    Route::get('trashed', [TenantController::class, 'trashed']);
    Route::post('tenants/{id}/status', [TenantController::class, 'updateStatus']);
});
Route::get('search', [TenantController::class, 'search']);

Route::get('contracts', [ContractController::class, 'index']);  // List all contracts
Route::get('contracts/{id}', [ContractController::class, 'show']);  // Get contract details by ID
Route::post('contracts', [ContractController::class, 'store']);  // Create a new contract
Route::post('contracts/{id}', [ContractController::class, 'update']);  // Update contract details
Route::post('contracts_renew/{id}', [ContractController::class, 'renew']);  // Renew a contract
Route::delete('contracts/{id}', [ContractController::class, 'destroy']);  // Soft delete a contract
Route::patch('contracts/{id}/restore', [ContractController::class, 'restore']);  // Restore a soft-deleted contract
Route::post('contracts/{id}/status', [ContractController::class, 'updateStatus']);  // Update contract status


