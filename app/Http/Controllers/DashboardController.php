<?php

namespace App\Http\Controllers;

use App\Models\CustomerInvoiceItem;
use App\Models\Expenses;
use App\Models\Inventory;
use App\Models\Vendor;
use App\User;
use App\Branch;
use App\BankCash;
use App\RoleManage;
use App\Transaction;
use App\IncomeExpenseHead;
use App\IncomeExpenseType;
use App\IncomeExpenseGroup;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = array();

        // Cache and retrieve total branches with cache refresh every 1 second
        Cache::forget('total_branches');
        $data['total_branches'] = Branch::count();
        Cache::put('total_branches', $data['total_branches'], now()->addSeconds(1));

        // Cache and retrieve total income expense types
        Cache::forget('total_income_expense_types');
        $data['total_income_expense_types'] = IncomeExpenseType::count();
        Cache::put('total_income_expense_types', $data['total_income_expense_types'], now()->addSeconds(1));

        // Cache and retrieve total income expense groups
        Cache::forget('total_income_expense_groups');
        $data['total_income_expense_groups'] = IncomeExpenseGroup::count();
        Cache::put('total_income_expense_groups', $data['total_income_expense_groups'], now()->addSeconds(1));

        // Cache and retrieve total income expense heads
        Cache::forget('total_income_expense_heads');
        $data['total_income_expense_heads'] = IncomeExpenseHead::count();
        Cache::put('total_income_expense_heads', $data['total_income_expense_heads'], now()->addSeconds(1));

        // Cache and retrieve total bank cashes
        Cache::forget('total_bank_cashes');
        $data['total_bank_cashes'] = BankCash::count();
        Cache::put('total_bank_cashes', $data['total_bank_cashes'], now()->addSeconds(1));

        // Cache and retrieve total users
        Cache::forget('total_users');
        $data['total_users'] = User::count();
        Cache::put('total_users', $data['total_users'], now()->addSeconds(1));

        // Cache and retrieve total customers
        Cache::forget('total_customers');
        $data['total_customers'] = Customer::count();
        Cache::put('total_customers', $data['total_customers'], now()->addSeconds(1));

        // Cache and retrieve total vendors
        Cache::forget('total_vendors');
        $data['total_vendors'] = Vendor::count();
        Cache::put('total_vendors', $data['total_vendors'], now()->addSeconds(1));

        // Cache and retrieve total sales
        Cache::forget('total_sales');
        $data['total_sales'] = CustomerInvoiceItem::sum('amount');
        Cache::put('total_sales', $data['total_sales'], now()->addSeconds(1));

        // Cache and retrieve total expenses
        Cache::forget('total_expenses');
        $data['total_expenses'] = Expenses::sum('amount');
        Cache::put('total_expenses', $data['total_expenses'], now()->addSeconds(1));

        // Cache and retrieve total asset
        Cache::forget('total_asset');
        $data['total_asset'] = Inventory::count();
        Cache::put('total_asset', $data['total_asset'], now()->addSeconds(1));

        // Cache and retrieve total inventory
        Cache::forget('total_inventory');
        $data['total_inventory'] = Inventory::count();
        Cache::put('total_inventory', $data['total_inventory'], now()->addSeconds(1));

        // Cache and retrieve total unit cost
        Cache::forget('total_unit_cost');
        $data['total_unit_cost'] = CustomerInvoiceItem::join('inventories', 'customer_invoice_items.item_id', '=', 'inventories.id')
            ->selectRaw('SUM(customer_invoice_items.quantity * inventories.unit_cost) as total_cost')
            ->value('total_cost');
        Cache::put('total_unit_cost', $data['total_unit_cost'], now()->addSeconds(1));

        // Cache and retrieve total gross profit
        Cache::forget('total_gross_profit');
        $totalInventoryCost = CustomerInvoiceItem::join('inventories', 'customer_invoice_items.item_id', '=', 'inventories.id')
            ->selectRaw('SUM(customer_invoice_items.quantity * inventories.unit_cost) as total_cost')
            ->value('total_cost');
        $totalSales = CustomerInvoiceItem::sum('amount');
        $data['total_gross_profit'] = $totalSales - $totalInventoryCost;
        Cache::put('total_gross_profit', $data['total_gross_profit'], now()->addSeconds(1));

        // Cache and retrieve total net profit
        Cache::forget('total_net_profit');
        $data['total_net_profit'] = $data['total_gross_profit'] - $data['total_expenses'];
        Cache::put('total_net_profit', $data['total_net_profit'], now()->addSeconds(1));

        // Cache and retrieve total role manages
        Cache::forget('total_role_manages');
        $data['total_role_manages'] = RoleManage::count();
        Cache::put('total_role_manages', $data['total_role_manages'], now()->addSeconds(1));

        // Generate a list of years dynamically
        $year_start = (int) env('YEAR_START', 2016);
        $year_end = (int) env('YEAR_END', 2025);
        $years = [];
        for ($year_end; $year_end >= $year_start; $year_end--) {
            $years[$year_end] = $year_end;
        }
        $data['years'] = $years;

        return view('admin.dashboard.index', $data);
    }

    /**
     * This function retrun graph data for total voucher on dashboard
     *
     * @author      Md. Al-Mahmud <mamun120520@gmail.com>
     * @version     1.0
     * @see
     * @since       09/02/2022
     * Time         14:40:07
     * @param       $year
     * @return
     */
    public function graphTotalVoucher($year = null)
    {
        # code...
        // dr voucher, cr voucher, cnt voucher & journal voucher
        $total_dr_voucher = [];
        $total_cr_voucher = [];
        $total_cnt_voucher = [];
        $total_jnl_voucher = [];
        if ($year == null) {
            $year = date('Y');
        }
        $transaction_query = Transaction::query();
        $transaction_query->whereYear('voucher_date', $year);
        $transactions = $transaction_query->get();
        for ($i = 1; $i <= 12; $i++) {
            $drVoucherCount = $transactions->where('voucher_type', 'DV')->filter(function ($item, $key) use ($i) {
                $dateArray = explode('-', $item->voucher_date);
                return $i == (int) $dateArray[1];
            })->groupBy('voucher_no')->count();
            $crVoucherCount = $transactions->where('voucher_type', 'CV')->filter(function ($item, $key) use ($i) {
                $dateArray = explode('-', $item->voucher_date);
                return $i == (int) $dateArray[1];
            })->groupBy('voucher_no')->count();
            $jvVoucherCount = $transactions->where('voucher_type', 'JV')->filter(function ($item, $key) use ($i) {
                $dateArray = explode('-', $item->voucher_date);
                return $i == (int) $dateArray[1];
            })->groupBy('voucher_no')->count();
            $cntVoucherCount = $transactions->where('voucher_type', 'Contra')->filter(function ($item, $key) use ($i) {
                $dateArray = explode('-', $item->voucher_date);
                return $i == (int) $dateArray[1];
            })->groupBy('voucher_no')->count();

            array_push($total_dr_voucher, $drVoucherCount);
            array_push($total_cr_voucher, $crVoucherCount);
            array_push($total_cnt_voucher, $cntVoucherCount);
            array_push($total_jnl_voucher, $jvVoucherCount);
        }
        $data['total_dr_voucher'] = $total_dr_voucher;
        $data['total_cr_voucher'] = $total_cr_voucher;
        $data['total_cnt_voucher'] = $total_cnt_voucher;
        $data['total_jnl_voucher'] = $total_jnl_voucher;
        return response()->json($data);
    }
    #end

    /**
     * This function
     *
     * @author      Md. Al-Mahmud <mamun120520@gmail.com>
     * @version     1.0
     * @see
     * @since       09/02/2022
     * Time         15:02:32
     * @param
     * @return
     */
    public function graphProfitLoss($year = null)
    {
        // types
        $ledger_types = [102, 104, 114, 115, 105, 106, 116, 117];
        $types = IncomeExpenseType::whereIn('code', $ledger_types)->select('name', 'code')->get()->toArray();
        // transaction
        $transaction_query = Transaction::query();
        $transaction_query->whereHas('IncomeExpenseHead.IncomeExpenseType', function ($query) use ($ledger_types) {
            $query->whereIn('code', $ledger_types);
        });
        $transaction_query->with('IncomeExpenseHead', 'IncomeExpenseHead.IncomeExpenseType');
        $transaction_query->whereNotNull('income_expense_head_id');
        $transactions = $transaction_query->select(\DB::raw('CONCAT(YEAR(voucher_date), "-", MONTH(voucher_date))  AS month_date'), "id", 'income_expense_head_id', 'dr', 'cr')->get()->groupBy('IncomeExpenseHead.IncomeExpenseType.code');

        $revenue = [];
        $CostOfRevenue = [];
        $GrossProfit = [];
        $IndirectIncome = [];
        $IncomeFromOperation = [];
        $AdministrationExpenses = [];
        $IncomeBeforeTaxAndInterest = [];
        $FinancialExpense = [];
        $NetProfitOrLoss = [];
        for ($i = 1; $i <= 12; $i++) {
            $current_month_year = $year . "-" . $i;
            $items = [];
            foreach ($types as $type) {
                $amount = 0;
                if ($transactions->has($type['code'])) {
                    foreach ($transactions[$type['code']] as $transaction) {
                        if ($transaction->month_date === $current_month_year) {
                            // dr
                            if ($transaction->IncomeExpenseHead->type == 1) {
                                $amount += $transaction->dr - $transaction->cr;
                            } else {
                                $amount += $transaction->cr - $transaction->dr;
                            }
                        }
                    }
                }
                $items[$type['code']] = [
                    'name' => $type['name'],
                    'value' => $amount,
                ];
            }
            foreach ($ledger_types as $ledger_type) {
                if (!array_key_exists($ledger_type, $items)) {
                    $items[$ledger_type] = ['name' => '', 'value' => 0];
                }
            }
            // cost of revenue types
            $cost_of_revenue_types = [102, 104, 114, 115];
            //Revenue
            $revenue_code = 105;
            //Indirect income
            $indirect_income_code = 106;
            //administrative Expenses
            $administrative_expenses_code = 116;
            //Financial Expense
            $financial_expense_code = 117;

            $cost_of_revenue = 0;
            foreach ($cost_of_revenue_types as $cost_of_revenue_type) {
                $cost_of_revenue +=  $items[$cost_of_revenue_type]['value'];
            }

            $revenue_amount = $items[$revenue_code]['value'];
            $cost_of_revenue_amount = $cost_of_revenue;
            $gross_profit_amount = $revenue_amount - $cost_of_revenue;
            $indirect_income_amount = $items[$indirect_income_code]['value'];
            $income_from_operation_amount = $gross_profit_amount + $indirect_income_amount;
            $administration_expenses_amount = $items[$administrative_expenses_code]['value'];
            $income_before_tax_and_interest_amount = $income_from_operation_amount - $administration_expenses_amount;
            $financial_expense_amount = $items[$financial_expense_code]['value'];
            $income_after_tax_and_interest_amount = $income_before_tax_and_interest_amount - $financial_expense_amount;

            // push all items
            array_push($revenue, $revenue_amount);
            array_push($CostOfRevenue, $cost_of_revenue_amount);
            array_push($GrossProfit, $gross_profit_amount);
            array_push($IndirectIncome, $indirect_income_amount);
            array_push($IncomeFromOperation, $income_from_operation_amount);
            array_push($AdministrationExpenses, $administration_expenses_amount);
            array_push($IncomeBeforeTaxAndInterest, $income_before_tax_and_interest_amount);
            array_push($FinancialExpense, $financial_expense_amount);
            array_push($NetProfitOrLoss, $income_after_tax_and_interest_amount);
        }
        $data['revenue'] = $revenue;
        $data['CostOfRevenue'] = $CostOfRevenue;
        $data['GrossProfit'] = $GrossProfit;
        $data['IndirectIncome'] = $IndirectIncome;
        $data['IncomeFromOperation'] = $IncomeFromOperation;
        $data['AdministrationExpenses'] = $AdministrationExpenses;
        $data['IncomeBeforeTaxAndInterest'] = $IncomeBeforeTaxAndInterest;
        $data['FinancialExpense'] = $FinancialExpense;
        $data['NetProfitOrLoss'] = $NetProfitOrLoss;
        return $data;
    }
    #end



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('welcome');
    }
}
