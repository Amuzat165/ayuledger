<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportDataSeeder extends Seeder
{
    private $now;

    public function run()
    {
        $this->now = now();

        $branches = $this->seedBranches();
        $types = $this->seedIncomeExpenseTypes();
        $groups = $this->seedGroups();
        $heads = $this->seedHeads($types, $groups);
        $cashAccounts = $this->seedCashAccounts();
        $customers = $this->seedCustomers($branches, $types);
        $vendors = $this->seedVendors();
        $inventoryTypes = $this->seedInventoryTypes();
        $inventory = $this->seedInventory($inventoryTypes, $branches);
        $this->seedCustomerInvoices($customers, $inventory);
        $this->seedVendorInvoices($vendors, $inventory);
        $this->seedPayments($customers, $vendors, $cashAccounts);
        $this->seedExpenses($types, $heads);
        $this->seedPayrolls();
        $this->seedBudgets();
        $this->seedGeneralJournals();
        $this->seedTransactions($branches, $heads, $cashAccounts);
    }

    private function seedBranches()
    {
        $branches = [];

        foreach ([
            'HQ' => 'Kaduna Branch',
            'Head Office' => 'Abuja Head Office',
            'Rajshahi Branch' => 'Port Harcourt Branch',
            'Sirajgonj Branch' => 'Ibadan Branch',
            'Bogra Branch' => 'Enugu Branch',
        ] as $oldName => $newName) {
            DB::table('branches')->where('name', $oldName)->update([
                'name' => $newName,
                'location' => str_replace(' Branch', '', $newName) . ', Nigeria',
            ]);
        }

        foreach ([
            ['name' => 'Abuja Head Office', 'location' => 'Abuja, Nigeria', 'description' => 'Main office'],
            ['name' => 'Lagos Branch', 'location' => 'Lagos, Nigeria', 'description' => 'Lagos branch'],
            ['name' => 'Kano Branch', 'location' => 'Kano, Nigeria', 'description' => 'Kano branch'],
        ] as $branch) {
            $this->upsert('branches', ['name' => $branch['name']], $branch);
            $branches[$branch['name']] = $this->id('branches', ['name' => $branch['name']]);
        }

        // The report branch selector uses this shared cache key.
        Cache::forget('branches');

        return $branches;
    }

    private function seedIncomeExpenseTypes()
    {
        $types = [];
        foreach ([
            ['name' => 'Inventories', 'code' => 101],
            ['name' => 'Revenue', 'code' => 105],
            ['name' => 'Account Payable', 'code' => 107],
            ['name' => 'Administrative Expense', 'code' => 116],
            ['name' => 'Cash and Bank', 'code' => 123],
        ] as $type) {
            $this->upsert('income_expense_types', ['code' => $type['code']], array_merge($type, [
                'created_by' => 'Report seed',
            ]));
            $types[$type['name']] = $this->id('income_expense_types', ['code' => $type['code']]);
        }

        return $types;
    }

    private function seedGroups()
    {
        $groups = [];
        foreach ([
            ['name' => 'Inventory', 'description' => 'Inventory accounts'],
            ['name' => 'Sales', 'description' => 'Sales accounts'],
            ['name' => 'Operating Expenses', 'description' => 'Operating expense accounts'],
        ] as $group) {
            $this->upsert('income_expense_groups', ['name' => $group['name']], $group);
            $groups[$group['name']] = $this->id('income_expense_groups', ['name' => $group['name']]);
        }

        return $groups;
    }

    private function seedHeads($types, $groups)
    {
        $heads = [];
        $rows = [
            ['name' => 'Inventory Purchases', 'type' => 1, 'type_id' => 'Inventories', 'group_id' => 'Inventory'],
            ['name' => 'Sales Revenue', 'type' => 0, 'type_id' => 'Revenue', 'group_id' => 'Sales'],
            ['name' => 'Accounts Payable', 'type' => 0, 'type_id' => 'Account Payable', 'group_id' => 'Operating Expenses'],
            ['name' => 'Office Expense', 'type' => 1, 'type_id' => 'Administrative Expense', 'group_id' => 'Operating Expenses'],
            ['name' => 'Cash Account', 'type' => 1, 'type_id' => 'Cash and Bank', 'group_id' => 'Operating Expenses'],
        ];

        foreach ($rows as $row) {
            $this->upsert('income_expense_heads', ['name' => $row['name']], [
                'unit' => 'Amount',
                'income_expense_type_id' => (string) $types[$row['type_id']],
                'income_expense_group_id' => (string) $groups[$row['group_id']],
                'type' => $row['type'],
                'created_by' => 'Report seed',
            ]);
            $heads[$row['name']] = $this->id('income_expense_heads', ['name' => $row['name']]);
        }

        return $heads;
    }

    private function seedCashAccounts()
    {
        $accounts = [];
        foreach ([
            ['name' => 'Cash', 'account_number' => null],
            ['name' => 'Main Bank', 'account_number' => 'NG-0001'],
        ] as $account) {
            $this->upsert('bank_cashes', ['name' => $account['name']], $account);
            $accounts[$account['name']] = $this->id('bank_cashes', ['name' => $account['name']]);
        }

        return $accounts;
    }

    private function seedCustomers($branches, $types)
    {
        $customers = [];
        foreach ([
            ['name' => 'Alpha Builders Ltd', 'email' => 'alpha@example.test', 'phone' => '08030000001', 'branch' => 'Abuja Head Office', 'terms' => 'Pay in number of days', 'days' => 30],
            ['name' => 'Beta Supplies Ltd', 'email' => 'beta@example.test', 'phone' => '08030000002', 'branch' => 'Lagos Branch', 'terms' => 'COD', 'days' => null],
            ['name' => 'Gamma Engineering', 'email' => 'gamma@example.test', 'phone' => '08030000003', 'branch' => 'Kano Branch', 'terms' => 'Pay in number of days', 'days' => 14],
        ] as $customer) {
            $this->upsert('customers', ['email' => $customer['email']], [
                'name' => $customer['name'],
                'phone' => $customer['phone'],
                'address' => 'Test business address',
                'income_expense_type_id' => $types['Revenue'],
                'terms_of_payment' => $customer['terms'],
                'terms_of_payment_in_days' => $customer['days'],
                'branch_id' => $branches[$customer['branch']],
                'status' => 1,
            ]);
            $customers[$customer['email']] = $this->id('customers', ['email' => $customer['email']]);
        }

        return $customers;
    }

    private function seedVendors()
    {
        $vendors = [];
        foreach ([
            ['name' => 'BuildRight Materials', 'reg_number' => 'V-001', 'phone_number' => '08040000001'],
            ['name' => 'Office World Nigeria', 'reg_number' => 'V-002', 'phone_number' => '08040000002'],
        ] as $vendor) {
            $this->upsert('vendors', ['reg_number' => $vendor['reg_number']], array_merge($vendor, [
                'address' => 'Test vendor address',
                'account' => 'Accounts Payable',
                'terms_of_payment' => '30 days',
            ]));
            $vendors[$vendor['reg_number']] = $this->id('vendors', ['reg_number' => $vendor['reg_number']]);
        }

        return $vendors;
    }

    private function seedInventoryTypes()
    {
        $types = [];
        foreach (['Building Materials', 'Office Supplies'] as $name) {
            $this->upsert('inventory_types', ['name' => $name], []);
            $types[$name] = $this->id('inventory_types', ['name' => $name]);
        }

        return $types;
    }

    private function seedInventory($types, $branches)
    {
        $inventory = [];
        foreach ([
            ['type' => 'Building Materials', 'description' => 'Premium cement', 'sub_item' => '50kg bag', 'unit' => 'Bag', 'cost' => 6500, 'sale' => 7500, 'quantity' => 100],
            ['type' => 'Building Materials', 'description' => 'Reinforcement steel', 'sub_item' => '12mm rod', 'unit' => 'Piece', 'cost' => 4500, 'sale' => 5200, 'quantity' => 75],
            ['type' => 'Office Supplies', 'description' => 'A4 paper', 'sub_item' => '500 sheet ream', 'unit' => 'Ream', 'cost' => 7000, 'sale' => 8500, 'quantity' => 40],
        ] as $item) {
            $this->upsert('inventories', ['description' => $item['description']], [
                'type_id' => $types[$item['type']],
                'branch_id' => $branches['Abuja Head Office'],
                'description' => $item['description'],
                'sub_item' => $item['sub_item'],
                'unit_of_measurement' => $item['unit'],
                'unit_cost' => $item['cost'],
                'sale_price' => $item['sale'],
                'quantity_at_hand' => $item['quantity'],
                'account' => 'Inventory',
            ]);
            $inventory[$item['description']] = $this->id('inventories', ['description' => $item['description']]);
        }

        return $inventory;
    }

    private function seedCustomerInvoices($customers, $inventory)
    {
        $invoice = $this->invoice('customer_invoices', 'invoice_number', 'CINV-TEST-001', [
            'customer_id' => $customers['alpha@example.test'],
            'invoice_date' => '2026-01-15',
            'exchange_rate' => 1,
        ]);
        $this->invoiceItem('customer_invoice_items', $invoice, $inventory['Premium cement'], 20, 7500);

        $invoice = $this->invoice('customer_invoices', 'invoice_number', 'CINV-TEST-002', [
            'customer_id' => $customers['beta@example.test'],
            'invoice_date' => '2026-02-10',
            'exchange_rate' => 1,
        ]);
        $this->invoiceItem('customer_invoice_items', $invoice, $inventory['A4 paper'], 5, 8500);
    }

    private function seedVendorInvoices($vendors, $inventory)
    {
        $invoice = $this->invoice('vendor_invoices', 'invoice_number', 'VINV-TEST-001', [
            'vendor_id' => $vendors['V-001'],
            'invoice_date' => '2026-01-05',
            'exchange_rate' => 1,
        ]);
        $this->invoiceItem('vendor_invoice_items', $invoice, $inventory['Premium cement'], 30, 6500);

        $invoice = $this->invoice('vendor_invoices', 'invoice_number', 'VINV-TEST-002', [
            'vendor_id' => $vendors['V-002'],
            'invoice_date' => '2026-02-01',
            'exchange_rate' => 1,
        ]);
        $this->invoiceItem('vendor_invoice_items', $invoice, $inventory['A4 paper'], 10, 7000);
    }

    private function seedPayments($customers, $vendors, $cashAccounts)
    {
        $customerInvoice = $this->id('customer_invoices', ['invoice_number' => 'CINV-TEST-001']);
        $vendorInvoice = $this->id('vendor_invoices', ['invoice_number' => 'VINV-TEST-001']);

        $this->upsert('customer_payments', ['invoice_id' => $customerInvoice], [
            'customer_id' => $customers['alpha@example.test'],
            'invoice_id' => $customerInvoice,
            'total_amount' => 150000,
            'amount_paid' => 75000,
            'balance_due' => 75000,
            'payment_mode_id' => $cashAccounts['Main Bank'],
        ]);
        $this->upsert('vendor_payments', ['invoice_id' => $vendorInvoice], [
            'vendor_id' => $vendors['V-001'],
            'invoice_id' => $vendorInvoice,
            'total_amount' => 195000,
            'amount_paid' => 100000,
            'balance_due' => 95000,
            'payment_mode_id' => $cashAccounts['Main Bank'],
        ]);
    }

    private function seedExpenses($types, $heads)
    {
        $expenseType = $this->id('expenses_types', ['name' => 'Utilities']);
        $this->upsert('expenses_types', ['name' => 'Utilities'], []);
        $expenseType = $this->id('expenses_types', ['name' => 'Utilities']);

        foreach ([
            ['date' => '2026-01-20', 'vendor' => 'Power Company', 'amount' => 45000, 'description' => 'January electricity'],
            ['date' => '2026-02-20', 'vendor' => 'Internet Provider', 'amount' => 30000, 'description' => 'February internet service'],
        ] as $expense) {
            $this->upsert('expenses', ['expense_date' => $expense['date'], 'vendor' => $expense['vendor']], [
                'type_id' => $expenseType,
                'account' => 'Office Expense',
                'vendor' => $expense['vendor'],
                'expense_date' => $expense['date'],
                'amount' => $expense['amount'],
                'description' => $expense['description'],
            ]);
        }
    }

    private function seedPayrolls()
    {
        foreach ([
            ['employee' => 'Amina Yusuf', 'basic' => 180000],
            ['employee' => 'Daniel Okoro', 'basic' => 220000],
        ] as $employee) {
            $gross = $employee['basic'] + 30000 + 20000 + 15000 + 10000;
            $deduction = 15000;
            $this->upsert('payrolls', ['employee' => $employee['employee']], [
                'employee' => $employee['employee'],
                'basic_salary' => $employee['basic'],
                'housing_allowance' => 30000,
                'transport_allowance' => 20000,
                'medical_allowance' => 15000,
                'other_allowance' => 10000,
                'gross_salary' => $gross,
                'deduction_payee' => $deduction,
                'pension' => 10000,
                'net_pay' => $gross - $deduction - 10000,
            ]);
        }
    }

    private function seedBudgets()
    {
        foreach ([
            ['account' => 'Sales Revenue', 'amount' => 1500000],
            ['account' => 'Office Expense', 'amount' => 250000],
            ['account' => 'Inventory Purchases', 'amount' => 800000],
        ] as $budget) {
            $values = array_fill_keys([
                'january', 'february', 'march', 'april', 'may', 'june',
                'july', 'august', 'september', 'october', 'november', 'december',
            ], $budget['amount'] / 12);
            $values['annual_amount'] = $budget['amount'];
            $this->upsert('budgets', ['account' => $budget['account']], $values);
        }
    }

    private function seedGeneralJournals()
    {
        foreach ([
            ['account' => 'Sales Revenue', 'debit' => 0, 'credit' => 150000, 'memo' => 'January sales'],
            ['account' => 'Office Expense', 'debit' => 45000, 'credit' => 0, 'memo' => 'January utilities'],
            ['account' => 'Inventory Purchases', 'debit' => 195000, 'credit' => 0, 'memo' => 'January stock purchase'],
        ] as $journal) {
            $this->upsert('general_journals', ['account' => $journal['account'], 'memo' => $journal['memo']], array_merge($journal, [
                'customer_vendor' => 'Report seed',
            ]));
        }
    }

    private function seedTransactions($branches, $heads, $cashAccounts)
    {
        $rows = [
            [1, 'DV', '2026-01-05', $heads['Inventory Purchases'], $cashAccounts['Main Bank'], 195000, null, 'Inventory purchase'],
            [1, 'CV', '2026-01-15', $heads['Sales Revenue'], $cashAccounts['Main Bank'], null, 150000, 'Customer sale'],
            [2, 'DV', '2026-02-20', $heads['Office Expense'], $cashAccounts['Cash'], 45000, null, 'Office utilities'],
            [2, 'CV', '2026-02-20', $heads['Cash Account'], $cashAccounts['Cash'], null, 45000, 'Expense payment'],
        ];

        foreach ($rows as $row) {
            $this->upsert('transactions', [
                'voucher_no' => $row[0],
                'voucher_type' => $row[1],
                'voucher_date' => $row[2],
                'income_expense_head_id' => $row[3],
                'particulars' => $row[7],
            ], [
                'voucher_no' => $row[0],
                'branch_id' => $branches['Abuja Head Office'],
                'income_expense_head_id' => $row[3],
                'bank_cash_id' => $row[4],
                'voucher_type' => $row[1],
                'voucher_date' => $row[2],
                'dr' => $row[5],
                'cr' => $row[6],
                'particulars' => $row[7],
                'created_by' => 'Report seed',
            ]);
        }
    }

    private function invoice($table, $key, $number, $values)
    {
        $this->upsert($table, [$key => $number], array_merge($values, [$key => $number]));

        return $this->id($table, [$key => $number]);
    }

    private function invoiceItem($table, $invoiceId, $itemId, $quantity, $rate)
    {
        $this->upsert($table, [
            'invoice_id' => $invoiceId,
            'item_id' => $itemId,
        ], [
            'invoice_id' => $invoiceId,
            'item_id' => $itemId,
            'description' => 'Seeded report item',
            'rate' => $rate,
            'quantity' => $quantity,
            'amount' => $quantity * $rate,
        ]);
    }

    private function upsert($table, $where, $values)
    {
        DB::table($table)->updateOrInsert($where, array_merge($values, [
            'updated_at' => $this->now,
            'created_at' => $this->now,
        ]));
    }

    private function id($table, $where)
    {
        return DB::table($table)->where($where)->value('id');
    }
}
