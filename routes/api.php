<?php

use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\ConfigController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
*/

// // GET /api/config/{license_key} - Get config by license key in URL
// Route::get('config/{license_key}', [ConfigController::class, 'getConfig'])->name('api.config.get');

// // GET /api/config?license_key=xxx or ?hardware_id=xxx
// Route::get('config', [ConfigController::class, 'getConfig'])->name('api.config.query');


// POST /api/config - جلب الإعدادات عبر POST مع license_key أو hardware_id
Route::post('/config', [ConfigController::class, 'getConfig'])->name('api.config.post');

// ──────────────────────────────────────────────────────────────────────────
// راوتات المنتجات والتاجات العامة (بدون توثيق - عامة)
// ──────────────────────────────────────────────────────────────────────────
Route::prefix('{slug}')->group(function () {

    // جلب قائمة المنتجات لشركة معينة
    // GET /api/{slug}/vital-products/list
    Route::get('/vital-products/list', [ProductApiController::class, 'vitalProductList']);

    // جلب تفاصيل منتج واحد
    // GET /api/{slug}/vital-products/{productId}
    Route::get('/vital-products/{productId}', [ProductApiController::class, 'vitalProductShow']);

    // جلب قائمة التاجات لشركة معينة
    // GET /api/{slug}/tags
    Route::get('/tags', [ProductApiController::class, 'vitalTagsList']);

    // جلب معلومات الشركة العامة
    // GET /api/{slug}/info
    Route::get('/info', [ProductApiController::class, 'vitalCompanyInfo']);
});
