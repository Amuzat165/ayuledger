<?php

namespace Tests\Feature;

use App\Http\Controllers\Reports\General\GeneralReportController;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Tests\TestCase;

class GeneralVoucherDateFilterTest extends TestCase
{
    public function test_all_branch_january_and_february_results_use_voucher_date()
    {
        $january = $this->runReport('01/01/2026', '01/31/2026');
        $february = $this->runReport('02/01/2026', '02/28/2026');

        $this->assertInstanceOf(View::class, $january);
        $this->assertInstanceOf(View::class, $february);
        $this->assertSame([1, 2], $this->sortedIds($january));
        $this->assertSame([3, 4], $this->sortedIds($february));
        $this->assertSame('All Branches', $january->getData()['search_by']['branch_name']);

        foreach (array_merge($january->getData()['items']->all(), $february->getData()['items']->all()) as $transaction) {
            $this->assertStringStartsNotWith('2026-01', $transaction->created_at->format('Y-m-d'));
            $this->assertStringStartsNotWith('2026-02', $transaction->created_at->format('Y-m-d'));
        }
    }

    public function test_voucher_type_query_branch_also_filters_on_voucher_date()
    {
        $report = $this->runReport('01/01/2026', '01/31/2026', null, 'CV');

        $this->assertInstanceOf(View::class, $report);
        $this->assertSame([2], $this->sortedIds($report));
    }

    public function test_specific_branch_filter_is_preserved()
    {
        $report = $this->runReport('01/01/2026', '01/31/2026', 1);

        $this->assertInstanceOf(View::class, $report);
        $this->assertSame([1, 2], $this->sortedIds($report));
        $this->assertSame('Abuja Head Office', $report->getData()['search_by']['branch_name']);
        foreach ($report->getData()['items'] as $item) {
            $this->assertSame(1, (int) $item->branch_id);
        }
    }

    public function test_start_and_end_dates_are_inclusive_and_empty_period_redirects()
    {
        $startBoundary = $this->runReport('01/05/2026', '01/05/2026');
        $endBoundary = $this->runReport('01/15/2026', '01/15/2026');
        $noData = $this->runReport('03/01/2026', '03/31/2026');

        $this->assertSame([1], $this->sortedIds($startBoundary));
        $this->assertSame([2], $this->sortedIds($endBoundary));
        $this->assertInstanceOf(RedirectResponse::class, $noData);
    }

    private function runReport($from, $to, $branchId = null, $voucherType = null)
    {
        $data = [
            'from' => $from,
            'to' => $to,
            'action' => 'Show',
        ];
        if ($branchId !== null) {
            $data['branch_id'] = $branchId;
        }
        if ($voucherType !== null) {
            $data['voucher_type'] = $voucherType;
        }

        return (new GeneralReportController())->voucher_report(Request::create('/', 'POST', $data));
    }

    private function sortedIds(View $report)
    {
        $ids = array_map(function ($transaction) {
            return (int) $transaction->id;
        }, $report->getData()['items']->all());
        sort($ids);
        return $ids;
    }
}