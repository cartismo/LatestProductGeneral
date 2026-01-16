<?php

use Illuminate\Support\Facades\Route;
use Modules\LatestProductGeneral\Http\Controllers\Admin\SettingsController;

Route::prefix('modules/general/latest-product-general')->name('admin.general.latest.')->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/products', [SettingsController::class, 'addProduct'])->name('products.add');
    Route::delete('/products/{latestProduct}', [SettingsController::class, 'removeProduct'])->name('products.remove');
    Route::post('/products/reorder', [SettingsController::class, 'reorder'])->name('products.reorder');
    Route::post('/products/{latestProduct}/toggle', [SettingsController::class, 'toggle'])->name('products.toggle');
});