<?php

namespace App\Http\Controllers;

class ModuleHubController extends Controller
{
    public function sales()
    {
        return $this->render('Sales', 'Customer workspace', 'Manage customers, invoices, and receipts from one place.', [
            ['title' => 'Customers', 'items' => [
                ['customers.create', 'Add Customer', 'Create a new customer record.', 'fa-user-plus'],
                ['customers.index', 'View Customers', 'Review and manage your customers.', 'fa-users'],
            ]],
            ['title' => 'Invoices & Receipts', 'items' => [
                ['customers.invoices.create', 'Add Invoice', 'Create an invoice for a customer.', 'fa-file-invoice'],
                ['customers.invoices.index', 'View Invoices', 'Review customer invoices.', 'fa-file-alt'],
                ['customers.receipts.create', 'Add Receipt', 'Record a customer receipt.', 'fa-cash-register'],
                ['customers.receipts.view', 'View Receipts', 'Review customer receipts.', 'fa-receipt'],
            ]],
        ]);
    }

    public function administration()
    {
        return $this->render('Administration', 'Control center', 'Manage your organization, users, access, language, and settings.', [
            ['title' => 'Organization', 'items' => [
                ['branch', 'View Branches', 'Manage company branches.', 'fa-project-diagram'],
                ['branch.create', 'Add Branch', 'Create a new company branch.', 'fa-plus-circle'],
                ['settings.general', 'General Settings', 'Configure general company settings.', 'fa-sliders-h'],
                ['settings.system', 'System Settings', 'Manage system and accounting settings.', 'fa-cog'],
            ]],
            ['title' => 'Users & Access', 'items' => [
                ['user', 'View Users', 'Review and manage system users.', 'fa-users'],
                ['user.create', 'Add User', 'Create a new system user.', 'fa-user-plus'],
                ['role-manage', 'View Roles', 'Manage roles and permissions.', 'fa-user-lock'],
                ['role-manage.create', 'Add Role', 'Create a new access role.', 'fa-shield-alt'],
            ]],
            ['title' => 'Language', 'items' => [
                ['language', 'View Languages', 'Manage available application languages.', 'fa-language'],
                ['language.create', 'Add Language', 'Create a new application language.', 'fa-plus-circle'],
            ]],
        ]);
    }

    public function accounting()
    {
        return $this->render('Accounting', 'Core finance workspace', 'Manage ledgers, journals, vouchers, payroll, and budgets.', [
            ['title' => 'Income & Expense Ledger', 'items' => [
                ['income_expense_type', 'Ledger Types', 'View income and expense types.', 'fa-list'],
                ['income_expense_type.create', 'Add Ledger Type', 'Create an income or expense type.', 'fa-plus-circle'],
                ['income_expense_group', 'Ledger Groups', 'View income and expense groups.', 'fa-layer-group'],
                ['income_expense_group.create', 'Add Ledger Group', 'Create a new ledger group.', 'fa-folder-plus'],
                ['income_expense_head', 'Ledger Names', 'View ledger names and accounts.', 'fa-book'],
                ['income_expense_head.create', 'Add Ledger Name', 'Create a new ledger name.', 'fa-file-medical'],
            ]],
            ['title' => 'Journals & Planning', 'items' => [
                ['general-journal.create', 'Add Journal Entry', 'Record a general journal entry.', 'fa-book-open'],
                ['general-journal.index', 'View Journal Entries', 'Review general journal entries.', 'fa-list-alt'],
                ['payrolls.create', 'Add Payroll', 'Create a payroll record.', 'fa-user-tie'],
                ['payrolls.index', 'View Payrolls', 'Review payroll records.', 'fa-users'],
                ['budgets.index', 'View Budgets', 'Review planned financial activity.', 'fa-clipboard-list'],
            ]],
            ['title' => 'Vouchers', 'items' => [
                ['cr_voucher.create', 'Add Credit Voucher', 'Create a credit voucher.', 'fa-arrow-right'],
                ['cr_voucher', 'View Credit Vouchers', 'Review credit vouchers.', 'fa-list'],
                ['dr_voucher.create', 'Add Debit Voucher', 'Create a debit voucher.', 'fa-arrow-left'],
                ['dr_voucher', 'View Debit Vouchers', 'Review debit vouchers.', 'fa-list'],
                ['jnl_voucher.create', 'Add Journal Voucher', 'Create a journal voucher.', 'fa-arrows-alt-h'],
                ['jnl_voucher', 'View Journal Vouchers', 'Review journal vouchers.', 'fa-list'],
                ['contra_voucher.create', 'Add Contra Voucher', 'Create a contra voucher.', 'fa-exchange-alt'],
                ['contra_voucher', 'View Contra Vouchers', 'Review contra vouchers.', 'fa-list'],
            ]],
        ]);
    }

    public function banking()
    {
        return $this->render('Banking & Cash', 'Treasury workspace', 'Manage bank and cash accounts, balances, and opening entries.', [
            ['title' => 'Bank & Cash Accounts', 'items' => [
                ['bank_cash', 'View Bank & Cash', 'Review your bank and cash accounts.', 'fa-university'],
                ['bank_cash.create', 'Add Bank or Cash', 'Create a new bank or cash account.', 'fa-plus-circle'],
                ['bank_cash.trashed', 'Trashed Accounts', 'Restore or permanently remove accounts.', 'fa-trash'],
            ]],
            ['title' => 'Opening Balances', 'items' => [
                ['initial_bank_cash_balance', 'Bank or Cash Balances', 'Manage opening bank and cash balances.', 'fa-wallet'],
                ['initial_bank_cash_balance.create', 'Add Opening Bank Balance', 'Record an opening bank or cash balance.', 'fa-money-bill-wave'],
                ['initial_income_expense_head_balance', 'Ledger Balances', 'Manage opening ledger balances.', 'fa-book'],
                ['initial_income_expense_head_balance.create', 'Add Opening Ledger Balance', 'Record an opening ledger balance.', 'fa-plus-circle'],
            ]],
        ]);
    }

    public function inventory()
    {
        return $this->render('Inventory & Assets', 'Stock and asset workspace', 'Manage inventory records and review fixed asset schedules.', [
            ['title' => 'Inventory', 'items' => [
                ['inventories.create', 'Add Inventory', 'Create a new inventory record.', 'fa-box-open'],
                ['inventories.index', 'View Inventory', 'Review inventory and quantities on hand.', 'fa-boxes'],
            ]],
            ['title' => 'Assets', 'items' => [
                ['reports.accounts.fixed_asset_schedule', 'Fixed Asset Schedule', 'Review assets and depreciation schedules.', 'fa-briefcase'],
            ]],
        ]);
    }

    private function render($title, $eyebrow, $description, $categories)
    {
        return view('admin.module-hub.index', compact('title', 'eyebrow', 'description', 'categories'));
    }
}
