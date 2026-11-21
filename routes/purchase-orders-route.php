<?php

use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\GoodsReceivedController;
use App\Http\Controllers\SupplierInvoiceController;
use App\Http\Controllers\SupplierInvoicePaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant'])->prefix('purchase-orders')->name('purchase-orders.')->group(function () {
    Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
    Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
    Route::post('/{id}/approve', [PurchaseOrderController::class, 'approve'])->whereNumber('id')->name('approve');
    Route::get('/{id}/goods-received/create', [GoodsReceivedController::class, 'create'])->whereNumber('id')->name('goods-received.create');
    Route::post('/{id}/goods-received', [GoodsReceivedController::class, 'store'])->whereNumber('id')->name('goods-received.store');
    Route::get('/vendors/{id}', [PurchaseOrderController::class, 'vendorDetails'])->name('vendors.show');
    Route::get('/inventory-items/{id}', [PurchaseOrderController::class, 'inventoryDetails'])->name('inventory-items.show');
    Route::get('/{id}/edit', [PurchaseOrderController::class, 'edit'])->whereNumber('id')->name('edit');
    Route::put('/{id}', [PurchaseOrderController::class, 'update'])->whereNumber('id')->name('update');
    Route::get('/{id}', [PurchaseOrderController::class, 'show'])->whereNumber('id')->name('show');
});

Route::middleware(['auth', 'tenant'])->prefix('goods-received')->name('goods-received.')->group(function () {
    Route::get('/', [GoodsReceivedController::class, 'index'])->name('index');
    Route::get('/{id}', [GoodsReceivedController::class, 'show'])->whereNumber('id')->name('show');
    Route::post('/{id}/confirm', [GoodsReceivedController::class, 'confirm'])->whereNumber('id')->name('confirm');
});

Route::middleware(['auth', 'tenant'])->prefix('supplier-invoices')->name('supplier-invoices.')->group(function () {
    Route::get('/', [SupplierInvoiceController::class, 'index'])->name('index');
    Route::get('/create/{purchaseOrderId}', [SupplierInvoiceController::class, 'create'])->whereNumber('purchaseOrderId')->name('create');
    Route::post('/', [SupplierInvoiceController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [SupplierInvoiceController::class, 'edit'])->whereNumber('id')->name('edit');
    Route::put('/{id}', [SupplierInvoiceController::class, 'update'])->whereNumber('id')->name('update');
    Route::post('/{id}/approve', [SupplierInvoiceController::class, 'approve'])->whereNumber('id')->name('approve');
    Route::post('/{id}/cancel', [SupplierInvoiceController::class, 'cancel'])->whereNumber('id')->name('cancel');
    Route::get('/{id}/pdf', [SupplierInvoiceController::class, 'pdf'])->whereNumber('id')->name('pdf');
    Route::get('/{id}', [SupplierInvoiceController::class, 'show'])->whereNumber('id')->name('show');
});

Route::middleware(['auth', 'tenant'])->prefix('supplier-invoices/{invoiceId}/payments')->name('supplier-invoice-payments.')->group(function () {
    Route::get('/create', [SupplierInvoicePaymentController::class, 'create'])->whereNumber('invoiceId')->name('create');
    Route::post('/', [SupplierInvoicePaymentController::class, 'store'])->whereNumber('invoiceId')->name('store');
});
Route::middleware(['auth', 'tenant'])->get('/supplier-invoice-payments', [SupplierInvoicePaymentController::class, 'index'])
    ->name('supplier-invoice-payments.index');

Route::middleware(['auth', 'tenant'])->get('/supplier-invoice-payments/{paymentId}/receipt', [SupplierInvoicePaymentController::class, 'receipt'])
    ->whereNumber('paymentId')->name('supplier-invoice-payments.receipt');
