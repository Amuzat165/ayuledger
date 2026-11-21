<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FinancialLocalizationTest extends TestCase
{
    public function test_financial_catalog_keys_and_placeholders_match_in_all_six_locales()
    {
        $source = $this->flatten(require resource_path('lang/main/financial.php'));
        $this->assertCount(445, $source);

        foreach (['fr', 'ar', 'pt', 'es', 'zh'] as $locale) {
            $translated = $this->flatten(require resource_path("lang/{$locale}/financial.php"));
            $this->assertSame(array_keys($source), array_keys($translated), "{$locale} financial keys");
            foreach ($source as $key => $value) {
                $this->assertIsString($translated[$key], "{$locale}.{$key}");
                $this->assertSame($this->placeholders($value), $this->placeholders($translated[$key]), "{$locale}.{$key} placeholders");
            }
        }
    }

    public function test_voucher_and_ledger_report_labels_resolve_in_all_six_locales()
    {
        $expected = [
            'main' => ['voucher_no' => 'Voucher No', 'particulars' => 'Particulars', 'branch_name' => 'Branch Name', 'date' => 'Date', 'head_of_account_name' => 'Head Of Account Name', 'made_of_payment' => 'Made Of Payment', 'chq_no' => 'CHQ. No', 'amount' => 'Amount', 'options' => 'Options', 'ledger_manage' => 'Ledger Manage', 'select_ledger_name' => 'Select Ledger Name', 'ledger_name_wise' => 'LEDGER NAME', 'cash_bank_book_wise' => 'CASH BANK BOOK', 'select_bank_cash_name' => 'Select Bank Cash Name', 'show' => 'Show', 'pdf_file' => 'PDF file', 'excel_file' => 'Excel file'],
            'fr' => ['voucher_no' => 'N° de pièce', 'particulars' => 'Libellé', 'branch_name' => 'Succursale', 'date' => 'Date', 'head_of_account_name' => 'Intitulé du compte', 'made_of_payment' => 'Mode de paiement', 'chq_no' => 'N° de chèque', 'amount' => 'Montant', 'options' => 'Actions', 'ledger_manage' => 'Gestion du grand livre', 'select_ledger_name' => 'Sélectionner un compte du grand livre', 'ledger_name_wise' => 'COMPTE DU GRAND LIVRE', 'cash_bank_book_wise' => 'JOURNAL DE BANQUE ET DE CAISSE', 'select_bank_cash_name' => 'Sélectionner un compte bancaire ou de caisse', 'show' => 'Afficher', 'pdf_file' => 'Fichier PDF', 'excel_file' => 'Fichier Excel'],
            'ar' => ['voucher_no' => 'رقم السند', 'particulars' => 'البيان', 'branch_name' => 'اسم الفرع', 'date' => 'التاريخ', 'head_of_account_name' => 'اسم الحساب', 'made_of_payment' => 'طريقة الدفع', 'chq_no' => 'رقم الشيك', 'amount' => 'المبلغ', 'options' => 'الإجراءات', 'ledger_manage' => 'إدارة دفتر الأستاذ', 'select_ledger_name' => 'اختر حساب دفتر الأستاذ', 'ledger_name_wise' => 'حساب دفتر الأستاذ', 'cash_bank_book_wise' => 'دفتر البنك والنقد', 'select_bank_cash_name' => 'اختر حساب البنك أو النقد', 'show' => 'عرض', 'pdf_file' => 'ملف PDF', 'excel_file' => 'ملف Excel'],
            'pt' => ['voucher_no' => 'N.º do comprovativo', 'particulars' => 'Descrição', 'branch_name' => 'Filial', 'date' => 'Data', 'head_of_account_name' => 'Nome da conta', 'made_of_payment' => 'Meio de pagamento', 'chq_no' => 'N.º do cheque', 'amount' => 'Montante', 'options' => 'Ações', 'ledger_manage' => 'Gestão do razão', 'select_ledger_name' => 'Selecionar conta do razão', 'ledger_name_wise' => 'CONTA DO RAZÃO', 'cash_bank_book_wise' => 'LIVRO DE BANCOS E CAIXA', 'select_bank_cash_name' => 'Selecionar conta bancária ou de caixa', 'show' => 'Mostrar', 'pdf_file' => 'Ficheiro PDF', 'excel_file' => 'Ficheiro Excel'],
            'es' => ['voucher_no' => 'N.º de comprobante', 'particulars' => 'Concepto', 'branch_name' => 'Sucursal', 'date' => 'Fecha', 'head_of_account_name' => 'Nombre de la cuenta', 'made_of_payment' => 'Método de pago', 'chq_no' => 'N.º de cheque', 'amount' => 'Importe', 'options' => 'Acciones', 'ledger_manage' => 'Gestión del libro mayor', 'select_ledger_name' => 'Seleccionar cuenta del libro mayor', 'ledger_name_wise' => 'CUENTA DEL LIBRO MAYOR', 'cash_bank_book_wise' => 'LIBRO DE BANCOS Y CAJA', 'select_bank_cash_name' => 'Seleccionar cuenta bancaria o de caja', 'show' => 'Mostrar', 'pdf_file' => 'Archivo PDF', 'excel_file' => 'Archivo Excel'],
            'zh' => ['voucher_no' => '凭证编号', 'particulars' => '摘要', 'branch_name' => '分支机构', 'date' => '日期', 'head_of_account_name' => '会计科目名称', 'made_of_payment' => '付款方式', 'chq_no' => '支票号', 'amount' => '金额', 'options' => '操作', 'ledger_manage' => '总账管理', 'select_ledger_name' => '选择总账科目', 'ledger_name_wise' => '总账科目', 'cash_bank_book_wise' => '银行和现金日记账', 'select_bank_cash_name' => '选择银行或现金账户', 'show' => '显示', 'pdf_file' => 'PDF 文件', 'excel_file' => 'Excel 文件'],
        ];
        $voucherSections = ['dr_voucher', 'cr_voucher', 'jnl_voucher', 'contra_voucher'];
        $voucherKeys = ['voucher_no', 'particulars', 'branch_name', 'date', 'head_of_account_name', 'made_of_payment', 'chq_no', 'amount', 'options'];
        $reportKeys = ['ledger_manage', 'select_ledger_name', 'ledger_name_wise', 'cash_bank_book_wise', 'select_bank_cash_name'];

        foreach (array_keys($expected) as $locale) {
            app()->setLocale($locale);
            $root = require resource_path("lang/{$locale}/root.php");
            $financial = require resource_path("lang/{$locale}/financial.php");

            foreach ($voucherSections as $section) {
                $this->assertArrayHasKey($section, $root, "{$locale}.{$section}");
                foreach ($voucherKeys as $key) {
                    $this->assertArrayHasKey($key, $root[$section], "{$locale}.{$section}.{$key}");
                    $this->assertNotSame("root.{$section}.{$key}", __("root.{$section}.{$key}"));
                }
            }
            foreach ($reportKeys as $key) {
                $this->assertArrayHasKey($key, $root['reports'], "{$locale}.reports.{$key}");
            }
            foreach ($expected[$locale] as $key => $value) {
                $translationKey = in_array($key, $voucherKeys, true)
                    ? "root.dr_voucher.{$key}"
                    : (in_array($key, $reportKeys, true)
                        ? "root.reports.{$key}"
                        : "financial.screens.{$key}");
                $this->assertSame($value, __($translationKey), "{$locale}.{$translationKey}");
            }
        }

        $voucherView = file_get_contents(resource_path('views/admin/dr-voucher/index.blade.php'));
        $reportView = file_get_contents(resource_path('views/admin/accounts-report/ledger/index.blade.php'));
        $this->assertStringContainsString("__('root.dr_voucher.voucher_no')", $voucherView);
        $this->assertStringContainsString("__('root.dr_voucher.particulars')", $voucherView);
        $this->assertStringContainsString("__('root.dr_voucher.branch_name')", $voucherView);
        $this->assertStringContainsString("__('root.reports.ledger_manage')", $reportView);
        $this->assertStringContainsString("__('root.reports.select_ledger_name')", $reportView);
        $this->assertStringContainsString("__('financial.screens.show')", $reportView);
        $this->assertStringContainsString("__('financial.screens.pdf_file')", $reportView);
        $this->assertStringContainsString("__('financial.screens.excel_file')", $reportView);
        app()->setLocale('main');
    }
    public function test_standard_financial_terms_resolve_for_each_approved_locale()
    {
        $expectedInvoices = [
            'main' => 'Invoice', 'fr' => 'Facture', 'ar' => "\u{641}\u{627}\u{62a}\u{648}\u{631}\u{629}",
            'pt' => 'Fatura', 'es' => 'Factura', 'zh' => "\u{53d1}\u{7968}",
        ];
        foreach ($expectedInvoices as $locale => $expected) {
            app()->setLocale($locale);
            $this->assertSame($expected, __('financial.terms.invoice'), $locale);
            $this->assertNotSame('Invoice', __('financial.terms.balance_due'), $locale);
        }
    }

    public function test_accounting_and_banking_hubs_render_localized_for_all_six_locales()
    {
        $controller = app(\App\Http\Controllers\ModuleHubController::class);
        foreach (['main', 'fr', 'ar', 'pt', 'es', 'zh'] as $locale) {
            app()->setLocale($locale);
            foreach (['accounting', 'banking'] as $hub) {
                $viewData = $controller->{$hub}()->getData();
                $renderedValues = json_encode($viewData, JSON_UNESCAPED_UNICODE);
                $this->assertStringNotContainsString('root.hubs.', $renderedValues, "{$locale}.{$hub} hub keys resolved");
            }
        }
        app()->setLocale('main');
    }
    public function test_customer_vendor_and_financial_report_blade_templates_compile()
    {
        $directories = [
            resource_path('views/pages/customers'),
            resource_path('views/pages/vendors'),
            resource_path('views/admin/accounts-report'),
            resource_path('views/pages/expenses'),
            resource_path('views/pages/inventories'),
            resource_path('views/pages/payrolls'),
            resource_path('views/admin/bank-cash'),
            resource_path('views/admin/initial-bank-cash-balance'),
            resource_path('views/admin/initial-income-expense-head-balance'),
            resource_path('views/admin/income-expense-head'),
            resource_path('views/admin/income-expense-group'),
            resource_path('views/admin/income-expense-type'),
            resource_path('views/admin/general-report'),
            resource_path('views/admin/branch'),
            resource_path('views/admin/dr-voucher'),
            resource_path('views/admin/cr-voucher'),
            resource_path('views/admin/jnl-voucher'),
            resource_path('views/admin/cnt-voucher'),
            resource_path('views/admin/dashboard'),
            resource_path('views/admin/settings'),
            resource_path('views/shared'),
        ];
        $compiledCount = 0;
        foreach ($directories as $directory) {
            foreach (\Illuminate\Support\Facades\File::allFiles($directory) as $file) {
                if (!str_ends_with($file->getFilename(), '.blade.php')) continue;
                $compiled = Blade::compileString($file->getContents());
                $this->assertIsString($compiled, $file->getRelativePathname());
                $compiledCount++;
            }
        }
        $this->assertGreaterThan(50, $compiledCount);
    }

    public function test_financial_translation_references_in_audited_views_exist_in_source_catalog()
    {
        $directories = [
            resource_path('views/pages/expenses'),
            resource_path('views/pages/inventories'),
            resource_path('views/pages/payrolls'),
            resource_path('views/pages/customers'),
            resource_path('views/pages/vendors'),
            resource_path('views/admin/accounts-report'),
            resource_path('views/admin/bank-cash'),
            resource_path('views/admin/initial-bank-cash-balance'),
            resource_path('views/admin/initial-income-expense-head-balance'),
            resource_path('views/admin/income-expense-head'),
            resource_path('views/admin/income-expense-group'),
            resource_path('views/admin/income-expense-type'),
            resource_path('views/admin/general-report'),
            resource_path('views/admin/branch'),
            resource_path('views/admin/dr-voucher'),
            resource_path('views/admin/cr-voucher'),
            resource_path('views/admin/jnl-voucher'),
            resource_path('views/admin/cnt-voucher'),
            resource_path('views/admin/dashboard'),
            resource_path('views/admin/settings'),
            resource_path('views/shared'),
        ];
        $references = [];
        foreach ($directories as $directory) {
            foreach (\Illuminate\Support\Facades\File::allFiles($directory) as $file) {
                if (!str_ends_with($file->getFilename(), '.blade.php')) continue;
                preg_match_all("/financial\.([A-Za-z0-9_.]+)/", $file->getContents(), $matches);
                foreach ($matches[1] as $key) {
                    $references[$key] = $file->getRelativePathname();
                }
            }
        }
        $this->assertNotEmpty($references);
        foreach ($references as $key => $file) {
            $this->assertTrue(\Illuminate\Support\Facades\Lang::has('financial.' . $key, 'main'), $file . ': financial.' . $key);
        }
    }

    private function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];
        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) $flat += $this->flatten($value, $path);
            else $flat[$path] = $value;
        }
        return $flat;
    }

    private function placeholders(string $value): array
    {
        preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/', $value, $matches);
        $placeholders = array_values(array_unique($matches[1]));
        sort($placeholders);
        return $placeholders;
    }
}
