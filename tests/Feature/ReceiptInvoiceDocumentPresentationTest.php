<?php
namespace Tests\Feature;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Tests\TestCase;
class ReceiptInvoiceDocumentPresentationTest extends TestCase
{
    private $documentTestFontDirectory;
    private $originalDocumentPublicPath;
    private $documentTestPublicDirectory;
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalDocumentPublicPath = app()->make('path.public');
        $this->documentTestPublicDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ayu-document-public-' . bin2hex(random_bytes(8));
        mkdir($this->documentTestPublicDirectory . DIRECTORY_SEPARATOR . 'upload/header-image', 0777, true);
        mkdir($this->documentTestPublicDirectory . DIRECTORY_SEPARATOR . 'upload/company-logo', 0777, true);
        copy($this->originalDocumentPublicPath . DIRECTORY_SEPARATOR . 'upload/company-logo/e.png', $this->documentTestPublicDirectory . DIRECTORY_SEPARATOR . 'upload/company-logo/e.png');
        app()->instance('path.public', $this->documentTestPublicDirectory);
        $this->documentTestFontDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ayu-document-fonts-' . bin2hex(random_bytes(8));
        mkdir($this->documentTestFontDirectory, 0777, true);
        config(['dompdf.defines.font_dir' => $this->documentTestFontDirectory, 'dompdf.defines.font_cache' => $this->documentTestFontDirectory, 'dompdf.defines.temp_dir' => sys_get_temp_dir()]);
        $compiled = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ayu-document-presentation-views';
        if (!is_dir($compiled)) mkdir($compiled, 0777, true);
        config(['view.compiled' => $compiled]);
        $compiler = $this->app->make('blade.compiler');
        $property = new \ReflectionProperty(\Illuminate\View\Compilers\Compiler::class, 'cachePath');
        $property->setAccessible(true);
        $property->setValue($compiler, $compiled);
    }
    protected function tearDown(): void
    {
        app()->instance('path.public', $this->originalDocumentPublicPath);
        \Illuminate\Support\Facades\File::deleteDirectory($this->documentTestPublicDirectory);
        \Illuminate\Support\Facades\File::deleteDirectory($this->documentTestFontDirectory);
        parent::tearDown();
    }
    public function test_invoice_pdf_view_uses_final_total_words_and_renders_without_database()
    {
        $data = $this->invoiceData();
        $html = view('pages.customers.invoices.receipt', $data)->render();
        $this->assertStringContainsString('Five Thousand Seven Hundred and Fifty Naira, Fifty Kobo Only.', $html);
        $this->assertStringContainsString('5,750.50', $html);
        $this->assertStringContainsString('Amount Due', $html);
        $this->assertStringNotContainsString('Balance Due', $html);
        $this->assertStringNotContainsString('Three Thousand Naira Only.', $html);
        $pdf = Pdf::loadView('pages.customers.invoices.receipt', $data)->setPaper('A4')->output();
        $this->assertStringStartsWith('%PDF', $pdf);
    }
    public function test_invoice_0009_pdf_keeps_original_snapshot_amount_due_and_total_words()
    {
        $data = $this->invoiceData();
        $data['invoice']->invoice_number = '0009';
        $data['invoice']->subtotal = '32600.00';
        $data['invoice']->vat_percentage = '7.50';
        $data['invoice']->vat_amount = '2445.00';
        $data['invoice']->total_due = '35045.00';
        $data['invoice']->discount_percentage = '2.00';
        $data['invoice']->discount_amount = '700.90';
        $data['invoice']->balance_due = '34344.10';
        $data['amount_in_words'] = app(\App\Support\AmountInWordsFormatter::class)->format($data['invoice']->balance_due, 'NGN');
        $html = view('pages.customers.invoices.receipt', $data)->render();
        foreach (['35,045.00', '700.90', '34,344.10', 'Amount Due', 'Amount in words'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringNotContainsString('4,000.00', $html);
        $pdf = Pdf::loadView('pages.customers.invoices.receipt', $data)->setPaper('A4')->output();
        $this->assertStringStartsWith('%PDF', $pdf);
    }
    public function test_customer_invoice_balance_due_amount_in_words_matches_displayed_balance()
    {
        $controller = file_get_contents(app_path('Http/Controllers/CustomerInvoiceController.php'));
        $this->assertStringContainsString("->format(\$invoice->balance_due ?? \$invoice->total_due, config('settings.currency_code', ''))", $controller);
        $this->assertStringContainsString("\$balanceDue = \$invoice->balance_due ?? \$invoiceTotal;", $controller);
        $this->assertStringContainsString('->format($balanceDue, $currencyCode)', $controller);

        $cases = [
            ['balance' => null, 'display' => '100,000.00', 'words' => 'One Hundred Thousand Naira Only.'],
            ['balance' => '50000.00', 'display' => '50,000.00', 'words' => 'Fifty Thousand Naira Only.'],
            ['balance' => '0.00', 'display' => '0.00', 'words' => 'Zero Naira Only.'],
        ];

        foreach ($cases as $case) {
            $data = $this->invoiceData();
            $data['invoice']->total_due = '100000.00';
            $data['invoice']->balance_due = $case['balance'];
            $balanceDue = $data['invoice']->balance_due ?? $data['invoice']->total_due;
            $words = app(\App\Support\AmountInWordsFormatter::class)->format($balanceDue, 'NGN');
            $data['amount_in_words'] = $words;

            $html = view('pages.customers.invoices.receipt', $data)->render();
            $this->assertStringContainsString($case['display'], $html);
            $this->assertStringContainsString($case['words'], $html);

            if ($case['balance'] === '50000.00') {
                $originalTotalWords = app(\App\Support\AmountInWordsFormatter::class)->format('100000.00', 'NGN');
                $this->assertStringNotContainsString($originalTotalWords, $html);
            }
        }
    }
    public function test_receipt_pdf_view_uses_amount_received_words_and_renders_without_database()
    {
        $invoice = (object) [
            'financial_snapshot_version' => 1, 'subtotal' => '5000.00', 'vat_percentage' => '0.00',
            'vat_amount' => '0.00', 'total_due' => '5000.00', 'discount_percentage' => null,
            'discount_amount' => null, 'balance_due' => null, 'invoice_number' => 'INV-TEST',
        ];
        $receipt = (object) [
            'receipt_number' => 'AYU-RCP-TEST', 'received_at' => Carbon::parse('2026-02-10 10:30:00'),
            'customer' => (object) ['name' => 'Test Customer'], 'payment_mode_name' => 'Cash',
            'amount_received' => '3000.00', 'deposit_created_amount' => '0.00',
            'customer_deposit_balance_after' => '0.00', 'deposit' => null,
        ];
        $allocation = (object) ['invoice' => $invoice, 'invoice_id' => 77, 'allocated_amount' => '3000.00', 'balance_after' => '2000.00'];
        $data = [
            'receipt' => $receipt, 'allocation' => $allocation, 'currency' => '₦', 'currency_code' => 'NGN',
            'amount_in_words' => 'Three Thousand Naira Only.', 'total_paid_to_date' => '3000.00',
            'company_name' => 'Test Company', 'contact_person' => '', 'company_logo' => null, 'header_image' => null,
            'company_color' => '#176b63', 'address_1' => '', 'address_2' => '', 'city' => '',
            'state' => '', 'zip_code' => '', 'phone' => '', 'email' => '',
        ];
        $html = view('pages.customers.receipts.event-pdf', $data)->render();
        $this->assertStringContainsString('Three Thousand Naira Only.', $html);
        $this->assertStringContainsString('Amount Paid', $html);
        $this->assertStringContainsString('Invoice Total', $html);
        $this->assertStringContainsString('Discount', $html);
        $this->assertStringContainsString('Amount Due', $html);
        $this->assertStringContainsString('Balance Remaining', $html);
        $this->assertStringContainsString('2,000.00', $html);
        $this->assertStringNotContainsString('Five Thousand Naira Only.', $html);
        $pdf = Pdf::loadView('pages.customers.receipts.event-pdf', $data)->setPaper('A4')->output();
        $this->assertStringStartsWith('%PDF', $pdf);
    }
    public function test_receipt_pdf_preserves_invoice_snapshot_and_current_payment_state_for_invoice_0009_case()
    {
        $invoice = (object) [
            'financial_snapshot_version' => 2, 'subtotal' => '32600.00', 'vat_percentage' => '7.50',
            'vat_amount' => '2445.00', 'total_due' => '35045.00', 'discount_percentage' => '2.00',
            'discount_amount' => '700.90', 'balance_due' => '34344.10', 'invoice_number' => '0009',
        ];
        $receipt = (object) [
            'receipt_number' => 'AYU-RCP-000004', 'received_at' => Carbon::parse('2026-02-10 10:30:00'),
            'customer' => (object) ['name' => 'Test Customer'], 'payment_mode_name' => 'Bank Transfer',
            'amount_received' => '30344.10', 'deposit_created_amount' => '0.00',
            'customer_deposit_balance_after' => '0.00', 'deposit' => null,
        ];
        $allocation = (object) ['invoice' => $invoice, 'invoice_id' => 29, 'allocated_amount' => '30344.10', 'balance_after' => '4000.00'];
        $data = [
            'receipt' => $receipt, 'allocation' => $allocation, 'currency' => 'NGN ', 'currency_code' => 'NGN',
            'amount_in_words' => app(\App\Support\AmountInWordsFormatter::class)->format('30344.10', 'NGN'),
            'total_paid_to_date' => '30344.10', 'company_name' => 'Test Company', 'contact_person' => '',
            'company_logo' => null, 'header_image' => null, 'company_color' => '#176b63', 'address_1' => '',
            'address_2' => '', 'city' => '', 'state' => '', 'zip_code' => '', 'phone' => '', 'email' => '',
        ];
        $html = view('pages.customers.receipts.event-pdf', $data)->render();
        foreach (['Invoice Total', 'Discount', 'Amount Due', 'Amount Paid', 'Total Paid to Date', 'Balance Remaining', '35,045.00', '700.90', '34,344.10', '30,344.10', '4,000.00', 'Thirty Thousand Three Hundred and Forty Four Naira, Ten Kobo Only.'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringNotContainsString('Balance Due', $html);
        $pdf = Pdf::loadView('pages.customers.receipts.event-pdf', $data)->setPaper('A4')->output();
        $this->assertStringStartsWith('%PDF', $pdf);
    }
    public function test_receipt_pdf_distinguishes_an_existing_overpayment_as_customer_credit()
    {
        $invoice = (object) [
            'financial_snapshot_version' => 1, 'subtotal' => '11180.00', 'vat_percentage' => '0.00',
            'vat_amount' => '0.00', 'total_due' => '11180.00', 'discount_percentage' => null,
            'discount_amount' => null, 'balance_due' => null, 'invoice_number' => '0006',
        ];
        $receipt = (object) [
            'receipt_number' => 'AYU-RCP-000002', 'received_at' => Carbon::parse('2026-09-25 15:14:00'),
            'customer' => (object) ['name' => 'Olu'], 'payment_mode_name' => 'FCMB',
            'amount_received' => '2000.00', 'deposit_created_amount' => '820.00',
            'customer_deposit_balance_after' => '820.00', 'deposit' => null,
        ];
        $allocation = (object) ['invoice' => $invoice, 'invoice_id' => 8, 'allocated_amount' => '1180.00', 'balance_after' => '0.00'];
        $data = [
            'receipt' => $receipt, 'allocation' => $allocation, 'currency' => 'NGN ', 'currency_code' => 'NGN',
            'amount_in_words' => 'Two Thousand Naira Only.', 'total_paid_to_date' => '11180.00',
            'company_name' => 'Test Company', 'contact_person' => '', 'company_logo' => null, 'header_image' => null,
            'company_color' => '#176b63', 'address_1' => '', 'address_2' => '', 'city' => '', 'state' => '',
            'zip_code' => '', 'phone' => '', 'email' => '',
        ];
        $html = view('pages.customers.receipts.event-pdf', $data)->render();
        foreach (['Amount Paid', 'Applied to Invoice', 'Customer Credit', 'Total Paid to Date', 'Balance Remaining', '2,000.00', '1,180.00', '820.00', '11,180.00', '0.00', 'Paid in Full'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $pdf = Pdf::loadView('pages.customers.receipts.event-pdf', $data)->setPaper('A4')->output();
        $this->assertStringStartsWith('%PDF', $pdf);
    }
    public function test_operational_invoice_list_is_wired_to_authoritative_current_balance()
    {
        $controller = file_get_contents(app_path('Http/Controllers/CustomerInvoiceController.php'));
        $view = file_get_contents(resource_path('views/pages/customers/invoices/invoice-view.blade.php'));
        $this->assertStringContainsString('CustomerInvoiceBalanceService::class', $controller);
        $this->assertStringContainsString('balanceCentsFor($invoice)', $controller);
        $this->assertStringContainsString('$invoice->current_balance_cents / 100', $view);
        $this->assertStringContainsString('financial.labels.balance_due', $view);
        $this->assertStringContainsString('financial.labels.historical_invoice_requires_review', $view);
        $this->assertStringNotContainsString('$invoice->balance_due ?? $invoice->total_amount', $view);
        $compiler = new \Illuminate\View\Compilers\BladeCompiler(new \Illuminate\Filesystem\Filesystem(), sys_get_temp_dir());
        $this->assertNotEmpty($compiler->compileString($view));
    }
    public function test_receipt_list_uses_simple_primary_columns_and_keeps_legacy_summary()
    {
        $source = file_get_contents(resource_path('views/pages/customers/receipts/event-index.blade.php'));
        $compiler = new \Illuminate\View\Compilers\BladeCompiler(new \Illuminate\Filesystem\Filesystem(), sys_get_temp_dir());
        $compiled = $compiler->compileString($source);
        foreach (['table-responsive', 'financial.terms.amount_paid', 'financial.labels.payment_method', 'financial.labels.balance_remaining', 'customer.payment_receipts.pdf', 'legacy_payment_summaries'] as $expected) {
            $this->assertStringContainsString($expected, $compiled);
        }
        foreach (['financial.labels.allocated', 'financial.labels.deposit_created', 'financial.labels.balance_after'] as $removedPrimaryLabel) {
            $this->assertStringNotContainsString($removedPrimaryLabel, $compiled);
        }
    }
    public function test_new_receipt_labels_exist_in_all_six_supported_language_catalogs()
    {
        foreach (['main', 'fr', 'ar', 'pt', 'es', 'zh'] as $locale) {
            $catalog = require resource_path('lang/' . $locale . '/financial.php');
            foreach (['total_paid_to_date', 'applied_to_invoice', 'customer_credit', 'paid_in_full', 'back_to_receipts', 'amount_due', 'balance_remaining'] as $key) {
                $this->assertNotEmpty($catalog['labels'][$key] ?? null, $locale . ': ' . $key);
            }
        }
    }
    public function test_deposit_page_links_to_the_existing_receipt_list_route()
    {
        $html = view('pages.customers.deposit-index', [
            'title' => 'Customer Deposits', 'is_rtl' => false, 'deposits' => collect(), 'currency' => '₦',
        ])->render();
        $this->assertStringContainsString('Back to Receipts', $html);
        $this->assertStringContainsString(route('customers.receipts.view'), $html);
    }
    public function test_company_logo_fallback_remains_when_header_image_is_absent()
    {
        $data = $this->invoiceData();
        $data['company_logo'] = 'upload/company-logo/e.png';
        $this->assertFileExists(public_path($data['company_logo']));
        $html = view('pages.customers.invoices.receipt', $data)->render();
        $this->assertStringContainsString(public_path($data['company_logo']), $html);
        $this->assertStringContainsString('Test Company', $html);
    }
    public function test_invoice_and_receipt_header_images_scale_proportionally_for_varied_dimensions()
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD is required to create temporary header-image fixtures.');
        }
        $directory = public_path('upload/header-image');
        $this->assertDirectoryExists($directory);
        $createdPaths = [];
        $fontDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ayu-header-fonts-' . bin2hex(random_bytes(8));
        mkdir($fontDirectory, 0777, true);
        \Barryvdh\DomPDF\Facade\Pdf::setOptions([
            'fontDir' => $fontDirectory,
            'fontCache' => $fontDirectory,
            'tempDir' => $fontDirectory,
        ]);
        $profiles = [
            'wide-short' => [1600, 180],
            'standard-horizontal' => [1000, 320],
            'narrow-tall' => [320, 900],
            'minimal-branding' => [650, 180],
        ];
        $fixtures = [
            ['view' => 'pages.customers.invoices.receipt', 'data' => $this->invoiceData()],
            ['view' => 'pages.customers.receipts.event-pdf', 'data' => $this->receiptPresentationData()],
            ['view' => 'pages.customers.receipts.pdf', 'data' => $this->legacyReceiptPresentationData()],
        ];
        try {
            foreach ($profiles as $profile => [$width, $height]) {
                $filename = 'codex-header-' . bin2hex(random_bytes(8)) . '.png';
                $path = $directory . DIRECTORY_SEPARATOR . $filename;
                $createdPaths[] = $path;
                $image = imagecreatetruecolor($width, $height);
                $background = imagecolorallocate($image, 255, 255, 255);
                $accent = imagecolorallocate($image, 23, 107, 99);
                imagefill($image, 0, 0, $background);
                imagefilledrectangle($image, 0, 0, max(8, (int) ($width * 0.04)), $height, $accent);
                imagestring($image, 5, min(24, $width - 10), min(24, $height - 10), $profile, $accent);
                imagepng($image, $path);
                imagedestroy($image);
                foreach ($fixtures as $fixture) {
                    $data = $fixture['data'];
                    $data['header_image'] = 'upload/header-image/' . $filename;
                    $data['company_logo'] = 'upload/company-logo/e.png';
                    $data['address_1'] = '123 Example Street';
                    $html = view($fixture['view'], $data)->render();
                    if ($fixture['view'] === 'pages.customers.receipts.pdf') {
                        $this->assertStringContainsString('width:auto;max-width:100%;height:auto;max-height:0.95in;object-fit:contain', $html);
                    } else {
                        $this->assertStringContainsString('class="document-banner-container" style="width:100%;text-align:center;', $html);
                        $this->assertStringContainsString('display:block;width:100%;max-width:100%;height:auto;', $html);
                        $this->assertStringContainsString('display:block;text-align:center;font-size:9px;line-height:1.35;margin:0 auto 12px;', $html);
                    }
                    $this->assertStringContainsString(public_path($data['header_image']), $html);
                    $this->assertStringNotContainsString(public_path($data['company_logo']), $html, $profile . ' / duplicate logo');
                    $this->assertStringContainsString('Test Company', $html);
                    $this->assertStringContainsString('123 Example Street', $html);
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($fixture['view'], $data)->setPaper('A4')->output();
                    $this->assertStringStartsWith('%PDF-', $pdf, $profile . ' / ' . $fixture['view']);
                    $this->assertStringContainsString('%%EOF', substr($pdf, -2048), $profile . ' / ' . $fixture['view']);
                }
            }
        } finally {
            foreach ($createdPaths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
            \Illuminate\Support\Facades\File::deleteDirectory($fontDirectory);
        }
    }
    private function legacyReceiptPresentationData()
    {
        $payment = (object) [
            'id' => 99,
            'invoice' => (object) ['invoice_number' => 'INV-LEGACY', 'total_amount' => '5000.00'],
            'customer' => (object) ['name' => 'Test Customer', 'address' => 'Customer Road', 'phone' => '0800 000 0000'],
            'created_at' => Carbon::parse('2026-02-10'),
            'amount_paid' => '3000.00',
            'total_amount' => '5000.00',
            'balance_due' => '2000.00',
        ];
        return [
            'payment' => $payment,
            'payment_mode' => 'Cash',
            'header_image' => null,
            'company_logo' => null,
            'company_name' => 'Test Company',
            'company_color' => '#176b63',
            'address_1' => '123 Example Street',
            'address_2' => '',
            'city' => 'Abuja',
            'phone' => '0800 000 0000',
            'email' => 'accounts@example.test',
            'vat_percentage' => 0,
            'currency_symbol' => '₦',
        ];
    }
    private function receiptPresentationData()
    {
        $invoice = (object) [
            'financial_snapshot_version' => 1,
            'subtotal' => '5000.00',
            'vat_percentage' => '0.00',
            'vat_amount' => '0.00',
            'total_due' => '5000.00',
            'discount_percentage' => null,
            'discount_amount' => null,
            'balance_due' => null,
            'invoice_number' => 'INV-HEADER-TEST',
        ];
        $receipt = (object) [
            'receipt_number' => 'AYU-RCP-HEADER-TEST',
            'received_at' => \Illuminate\Support\Carbon::parse('2026-02-10 10:30:00'),
            'customer' => (object) ['name' => 'Test Customer'],
            'payment_mode_name' => 'Cash',
            'amount_received' => '3000.00',
            'deposit_created_amount' => '0.00',
        ];
        $allocation = (object) [
            'invoice' => $invoice,
            'invoice_id' => 77,
            'allocated_amount' => '3000.00',
            'balance_after' => '2000.00',
        ];
        return [
            'receipt' => $receipt,
            'allocation' => $allocation,
            'currency' => '₦',
            'currency_code' => 'NGN',
            'amount_in_words' => 'Three Thousand Naira Only.',
            'total_paid_to_date' => '3000.00',
            'company_name' => 'Test Company',
            'contact_person' => '',
            'company_logo' => null,
            'header_image' => null,
            'company_color' => '#176b63',
            'address_1' => '123 Example Street',
            'address_2' => '',
            'city' => 'Abuja',
            'state' => '',
            'zip_code' => '',
            'phone' => '0800 000 0000',
            'email' => 'accounts@example.test',
        ];
    }
    private function invoiceData()
    {
        $invoice = (object) [
            'invoice_number' => 'INV-TEST', 'invoice_date' => '2026-02-01', 'subtotal' => '5000.44',
            'vat_percentage' => '15.00', 'vat_amount' => '750.06', 'total_due' => '5750.50',
            'discount_percentage' => null, 'discount_amount' => null, 'balance_due' => '5750.50',
        ];
        $item = (object) [
            'inventory' => (object) ['type' => (object) ['name' => 'Test Item']],
            'description' => 'Test item description', 'quantity' => 1, 'rate' => '5000.44', 'amount' => '5000.44',
        ];
        return [
            'invoice' => $invoice, 'customer' => (object) ['name' => 'Test Customer', 'address' => '1 Test Road', 'branch' => (object) ['name' => 'Abuja']],
            'invoiceItems' => [$item], 'totalAmount' => '5000.44', 'header_image' => null,
            'company_logo' => null, 'company_name' => 'Test Company', 'contact_person' => '', 'company_color' => '#176b63',
            'address_1' => '', 'address_2' => '', 'city' => '', 'state' => '', 'zip_code' => '', 'phone' => '', 'email' => '',
            'currency' => '₦', 'currency_code' => 'NGN',
            'amount_in_words' => 'Five Thousand Seven Hundred and Fifty Naira, Fifty Kobo Only.',
        ];
    }
}
