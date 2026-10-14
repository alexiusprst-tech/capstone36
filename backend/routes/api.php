<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentTemplateController;
use App\Http\Controllers\Api\OrderDocumentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\SpjController;
use Illuminate\Support\Facades\Route;

// Public Auth Routes
Route::post('/auth/register', [AuthController::class, 'register'])->name('api.auth.register');
Route::post('/auth/login', [AuthController::class, 'login'])->name('api.auth.login');

// Products & Inventory Catalog
Route::get('/products', [ProductController::class, 'index'])->name('api.products.index');
Route::get('/products/template', [ProductController::class, 'downloadTemplate'])->name('api.products.template');
Route::get('/products/categories', [ProductController::class, 'getCategories'])->name('api.products.categories');
Route::post('/products/categories', [ProductController::class, 'storeCategory'])->name('api.products.categories.store');
Route::put('/products/categories/{id}', [ProductController::class, 'updateCategory'])->name('api.products.categories.update');
Route::delete('/products/categories/{id}', [ProductController::class, 'destroyCategory'])->name('api.products.categories.destroy');

Route::get('/products/units', [ProductController::class, 'getUnits'])->name('api.products.units');
Route::post('/products/units', [ProductController::class, 'storeUnit'])->name('api.products.units.store');
Route::put('/products/units/{id}', [ProductController::class, 'updateUnit'])->name('api.products.units.update');
Route::delete('/products/units/{id}', [ProductController::class, 'destroyUnit'])->name('api.products.units.destroy');

Route::post('/products/import', [ProductController::class, 'import'])->name('api.products.import');
Route::get('/products/{id}', [ProductController::class, 'show'])->name('api.products.show');
Route::post('/products', [ProductController::class, 'store'])->name('api.products.store');
Route::put('/products/{id}', [ProductController::class, 'update'])->name('api.products.update');
Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('api.products.destroy');

// Suppliers
Route::get('/suppliers', [PurchaseController::class, 'getSuppliers'])->name('api.suppliers.index');
Route::post('/suppliers', [PurchaseController::class, 'storeSupplier'])->name('api.suppliers.store');

// Warehouse Requests (Material Request)
Route::get('/warehouse/requests', [PurchaseController::class, 'getWarehouseRequests'])->name('api.warehouse.requests.index');
Route::post('/warehouse/requests', [PurchaseController::class, 'storeWarehouseRequest'])->name('api.warehouse.requests.store');

// Purchases (Transaksi Pembelian)
Route::get('/purchases', [PurchaseController::class, 'getPurchases'])->name('api.purchases.index');
Route::post('/purchases', [PurchaseController::class, 'storePurchase'])->name('api.purchases.store');

// Regions (DKI Jakarta Kecamatan & Kelurahan Data)
Route::get('/regions', [RegionController::class, 'index'])->name('api.regions.index');
Route::get('/regions/tree', [RegionController::class, 'tree'])->name('api.regions.tree');

// Document Templates & Downloads (Accessible for authenticated or direct download)
Route::get('/document-templates', [DocumentTemplateController::class, 'index'])->name('api.templates.index');
Route::get('/document-templates/{id}', [DocumentTemplateController::class, 'show'])->name('api.templates.show');
Route::get('/document-templates/{id}/download-blank', [DocumentTemplateController::class, 'downloadBlank'])->name('api.templates.download-blank');
Route::get('/document-templates/{id}/download-master', [DocumentTemplateController::class, 'downloadMaster'])->name('api.templates.download-master');

Route::get('/documents', [OrderDocumentController::class, 'index'])->name('api.documents.index');
Route::get('/documents/{id}/download', [OrderDocumentController::class, 'download'])->name('api.documents.download');

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user'])->name('api.auth.user');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

    // Template Administration
    Route::post('/document-templates/{id}/upload', [DocumentTemplateController::class, 'upload'])->name('api.templates.upload');
    Route::post('/document-templates/{id}/toggle-status', [DocumentTemplateController::class, 'toggle-status'])->name('api.templates.toggle-status');

    // Document Generation & Scans
    Route::post('/documents/generate', [OrderDocumentController::class, 'generate'])->name('api.documents.generate');
    Route::post('/documents/upload-scan', [OrderDocumentController::class, 'uploadScan'])->name('api.documents.upload-scan');
    Route::delete('/documents/{id}', [OrderDocumentController::class, 'destroy'])->name('api.documents.destroy');

});

// SPJ Integrated Routes (Operational, Read, Generate & Updates)
Route::prefix('spj')->group(function () {
    Route::get('/projects', [SpjController::class, 'getProjects'])->name('api.spj.projects.index');
    Route::get('/projects/{id}', [SpjController::class, 'getProject'])->name('api.spj.projects.show');
    Route::get('/projects/{id}/documents', [SpjController::class, 'getDocuments'])->name('api.spj.documents.index');
    Route::get('/projects/{id}/documents/{docKey}', [SpjController::class, 'getDocument'])->name('api.spj.documents.show');
    Route::get('/projects/{id}/documents/{docKey}/download', [SpjController::class, 'downloadDocument'])->name('api.spj.documents.download');

    Route::put('/projects/{id}', [SpjController::class, 'updateProject'])->name('api.spj.projects.update');
    Route::put('/projects/{id}/documents/{docKey}', [SpjController::class, 'updateDocument'])->name('api.spj.documents.update');
    Route::post('/projects/{id}/documents/{docKey}/generate', [SpjController::class, 'generateDocument'])->name('api.spj.documents.generate');
    Route::post('/projects/{id}/bulk-generate', [SpjController::class, 'bulkGenerate'])->name('api.spj.bulk-generate');
    Route::post('/projects/{id}/documents/{docKey}/upload-external', [SpjController::class, 'uploadExternal'])->name('api.spj.documents.upload-external');
});
