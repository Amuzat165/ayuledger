<?php

namespace Tests\Feature;

use App\Http\Controllers\ModuleHubController;
use Tests\TestCase;

class ModuleHubLocalizationTest extends TestCase
{
    public function test_affected_hub_keys_are_translated_in_all_six_catalogs()
    {
        $expected = [
            'purchases_hub' => [
                'title' => 'Purchases & Expenses',
                'eyebrow' => 'PURCHASING & EXPENSE MANAGEMENT',
                'description' => 'Manage suppliers, purchase orders, payments, and expenses.',
                'search_placeholder' => 'Search purchases and expenses',
                'vendors' => 'Vendors',
                'add_vendor' => 'Add a vendor',
                'add_vendor_desc' => 'Create a supplier record.',
                'view_vendors' => 'View vendors',
                'view_vendors_desc' => 'Browse and manage suppliers.',
                'purchases_payments' => 'Purchases & Payments',
                'create_purchase_order' => 'Create a purchase order',
                'create_purchase_order_desc' => 'Prepare an order before goods or services are received.',
                'expenses' => 'Expenses',
                'add_payment_voucher' => 'Create a payment voucher',
                'add_payment_voucher_desc' => 'Record a supplier or business payment.',
                'make_supplier_payment' => 'Make Supplier Payment',
                'make_supplier_payment_desc' => 'Pay an approved Supplier Invoice with an outstanding balance.',
                'view_payments' => 'View payments',
                'view_payments_desc' => 'Review recorded payment vouchers.',
                'record_expense' => 'Record an expense',
                'record_expense_desc' => 'Enter a business expense.',
                'view_expenses' => 'View expenses',
                'view_expenses_desc' => 'Browse recorded expenses.',
                'links_count' => 'links',
                'search_no_match' => 'No purchases or expenses match your search.',
            ],
            'hubs' => [
                'sales' => 'Sales',
                'sales_eyebrow' => 'CUSTOMERS & REVENUE',
                'sales_description' => 'Manage customers, invoices, receipts, and payments.',
                'customers' => 'Customers',
                'add_customer' => 'Add a customer',
                'add_customer_description' => 'Create a customer record.',
                'view_customers' => 'View customers',
                'view_customers_description' => 'Browse and manage customers.',
                'invoices_receipts' => 'Invoices & Receipts',
                'add_invoice' => 'Create an invoice',
                'add_invoice_description' => 'Prepare an invoice for a customer.',
                'view_invoices' => 'View invoices',
                'view_invoices_description' => 'Browse customer invoices.',
                'add_receipt' => 'Record a receipt',
                'add_receipt_description' => 'Record a customer payment.',
                'view_receipts' => 'View receipts',
                'view_receipts_description' => 'Browse customer receipts.',
                'inventory' => 'Inventory & Assets',
                'inventory_eyebrow' => 'STOCK & ASSETS',
                'inventory_description' => 'Manage inventory items and review fixed assets.',
                'inventory_section' => 'Inventory',
                'add_inventory' => 'Add an inventory item',
                'add_inventory_description' => 'Create an item in the inventory list.',
                'view_inventory' => 'View inventory',
                'view_inventory_description' => 'Browse and manage inventory items.',
                'assets' => 'Assets',
                'fixed_asset_schedule' => 'Fixed Asset Schedule',
                'fixed_asset_schedule_description' => 'Review fixed asset balances and movements.',
            ],
        ];

        $previousLocale = app()->getLocale();
        try {
            foreach (['main', 'fr', 'ar', 'pt', 'es', 'zh'] as $locale) {
                app()->setLocale($locale);
                $catalog = require resource_path("lang/{$locale}/root.php");

                foreach ($expected as $section => $keys) {
                    foreach ($keys as $key => $englishValue) {
                        $this->assertArrayHasKey($key, $catalog[$section], "{$locale}.{$section}.{$key}");
                        $this->assertIsString($catalog[$section][$key], "{$locale}.{$section}.{$key}");
                        $this->assertNotSame('', trim($catalog[$section][$key]), "{$locale}.{$section}.{$key}");
                        $this->assertSame($catalog[$section][$key], __("root.{$section}.{$key}"), "{$locale}.root.{$section}.{$key} resolves");
                        $this->assertNotSame("root.{$section}.{$key}", __("root.{$section}.{$key}"), "{$locale}.root.{$section}.{$key} is translated");

                        if ($locale === 'main') {
                            $this->assertSame($englishValue, __("root.{$section}.{$key}"), "English root.{$section}.{$key}");
                        }
                    }
                }
            }
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    public function test_sales_and_inventory_hub_controllers_resolve_english_titles()
    {
        app()->setLocale('main');
        $controller = app(ModuleHubController::class);

        $this->assertSame('Sales', $controller->sales()->getData()['title']);
        $this->assertSame('Inventory & Assets', $controller->inventory()->getData()['title']);
    }
    public function test_supplier_payment_shortcut_uses_supplier_invoice_workflow_and_preserves_other_cards()
    {
        $viewPath = resource_path('views/admin/purchases-expenses/index.blade.php');
        $view = file_get_contents($viewPath);

        $this->assertTrue(\Illuminate\Support\Facades\Route::has('supplier-invoices.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('supplier-invoice-payments.create'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('supplier-invoice-payments.store'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('supplier-invoice-payments.receipt'));
        $this->assertStringContainsString(
            "['supplier-invoices.index', __('root.purchases_hub.make_supplier_payment'), __('root.purchases_hub.make_supplier_payment_desc'), 'fa-money-check-alt']",
            $view
        );
        $this->assertStringNotContainsString("['payments.create',", $view);
        $this->assertStringNotContainsString("['vouchers.index',", $view);
        $this->assertStringNotContainsString("['vendors.receipts.view',", $view);
        $this->assertStringContainsString(
            "['supplier-invoices.index', __('supplier_invoices.title'), __('supplier_invoices.list_intro'), 'fa-shopping-cart']",
            $view
        );

        foreach (['main', 'fr', 'ar', 'pt', 'es', 'zh'] as $locale) {
            $catalog = require resource_path("lang/{$locale}/root.php");
            $this->assertNotEmpty($catalog['purchases_hub']['make_supplier_payment'] ?? null, "{$locale} shortcut title exists");
            $this->assertNotEmpty($catalog['purchases_hub']['make_supplier_payment_desc'] ?? null, "{$locale} shortcut description exists");
            $this->assertSame(
                $catalog['purchases_hub']['make_supplier_payment'],
                __("root.purchases_hub.make_supplier_payment", [], $locale),
                "{$locale} shortcut title resolves through root catalog"
            );
            $this->assertSame(
                $catalog['purchases_hub']['make_supplier_payment_desc'],
                __("root.purchases_hub.make_supplier_payment_desc", [], $locale),
                "{$locale} shortcut description resolves through root catalog"
            );
            $this->assertNotSame("root.purchases_hub.make_supplier_payment", __("root.purchases_hub.make_supplier_payment", [], $locale));
        }

        $english = require resource_path('lang/main/root.php');
        $this->assertSame('Make Supplier Payment', $english['purchases_hub']['make_supplier_payment']);
        $this->assertSame(
            'Pay an approved Supplier Invoice with an outstanding balance.',
            $english['purchases_hub']['make_supplier_payment_desc']
        );

        foreach ([
            "['vendors.create', __('root.purchases_hub.add_vendor'), __('root.purchases_hub.add_vendor_desc'), 'fa-user-plus']",
            "['vendors.index', __('root.purchases_hub.view_vendors'), __('root.purchases_hub.view_vendors_desc'), 'fa-briefcase']",
            "['purchase-orders.create', __('root.purchases_hub.create_purchase_order'), __('root.purchases_hub.create_purchase_order_desc'), 'fa-file-signature']",
            "['purchase-orders.index', __('purchase_orders.title'), __('purchase_orders.list_intro'), 'fa-clipboard-list']",
            "['supplier-invoice-payments.index', __('root.purchases_hub.view_payments'), __('supplier_payments.history_intro'), 'fa-receipt']",
            "['expenses.create', __('root.purchases_hub.record_expense'), __('root.purchases_hub.record_expense_desc'), 'fa-plus-circle']",
            "['expenses.index', __('root.purchases_hub.view_expenses'), __('root.purchases_hub.view_expenses_desc'), 'fa-list-alt']",
        ] as $unchangedCard) {
            $this->assertStringContainsString($unchangedCard, $view);
        }
    }

    public function test_supplier_payment_history_labels_resolve_in_all_six_catalogs()
    {
        $keys = [
            'history_title', 'history_intro', 'date_from', 'date_to', 'apply_filters',
            'reset_filters', 'no_history', 'no_matching_payments', 'view_supplier_invoice',
        ];

        foreach (['main', 'fr', 'ar', 'pt', 'es', 'zh'] as $locale) {
            $catalog = require resource_path("lang/{$locale}/supplier_payments.php");
            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $catalog, "{$locale}.supplier_payments.{$key}");
                $this->assertIsString($catalog[$key], "{$locale}.supplier_payments.{$key}");
                $this->assertNotSame('', trim($catalog[$key]), "{$locale}.supplier_payments.{$key}");
                $this->assertSame(
                    $catalog[$key],
                    __("supplier_payments.{$key}", [], $locale),
                    "{$locale} supplier_payments.{$key} resolves through its catalog"
                );
                $this->assertNotSame("supplier_payments.{$key}", __("supplier_payments.{$key}", [], $locale));
            }
        }

        $view = file_get_contents(resource_path('views/admin/purchases-expenses/index.blade.php'));
        $this->assertStringContainsString(
            "['supplier-invoice-payments.index', __('root.purchases_hub.view_payments'), __('supplier_payments.history_intro'), 'fa-receipt']",
            $view
        );
    }
}