<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FlyerController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\OrderFeedbackController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/product/{product}', [HomeController::class, 'show'])->name('product.show');
Route::post('/product/{product}/analyze-pdf', [ProductController::class, 'analyzePdf'])->name('product.analyze-pdf');

// Rotas de upload de arte
Route::get('/produto/{product}/upload', [UploadController::class, 'create'])->name('upload.create')->middleware('auth');
Route::post('/produto/{product}/upload', [UploadController::class, 'store'])->name('upload.store')->middleware('auth');

Route::get('/flyers', [FlyerController::class, 'index'])->name('flyers.index');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::get('/cart/add/flyer', function () {
    return redirect()
        ->route('flyers.index')
        ->with('info', 'Use o formulário para personalizar seus flyers antes de adicioná-los ao carrinho.');
})->name('cart.add.flyer.redirect');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/add/flyer', [CartController::class, 'addFlyer'])->name('cart.add.flyer');
Route::delete('/cart/remove/{cartItemId}', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/checkout/{product}', [App\Http\Controllers\CheckoutController::class, 'productCheckout'])->name('checkout.product');

Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/failure', [CheckoutController::class, 'failure'])->name('checkout.failure');
    Route::get('/checkout/pending', [CheckoutController::class, 'pending'])->name('checkout.pending');

    Route::get('/cliente', [CustomerDashboardController::class, 'index'])->name('customer.dashboard');
    Route::put('/cliente/perfil', [CustomerDashboardController::class, 'updateProfile'])->name('customer.profile.update');
    Route::get('/cliente/pedidos/{order}', [CustomerDashboardController::class, 'showOrder'])->name('customer.orders.show');
    Route::get('/cliente/pedidos/{order}/avaliacao', [OrderFeedbackController::class, 'create'])->name('customer.feedback.create');
    Route::post('/cliente/pedidos/{order}/avaliacao', [OrderFeedbackController::class, 'store'])->name('customer.feedback.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
    Route::patch('/orders/{order}/status', [AdminController::class, 'updateOrderStatus'])->name('orders.update-status');
    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::get('/products/create', [AdminController::class, 'createProduct'])->name('products.create');
    Route::post('/products', [AdminController::class, 'storeProduct'])->name('products.store');
    Route::get('/products/{product}', [AdminController::class, 'showProduct'])->name('products.show');
    Route::get('/products/{product}/edit', [AdminController::class, 'editProduct'])->name('products.edit');
    Route::put('/products/{product}', [AdminController::class, 'updateProduct'])->name('products.update');
    Route::delete('/products/{product}', [AdminController::class, 'destroyProduct'])->name('products.destroy');
    Route::get('/categories', [App\Http\Controllers\CategoryController::class, 'index'])->name('categories');
    Route::get('/categories/create', [App\Http\Controllers\CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [App\Http\Controllers\CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
    Route::get('/pricing', [AdminController::class, 'pricing'])->name('pricing');
    Route::post('/pricing', [AdminController::class, 'updatePricing'])->name('pricing.update');
});

// require __DIR__.'/auth.php'; // Commented out as auth.php doesn't exist

Auth::routes();

Route::get('/debug-product/{product}', function (App\Models\Product $product) {
    return response()->json($product);
});

