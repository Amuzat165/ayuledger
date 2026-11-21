<?php

namespace Tests\Feature;

use App\Http\Controllers\Reports\Accounts\TrialBalanceController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TrialBalanceFilteringTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('branches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->softDeletes();
        });

        Schema::create('income_expense_heads', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('type');
            $table->softDeletes();
        });

        Schema::create('bank_cashes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->softDeletes();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('branch_id');
            $table->unsignedInteger('income_expense_head_id')->nullable();
            $table->unsignedInteger('bank_cash_id')->nullable();
            $table->date('voucher_date');
            $table->decimal('dr', 14, 2)->default(0);
            $table->decimal('cr', 14, 2)->default(0);
            $table->softDeletes();
        });

        DB::table('branches')->insert([
            ['id' => 1, 'name' => 'Abuja Head Office'],
            ['id' => 2, 'name' => 'Lagos Branch'],
        ]);
        DB::table('income_expense_heads')->insert([
            ['id' => 1, 'name' => 'Operating Expense', 'type' => 1],
            ['id' => 2, 'name' => 'Sales Revenue', 'type' => 0],
        ]);

        $this->insertTransaction(1, 1, '2025-01-01', 100, 0);
        $this->insertTransaction(1, 2, '2025-01-01', 0, 100);
        $this->insertTransaction(1, 1, '2025-06-15', 25, 0);
        $this->insertTransaction(1, 2, '2025-06-15', 0, 25);
        $this->insertTransaction(1, 1, '2026-12-31', 50, 0);
        $this->insertTransaction(1, 2, '2026-12-31', 0, 50);
        $this->insertTransaction(2, 1, '2025-01-01', 200, 0);
        $this->insertTransaction(2, 2, '2025-01-01', 0, 200);
    }

    public function test_all_branches_and_specific_branches_use_the_selected_branch_scope(): void
    {
        $allBranches = $this->runReport('', '01/01/2025', '31/12/2025');
        $this->assertEqualsCanonicalizing(
            ['Abuja Head Office', 'Lagos Branch'],
            array_keys($allBranches->getData()['items']['branches']->all())
        );
        $this->assertCount(6, $this->transactionsIn($allBranches));
        $this->assertSame(325.0, $this->debitTotal($allBranches));
        $this->assertSame(325.0, $this->creditTotal($allBranches));
        $this->assertSame('TRIAL BALANCE REPORT - All Branches', $allBranches->getData()['extra']['voucher_type']);
        $this->assertSame('Trial Balance Report - All Branches', $allBranches->getData()['extra']['module_name']);

        $abuja = $this->runReport('1', '01/01/2025', '31/12/2025');
        $this->assertSame(['Abuja Head Office'], array_keys($abuja->getData()['items']['branches']->all()));
        $this->assertCount(4, $this->transactionsIn($abuja));
        $this->assertSame(125.0, $this->debitTotal($abuja));
        $this->assertSame(125.0, $this->creditTotal($abuja));
        $this->assertSame('TRIAL BALANCE REPORT - Abuja Head Office', $abuja->getData()['extra']['voucher_type']);

        $lagos = $this->runReport('2', '01/01/2025', '31/12/2025');
        $this->assertSame(['Lagos Branch'], array_keys($lagos->getData()['items']['branches']->all()));
        $this->assertCount(2, $this->transactionsIn($lagos));
        $this->assertSame(200.0, $this->debitTotal($lagos));
        $this->assertSame(200.0, $this->creditTotal($lagos));
        $this->assertSame('TRIAL BALANCE REPORT - Lagos Branch', $lagos->getData()['extra']['voucher_type']);
    }

    public function test_selected_period_controls_the_transaction_rows_and_report_date_values(): void
    {
        $year2025 = $this->runReport('1', '01/01/2025', '31/12/2025');
        $year2025To2026 = $this->runReport('1', '01/01/2025', '31/12/2026');
        $allBranches2025To2026 = $this->runReport('', '01/01/2025', '31/12/2026');
        $lagos2025To2026 = $this->runReport('2', '01/01/2025', '31/12/2026');
        $shortPeriod = $this->runReport('1', '15/06/2025', '15/06/2025');

        $this->assertCount(4, $this->transactionsIn($year2025));
        $this->assertCount(6, $this->transactionsIn($year2025To2026));
        $this->assertCount(8, $this->transactionsIn($allBranches2025To2026));
        $this->assertCount(2, $this->transactionsIn($lagos2025To2026));
        $this->assertCount(2, $this->transactionsIn($shortPeriod));
        $this->assertSame(175.0, $this->debitTotal($year2025To2026));
        $this->assertSame(175.0, $this->creditTotal($year2025To2026));
        $this->assertSame(375.0, $this->debitTotal($allBranches2025To2026));
        $this->assertSame(375.0, $this->creditTotal($allBranches2025To2026));
        $this->assertSame(25.0, $this->debitTotal($shortPeriod));
        $this->assertSame(25.0, $this->creditTotal($shortPeriod));
        $this->assertSame('2025-01-01', $year2025To2026->getData()['search_by']['from']);
        $this->assertSame('2026-12-31', $year2025To2026->getData()['search_by']['to']);
        $this->assertSame('01/01/2025', date('d/m/Y', strtotime($year2025To2026->getData()['search_by']['from'])));
        $this->assertSame('31/12/2026', date('d/m/Y', strtotime($year2025To2026->getData()['search_by']['to'])));
        $rendered = $year2025To2026->render();
        $this->assertStringContainsString('01/01/2025', $rendered);
        $this->assertStringContainsString('31/12/2026', $rendered);
        $this->assertStringNotContainsString('01 01 2025', $rendered);
    }

    public function test_incomplete_or_invalid_date_ranges_are_rejected_instead_of_removing_the_filter(): void
    {
        $this->expectException(ValidationException::class);
        $this->runReport('', '01/01/2025', '');
    }

    public function test_invalid_and_reversed_date_ranges_are_rejected(): void
    {
        try {
            $this->runReport('', '31/02/2025', '31/12/2025');
            $this->fail('An impossible calendar date must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('from', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        $this->runReport('', '01/01/2026', '31/12/2025');
    }

    private function runReport($branchId, $from, $to)
    {
        return app(TrialBalanceController::class)->branch_wise(Request::create('/reports/accounts/trial-balance/report', 'POST', [
            'branch_id' => $branchId,
            'from' => $from,
            'to' => $to,
            'action' => 'Show',
        ]));
    }

    private function insertTransaction($branchId, $headId, $date, $debit, $credit): void
    {
        DB::table('transactions')->insert([
            'branch_id' => $branchId,
            'income_expense_head_id' => $headId,
            'voucher_date' => $date,
            'dr' => $debit,
            'cr' => $credit,
        ]);
    }

    private function transactionsIn($view): array
    {
        return $view->getData()['items']['branches']->flatten()->all();
    }

    private function debitTotal($view): float
    {
        return array_sum(array_map(function ($transaction) {
            return (float) $transaction->dr;
        }, $this->transactionsIn($view)));
    }

    private function creditTotal($view): float
    {
        return array_sum(array_map(function ($transaction) {
            return (float) $transaction->cr;
        }, $this->transactionsIn($view)));
    }
}
