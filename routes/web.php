<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeliveryRouteController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDeliveryController;
use App\Http\Controllers\OrderPaymentStatusController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::middleware(['auth'])->group(function () {
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('warehouses', WarehouseController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('brands', BrandController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('stocks', StockController::class)->only(['index']);
    Route::resource('inventory-movements', InventoryMovementController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::get('deliveries', [DeliveryRouteController::class, 'index'])->name('deliveries.index');
    Route::post('delivery-routes', [DeliveryRouteController::class, 'store'])->name('delivery-routes.store');
    Route::get('delivery-routes/{deliveryRoute}', [DeliveryRouteController::class, 'show'])->name('delivery-routes.show');
    Route::resource('orders', OrderController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::patch('/orders/{order}/payment-status', [OrderPaymentStatusController::class, 'update'])
        ->name('orders.payment-status.update');
    Route::post('/orders/{order}/deliver', [OrderDeliveryController::class, 'deliver'])
        ->name('orders.deliver');
});
