<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth'], function () {
    Route::resource('customers', CustomerController::class)->except([
        'show'
    ]);

    Route::resource('customers/invoices', CustomerInvoiceController::class)->names("customers.invoices");

    Route::resource('customers/receipts', CustomerReceiptController::class)->names("customers.receipts");

    Route::resource('general-journal', GeneralJournalController::class);

    Route::resource('payrolls', PayrollController::class);

    Route::resource('budgets', BudgetController::class);

    Route::get('/reports/accounts/payroll', [\App\Http\Controllers\Reports\PayrollBudgetReportController::class, 'payroll'])
        ->name('reports.accounts.payroll');
    Route::post('/reports/accounts/payroll/report', [\App\Http\Controllers\Reports\PayrollBudgetReportController::class, 'payrollReport'])
        ->name('reports.accounts.payroll.report');
    Route::get('/reports/accounts/budget', [\App\Http\Controllers\Reports\PayrollBudgetReportController::class, 'budget'])
        ->name('reports.accounts.budget');
    Route::post('/reports/accounts/budget/report', [\App\Http\Controllers\Reports\PayrollBudgetReportController::class, 'budgetReport'])
        ->name('reports.accounts.budget.report');

    Route::resource('inventories', InventoryController::class);

    Route::resource('vendors', VendorController::class);

    Route::resource('vouchers', VoucherController::class);

    Route::resource('payments', PaymentVoucherController::class);

    Route::resource('expenses', ExpenseController::class);

});
