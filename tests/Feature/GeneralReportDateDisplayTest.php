<?php

namespace Tests\Feature;

use App\Http\Controllers\Reports\General\GeneralReportController;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Tests\TestCase;

class GeneralReportDateDisplayTest extends TestCase
{
    public function test_general_report_views_display_the_submitted_date_range()
    {
        $controller = new GeneralReportController();
        $reports = [
            'General Branch' => 'branch_report',
            'General Ledger Type' => 'ledger_type_report',
            'General Ledger Group' => 'ledger_group_report',
            'General Bank & Cash' => 'bank_cash_report',
            'General Voucher' => 'voucher_report',
        ];
        $ranges = [
            ['01/01/2025', '12/31/2026'],
            ['01/01/2026', '12/31/2026'],
        ];

        foreach ($reports as $label => $method) {
            foreach ($ranges as [$from, $to]) {
                $request = Request::create('/', 'POST', [
                    'from' => $from,
                    'to' => $to,
                    'action' => 'Show',
                ]);
                $view = $controller->{$method}($request);

                $this->assertInstanceOf(View::class, $view, $label . ' report view');
                $searchBy = $view->getData()['search_by'];
                $this->assertSame(
                    date('d/m/Y', strtotime($from)),
                    $searchBy['from'],
                    $label . ' start date'
                );
                $this->assertSame(
                    date('d/m/Y', strtotime($to)),
                    $searchBy['to'],
                    $label . ' end date'
                );
                $rendered = $view->render();
                $this->assertStringContainsString($searchBy['from'], $rendered, $label . ' rendered start date');
                $this->assertStringContainsString($searchBy['to'], $rendered, $label . ' rendered end date');
                $this->assertMatchesRegularExpression('/\b\d{2}\/\d{2}\/\d{4}\b/', $rendered, $label . ' slash-formatted date');
                $this->assertStringNotContainsString('01/01/1970', $rendered, $label . ' no epoch date');
            }
        }
    }

    public function test_invalid_incomplete_and_reversed_ranges_keep_existing_behavior()
    {
        $controller = new GeneralReportController();
        $reports = [
            'General Branch' => 'branch_report',
            'General Ledger Type' => 'ledger_type_report',
            'General Ledger Group' => 'ledger_group_report',
            'General Bank & Cash' => 'bank_cash_report',
            'General Voucher' => 'voucher_report',
        ];

        foreach ($reports as $label => $method) {
            $invalidDateRaised = false;
            try {
                $controller->{$method}(Request::create('/', 'POST', [
                    'from' => 'not-a-date',
                    'to' => '09/27/2026',
                    'action' => 'Show',
                ]));
            } catch (\Exception $exception) {
                $invalidDateRaised = true;
            }
            $this->assertTrue($invalidDateRaised, $label . ' invalid-date exception behavior');

            $incomplete = $controller->{$method}(Request::create('/', 'POST', [
                'from' => '01/01/2025',
                'action' => 'Show',
            ]));
            $this->assertInstanceOf(View::class, $incomplete, $label . ' incomplete-range behavior');

            $reversed = $controller->{$method}(Request::create('/', 'POST', [
                'from' => '09/27/2026',
                'to' => '09/20/2026',
                'action' => 'Show',
            ]));
            $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $reversed, $label . ' reversed-range behavior');
        }
    }

    public function test_general_voucher_branch_scope_label_remains_unchanged()
    {
        $controller = new GeneralReportController();
        $base = [
            'from' => '01/01/2025',
            'to' => '12/31/2026',
            'action' => 'Show',
        ];

        $allBranches = $controller->voucher_report(Request::create('/', 'POST', $base));
        $abuja = $controller->voucher_report(Request::create('/', 'POST', $base + ['branch_id' => 1]));

        $this->assertInstanceOf(View::class, $allBranches);
        $this->assertInstanceOf(View::class, $abuja);
        $this->assertSame('All Branches', $allBranches->getData()['search_by']['branch_name']);
        $this->assertSame('Abuja Head Office', $abuja->getData()['search_by']['branch_name']);
    }

    public function test_general_voucher_displays_an_iso_selected_range_as_dd_mm_yyyy(): void
    {
        $report = (new GeneralReportController())->voucher_report(Request::create('/', 'POST', [
            'from' => '2026-02-01',
            'to' => '2026-02-28',
            'action' => 'Show',
        ]));

        $this->assertInstanceOf(View::class, $report);
        $this->assertSame('01/02/2026', $report->getData()['search_by']['from']);
        $this->assertSame('28/02/2026', $report->getData()['search_by']['to']);
        $rendered = $report->render();
        $this->assertStringContainsString('01/02/2026', $rendered);
        $this->assertStringContainsString('28/02/2026', $rendered);
        $this->assertStringNotContainsString('01 02 2026', $rendered);
        $this->assertStringNotContainsString('01/01/1970', $rendered);
    }
}
