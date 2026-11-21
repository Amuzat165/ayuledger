<?php

namespace App;

use App\IncomeExpenseHead;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class Transaction extends TenantScopedModel
{
    use Notifiable;
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'voucher_no',
        'branch_id',
        'income_expense_head_id',
        'bank_cash_id',
        'cheque_number',
        'voucher_type',
        'voucher_date',
        'dr',
        'cr',
        'particulars',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function Branch()
    {
        return $this->belongsTo('App\Branch', 'branch_id');
    }

    public function IncomeExpenseHead()
    {
        return $this->belongsTo('App\IncomeExpenseHead', 'income_expense_head_id');
    }

    public function BankCash()
    {
        return $this->belongsTo('App\BankCash', 'bank_cash_id');
    }

    public function isDebitByIncomeExpenseHeadID($id)
    {
        return IncomeExpenseHead::find($id)->type;
    }

    public function GetBankCashBalanceByBranchBankCashIdDate($branch_id, $bankCashID = null, $from_date = null, $to_date = null)
    {
        $hasFromDate = $from_date !== null;
        $hasToDate = $to_date !== null;
        if (!is_numeric($bankCashID) || $bankCashID <= 0 || !is_numeric($branch_id) || $branch_id < 0 || $hasFromDate !== $hasToDate) {
            return 0;
        }
        if ($from_date === '' || $to_date === '') {
            return 0;
        }

        $query = DB::table('transactions')
            ->join('bank_cashes', 'transactions.bank_cash_id', '=', 'bank_cashes.id')
            ->whereNull('transactions.deleted_at');

        if ($branch_id > 0) {
            $query->where('transactions.branch_id', $branch_id);
        }
        if ($bankCashID > 0) {
            $query->where('transactions.bank_cash_id', $bankCashID);
        }
        if ($hasFromDate) {
            $query->whereBetween('transactions.voucher_date', [
                date('Y-m-d', strtotime($from_date)),
                date('Y-m-d', strtotime($to_date)),
            ]);
        }

        return $query->selectRaw('IFNULL(SUM(transactions.cr), 0) - IFNULL(SUM(transactions.dr), 0) AS Balance')->value('Balance') ?? 0;
    }

    public function GetUniqueIncomeExpenseHeadByBranch($branchID, $from_date = null, $to_date = null)
    {
        $hasFromDate = $from_date !== null;
        $hasToDate = $to_date !== null;
        if (!is_numeric($branchID) || $branchID < 0 || $hasFromDate !== $hasToDate || ($branchID == 0 && !$hasFromDate)) {
            return [];
        }
        if ($from_date === '' || $to_date === '') {
            return [];
        }

        $query = DB::table('transactions')
            ->join('income_expense_heads', 'transactions.income_expense_head_id', '=', 'income_expense_heads.id')
            ->whereNull('transactions.deleted_at')
            ->whereNotNull('transactions.income_expense_head_id');

        if ($branchID > 0) {
            $query->where('transactions.branch_id', $branchID);
        }
        if ($hasFromDate && $hasToDate) {
            $query->whereBetween('transactions.voucher_date', [$from_date, $to_date]);
        }

        return $query->select('transactions.income_expense_head_id', 'income_expense_heads.name', 'income_expense_heads.type')
            ->distinct()
            ->orderBy('transactions.income_expense_head_id')
            ->get()
            ->all();
    }

    public function GetBalanceByBranchIdIncExpIdTypeId($branch_id, $head_id, $type_id, $from_date = null, $to_date = null)
    {
        $query = DB::table('transactions')
            ->join('income_expense_heads', 'transactions.income_expense_head_id', '=', 'income_expense_heads.id')
            ->where('transactions.branch_id', $branch_id)
            ->where('transactions.income_expense_head_id', $head_id)
            ->where('income_expense_heads.type', $type_id)
            ->whereNull('transactions.deleted_at');

        if (!empty($from_date)) {
            $query->whereBetween('transactions.voucher_date', [
                date('Y-m-d', strtotime($from_date)),
                date('Y-m-d', strtotime($to_date)),
            ]);
        }

        $details = $query->select('transactions.dr', 'transactions.cr')->get();
        $balance = 0;
        foreach ($details as $detail) {
            $balance += $type_id == 1 ? $detail->dr - $detail->cr : $detail->cr - $detail->dr;
        }

        return $balance;
    }

    public function convert_number_to_words($number)
    {
        $hyphen = '-';
        $conjunction = ' and ';
        $separator = ', ';
        $negative = 'negative ';
        $decimal = ' point ';
        $dictionary = array(
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'fourty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety',
            100 => 'hundred',
            1000 => 'thousand',
            1000000 => 'million',
            1000000000 => 'billion',
            1000000000000 => 'trillion',
            1000000000000000 => 'quadrillion',
            1000000000000000000 => 'quintillion'
        );

        if (!is_numeric($number)) {
            return false;
        }

        if (($number >= 0 && (int)$number < 0) || (int)$number < 0 - PHP_INT_MAX) {
            // overflow
            trigger_error(
                'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
                E_USER_WARNING
            );
            return false;
        }

        if ($number < 0) {
            return $negative . Self::convert_number_to_words(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int)($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . Self::convert_number_to_words($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int)($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = Self::convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= Self::convert_number_to_words($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string)$fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return ucfirst($string);
    }

    public function convert_money_format($number)
    {
        return number_format($number);
    }

    public function getBalanceByIncExpHeadTypeIdBranchesTwoDate($IncomeExpenseTypeId, $transaction_unique_branches, $start_from, $start_to, $end_from, $end_to)
    {
        $IncomeExpenseHeads = IncomeExpenseType::find($IncomeExpenseTypeId)->IncomeExpenseHeads;

        $start_balance = 0;
        $end_balance = 0;
        $headBalanceStart = array();
        $headBalanceEnd = array();
        foreach ($transaction_unique_branches as $transaction_unique_branch) {
            foreach ($IncomeExpenseHeads as $incomeExpenseHead) {

                $start_balance += $startBalance = $this->GetBalanceByBranchIdIncExpIdTypeId($transaction_unique_branch->branch_id, $incomeExpenseHead->id, $incomeExpenseHead->type, $start_from, $start_to);
                $end_balance += $endBalance = $this->GetBalanceByBranchIdIncExpIdTypeId($transaction_unique_branch->branch_id, $incomeExpenseHead->id, $incomeExpenseHead->type, $end_from, $end_to);

                if (array_key_exists($incomeExpenseHead->name, $headBalanceStart)) {
                    $headBalanceStart[$incomeExpenseHead->name] += $startBalance;
                } else {
                    $headBalanceStart[$incomeExpenseHead->name] = $startBalance;
                }

                if (array_key_exists($incomeExpenseHead->name, $headBalanceEnd)) {
                    $headBalanceEnd[$incomeExpenseHead->name] += $endBalance;
                } else {
                    $headBalanceEnd[$incomeExpenseHead->name] = $endBalance;
                }
            }
        }

        $balance = array(
            'balance' => array(
                'start_balance' => $start_balance,
                'end_balance' => $end_balance
            ),
            'headDetails' => array(
                'StartDate' => $headBalanceStart,
                'EndDate' => $headBalanceEnd,
                'TotalBalance' => array(
                    'start_balance' => $start_balance,
                    'end_balance' => $end_balance
                ),
            )
        );
        return $balance;
    }

    public function getBalanceByIncExpHeadGroupIdBranchesTwoDate($IncomeExpenseGroupId, $transaction_unique_branches, $start_from, $start_to, $end_from, $end_to)
    {

        $IncomeExpenseHeads = IncomeExpenseGroup::find($IncomeExpenseGroupId)->IncomeExpenseHeads;

        $start_balance = 0;
        $end_balance = 0;
        $headBalanceStart = array();
        $headBalanceEnd = array();
        foreach ($transaction_unique_branches as $transaction_unique_branch) {
            foreach ($IncomeExpenseHeads as $incomeExpenseHead) {

                $start_balance += $startBalance = $this->GetBalanceByBranchIdIncExpIdTypeId($transaction_unique_branch->branch_id, $incomeExpenseHead->id, $incomeExpenseHead->type, $start_from, $start_to);
                $end_balance += $endBalance = $this->GetBalanceByBranchIdIncExpIdTypeId($transaction_unique_branch->branch_id, $incomeExpenseHead->id, $incomeExpenseHead->type, $end_from, $end_to);

                if (array_key_exists($incomeExpenseHead->name, $headBalanceStart)) {
                    $headBalanceStart[$incomeExpenseHead->name] += $startBalance;
                } else {
                    $headBalanceStart[$incomeExpenseHead->name] = $startBalance;
                }

                if (array_key_exists($incomeExpenseHead->name, $headBalanceEnd)) {
                    $headBalanceEnd[$incomeExpenseHead->name] += $endBalance;
                } else {
                    $headBalanceEnd[$incomeExpenseHead->name] = $endBalance;
                }
            }
        }

        $balance = array(
            'balance' => array(
                'start_balance' => $start_balance,
                'end_balance' => $end_balance
            ),
            'headDetails' => array(
                'StartDate' => $headBalanceStart,
                'EndDate' => $headBalanceEnd,
                'TotalBalance' => array(
                    'start_balance' => $start_balance,
                    'end_balance' => $end_balance
                ),
            )
        );
        return $balance;
    }
}
