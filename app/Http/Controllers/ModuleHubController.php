<?php

namespace App\Http\Controllers;

class ModuleHubController extends Controller
{
    public function sales()
    {
        return $this->render(__('root.hubs.sales'), __('root.hubs.sales_eyebrow'), __('root.hubs.sales_description'), [
            ['title' => __('root.hubs.customers'), 'items' => [
                ['customers.create', __('root.hubs.add_customer'), __('root.hubs.add_customer_description'), 'fa-user-plus'],
                ['customers.index', __('root.hubs.view_customers'), __('root.hubs.view_customers_description'), 'fa-users'],
            ]],
            ['title' => __('root.hubs.invoices_receipts'), 'items' => [
                ['customers.invoices.create', __('root.hubs.add_invoice'), __('root.hubs.add_invoice_description'), 'fa-file-invoice'],
                ['customers.invoices.index', __('root.hubs.view_invoices'), __('root.hubs.view_invoices_description'), 'fa-file-alt'],
                ['customers.receipts.create', __('root.hubs.add_receipt'), __('root.hubs.add_receipt_description'), 'fa-cash-register'],
                ['customers.receipts.view', __('root.hubs.view_receipts'), __('root.hubs.view_receipts_description'), 'fa-receipt'],
            ]],
        ]);
    }

    public function administration()
    {
        return $this->render(__('sections.administration.title'), __('sections.administration.eyebrow'), __('sections.administration.description'), [
            ['title' => __('sections.administration.organization'), 'items' => [
                ['branch', __('sections.administration.view_branches'), __('sections.administration.view_branches_description'), 'fa-project-diagram'],
                ['branch.create', __('sections.administration.add_branch'), __('sections.administration.add_branch_description'), 'fa-plus-circle'],
                ['settings.general', __('sections.administration.general_settings'), __('sections.administration.general_settings_description'), 'fa-sliders-h'],
                ['settings.system', __('sections.administration.system_settings'), __('sections.administration.system_settings_description'), 'fa-cog'],
            ]],
            ['title' => __('sections.administration.users_access'), 'items' => [
                ['user', __('sections.administration.view_users'), __('sections.administration.view_users_description'), 'fa-users'],
                ['user.create', __('sections.administration.add_user'), __('sections.administration.add_user_description'), 'fa-user-plus'],
                ['role-manage', __('sections.administration.view_roles'), __('sections.administration.view_roles_description'), 'fa-user-lock'],
                ['role-manage.create', __('sections.administration.add_role'), __('sections.administration.add_role_description'), 'fa-shield-alt'],
            ]],
            ['title' => __('sections.administration.language'), 'items' => [
                ['language', __('sections.administration.view_languages'), __('sections.administration.view_languages_description'), 'fa-language'],
                ['language.create', __('sections.administration.add_language'), __('sections.administration.add_language_description'), 'fa-plus-circle'],
            ]],
        ]);
    }

    public function accounting()
    {
        return $this->render(__('root.hubs.accounting'), __('root.hubs.accounting_eyebrow'), __('root.hubs.accounting_description'), [
            ['title' => __('root.hubs.income_expense_ledger'), 'items' => [
                ['income_expense_type', __('root.hubs.ledger_types'), __('root.hubs.ledger_types_description'), 'fa-list'],
                ['income_expense_type.create', __('root.hubs.add_ledger_type'), __('root.hubs.add_ledger_type_description'), 'fa-plus-circle'],
                ['income_expense_group', __('root.hubs.ledger_groups'), __('root.hubs.ledger_groups_description'), 'fa-layer-group'],
                ['income_expense_group.create', __('root.hubs.add_ledger_group'), __('root.hubs.add_ledger_group_description'), 'fa-folder-plus'],
                ['income_expense_head', __('root.hubs.ledger_names'), __('root.hubs.ledger_names_description'), 'fa-book'],
                ['income_expense_head.create', __('root.hubs.add_ledger_name'), __('root.hubs.add_ledger_name_description'), 'fa-file-medical'],
            ]],
            ['title' => __('root.hubs.journals_planning'), 'items' => [
                ['general-journal.create', __('root.hubs.add_journal_entry'), __('root.hubs.add_journal_entry_description'), 'fa-book-open'],
                ['general-journal.index', __('root.hubs.view_journal_entries'), __('root.hubs.view_journal_entries_description'), 'fa-list-alt'],
                ['payrolls.create', __('root.hubs.add_payroll'), __('root.hubs.add_payroll_description'), 'fa-user-tie'],
                ['payrolls.index', __('root.hubs.view_payrolls'), __('root.hubs.view_payrolls_description'), 'fa-users'],
                ['budgets.index', __('root.hubs.view_budgets'), __('root.hubs.view_budgets_description'), 'fa-clipboard-list'],
            ]],
            ['title' => __('root.hubs.vouchers'), 'items' => [
                ['cr_voucher.create', __('root.hubs.add_credit_voucher'), __('root.hubs.add_credit_voucher_description'), 'fa-arrow-right'],
                ['cr_voucher', __('root.hubs.view_credit_vouchers'), __('root.hubs.view_credit_vouchers_description'), 'fa-list'],
                ['dr_voucher.create', __('root.hubs.add_debit_voucher'), __('root.hubs.add_debit_voucher_description'), 'fa-arrow-left'],
                ['dr_voucher', __('root.hubs.view_debit_vouchers'), __('root.hubs.view_debit_vouchers_description'), 'fa-list'],
                ['jnl_voucher.create', __('root.hubs.add_journal_voucher'), __('root.hubs.add_journal_voucher_description'), 'fa-arrows-alt-h'],
                ['jnl_voucher', __('root.hubs.view_journal_vouchers'), __('root.hubs.view_journal_vouchers_description'), 'fa-list'],
                ['contra_voucher.create', __('root.hubs.add_contra_voucher'), __('root.hubs.add_contra_voucher_description'), 'fa-exchange-alt'],
                ['contra_voucher', __('root.hubs.view_contra_vouchers'), __('root.hubs.view_contra_vouchers_description'), 'fa-list'],
            ]],
        ]);
    }

    public function banking()
    {
        return $this->render(__('root.hubs.banking'), __('root.hubs.banking_eyebrow'), __('root.hubs.banking_description'), [
            ['title' => __('root.hubs.bank_cash_accounts'), 'items' => [
                ['bank_cash', __('root.hubs.view_bank_cash'), __('root.hubs.view_bank_cash_description'), 'fa-university'],
                ['bank_cash.create', __('root.hubs.add_bank_or_cash'), __('root.hubs.add_bank_or_cash_description'), 'fa-plus-circle'],
                ['bank_cash.trashed', __('root.hubs.trashed_accounts'), __('root.hubs.trashed_accounts_description'), 'fa-trash'],
            ]],
            ['title' => __('root.hubs.opening_balances'), 'items' => [
                ['initial_bank_cash_balance', __('root.hubs.bank_or_cash_balances'), __('root.hubs.bank_or_cash_balances_description'), 'fa-wallet'],
                ['initial_bank_cash_balance.create', __('root.hubs.add_opening_bank_balance'), __('root.hubs.add_opening_bank_balance_description'), 'fa-money-bill-wave'],
                ['initial_income_expense_head_balance', __('root.hubs.ledger_balances'), __('root.hubs.ledger_balances_description'), 'fa-book'],
                ['initial_income_expense_head_balance.create', __('root.hubs.add_opening_ledger_balance'), __('root.hubs.add_opening_ledger_balance_description'), 'fa-plus-circle'],
            ]],
        ]);
    }

    public function inventory()
    {
        return $this->render(__('root.hubs.inventory'), __('root.hubs.inventory_eyebrow'), __('root.hubs.inventory_description'), [
            ['title' => __('root.hubs.inventory_section'), 'items' => [
                ['inventories.create', __('root.hubs.add_inventory'), __('root.hubs.add_inventory_description'), 'fa-box-open'],
                ['inventories.index', __('root.hubs.view_inventory'), __('root.hubs.view_inventory_description'), 'fa-boxes'],
            ]],
            ['title' => __('root.hubs.assets'), 'items' => [
                ['reports.accounts.fixed_asset_schedule', __('root.hubs.fixed_asset_schedule'), __('root.hubs.fixed_asset_schedule_description'), 'fa-briefcase'],
            ]],
        ]);
    }

    private function render($title, $eyebrow, $description, $categories)
    {
        return view('admin.module-hub.index', compact('title', 'eyebrow', 'description', 'categories'));
    }
}
