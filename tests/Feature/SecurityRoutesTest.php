<?php

namespace Tests\Feature;

use App\Http\Middleware\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\SettingsController;
use App\Support\VoucherBulkActionAuthorization;
use App\Transaction;
use Illuminate\Http\UploadedFile as LaravelUploadedFile;
use Tests\TestCase;

class SecurityRoutesTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_payment_and_receipt_routes_require_authentication()
    {
        $this->withoutMiddleware(Settings::class);
        $routes = [
            ['GET', '/vendors/10/invoices'],
            ['POST', '/purchases/10/payment'],
            ['GET', '/purchases/10/receipt'],
            ['GET', '/purchases/view'],
            ['POST', '/vendors/10/pay'],
            ['GET', '/customers/10/invoices'],
            ['POST', '/invoices/10/pay'],
            ['GET', '/payment-receipts/10/pdf'],
            ['GET', '/receipts/view'],
            ['POST', '/payments/10/pay'],
        ];

        foreach ($routes as [$method, $uri]) {
            $route = app('router')->getRoutes()->match(Request::create($uri, $method));
            $this->assertContains('auth', $route->gatherMiddleware(), $method . ' ' . $uri);

            $response = $method === 'GET' ? $this->get($uri) : $this->post($uri);
            $response->assertRedirect(route('login'));
        }
    }

    public function test_legacy_voucher_state_changes_use_post_routes()
    {
        foreach (['dr-voucher', 'cr-voucher', 'journal-voucher', 'contra-voucher'] as $voucher) {
            foreach (['destroy/10', 'restore/10', 'kill/10', 'active/action', 'trashed/action'] as $action) {
                $uri = '/' . $voucher . '/' . $action;
                $route = app('router')->getRoutes()->match(Request::create($uri, 'POST'));

                $this->assertContains('POST', $route->methods(), $uri);
                $this->assertContains('auth', $route->gatherMiddleware(), $uri);
                $this->assertContains('web', $route->middleware(), $uri . ' must remain in the CSRF-protected web group');
                $this->get($uri)->assertStatus(405);
            }
        }

        $kernel = new \ReflectionClass(app(\App\Http\Kernel::class));
        $groups = $kernel->getProperty('middlewareGroups');
        $groups->setAccessible(true);
        $this->assertContains(\App\Http\Middleware\VerifyCsrfToken::class, $groups->getValue(app(\App\Http\Kernel::class))['web']);

        foreach ([
            ['dr-voucher', 'dr_voucher'],
            ['cr-voucher', 'cr_voucher'],
            ['journal-voucher', 'jnl_voucher'],
            ['contra-voucher', 'contra_voucher'],
        ] as [$path, $module]) {
            foreach ([
                ['destroy/10', $module . '.delete'],
                ['restore/10', $module . '.restore'],
                ['kill/10', $module . '.permanently_delete'],
            ] as [$action, $permission]) {
                $uri = '/' . $path . '/' . $action;
                $route = app('router')->getRoutes()->match(Request::create($uri, 'POST'));
                $this->assertContains($permission, $route->gatherMiddleware(), $uri);
            }

            // Bulk actions select different permissions in their controllers.
            $route = app('router')->getRoutes()->match(Request::create('/' . $path . '/active/action', 'POST'));
            $this->assertNotContains($module . '.delete', $route->gatherMiddleware());
            $this->assertContains('auth', $route->gatherMiddleware());
        }
    }

    public function test_voucher_bulk_permissions_are_selected_per_action()
    {
        $permissions = [
            'dr_voucher' => 'DrVoucher',
            'cr_voucher' => 'CrVoucher',
            'jnl_voucher' => 'JnlVoucher',
            'contra_voucher' => 'ContraVoucher',
        ];
        $actions = [
            ['active', 3, 'Delete'],
            ['active', 2, 'PermanentlyDelete'],
            ['trashed', 1, 'Restore'],
            ['trashed', 2, 'PermanentlyDelete'],
        ];

        $controllers = [
            'dr_voucher' => \App\Http\Controllers\DrVoucherController::class,
            'cr_voucher' => \App\Http\Controllers\CrVoucherController::class,
            'jnl_voucher' => \App\Http\Controllers\JournalVoucherController::class,
            'contra_voucher' => \App\Http\Controllers\ContraVoucherController::class,
        ];

        foreach ($permissions as $routeModule => $configModule) {
            foreach ($actions as [$location, $action, $expectedPermission]) {
                $permission = VoucherBulkActionAuthorization::permissionFor($location, $action);
                $this->assertSame($expectedPermission, $permission);
                config(['role_manage.' . $configModule . '.' . $permission => false]);
                try {
                    VoucherBulkActionAuthorization::authorize($routeModule, $location, $action);
                    $this->fail($routeModule . ' ' . $location . ' action ' . $action . ' must be denied');
                } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                    $this->assertSame(403, $exception->getStatusCode());
                }

                config(['role_manage.' . $configModule . '.' . $permission => true]);
                VoucherBulkActionAuthorization::authorize($routeModule, $location, $action);

                config(['role_manage.' . $configModule . '.' . $permission => false]);
                $request = Request::create('/voucher/bulk-action', 'POST', [
                    'items' => ['id' => [999999]],
                    'apply_comand_top' => $action,
                ]);
                try {
                    $controller = new $controllers[$routeModule]();
                    $method = $location === 'active' ? 'activeAction' : 'trashedAction';
                    $controller->{$method}($request);
                    $this->fail($routeModule . ' controller must reject ' . $location . ' action ' . $action);
                } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                    $this->assertSame(403, $exception->getStatusCode());
                }
            }
        }
    }

    public function test_authorized_bulk_actions_reach_the_expected_operation_without_database_access()
    {
        $controllers = [
            'dr_voucher' => TestDrVoucherController::class,
            'cr_voucher' => TestCrVoucherController::class,
            'jnl_voucher' => TestJournalVoucherController::class,
            'contra_voucher' => TestContraVoucherController::class,
        ];
        $configModules = [
            'dr_voucher' => 'DrVoucher',
            'cr_voucher' => 'CrVoucher',
            'jnl_voucher' => 'JnlVoucher',
            'contra_voucher' => 'ContraVoucher',
        ];
        $actions = [
            ['active', 3, 'Delete', 'destroy'],
            ['trashed', 1, 'Restore', 'restore'],
            ['active', 2, 'PermanentlyDelete', 'kill'],
            ['trashed', 2, 'PermanentlyDelete', 'kill'],
        ];

        foreach ($controllers as $module => $controllerClass) {
            foreach ($actions as [$location, $action, $permission, $expectedOperation]) {
                config(['role_manage.' . $configModules[$module] . '.' . $permission => true]);
                $controller = new $controllerClass();
                $controller->parentModel = FakeTransactionModel::class;
                $request = Request::create('/voucher/bulk-action', 'POST', [
                    'items' => ['id' => [42]],
                    'apply_comand_top' => $action,
                ]);

                $method = $location === 'active' ? 'activeAction' : 'trashedAction';
                $controller->{$method}($request);

                $this->assertSame([[$expectedOperation, 42]], $controller->operations, $module . ' ' . $location . ' ' . $permission);
            }
        }
    }

    public function test_ledger_helpers_reject_incomplete_scope_inputs_without_querying_database()
    {
        $transaction = new Transaction();

        $this->assertSame(0, $transaction->GetBankCashBalanceByBranchBankCashIdDate(1, null));
        $this->assertSame(0, $transaction->GetBankCashBalanceByBranchBankCashIdDate(1, 0));
        $this->assertSame(0, $transaction->GetBankCashBalanceByBranchBankCashIdDate(null, 2));
        $this->assertSame(0, $transaction->GetBankCashBalanceByBranchBankCashIdDate(1, 2, '2026-01-01', null));
        $this->assertSame(0, $transaction->GetBankCashBalanceByBranchBankCashIdDate(1, 2, null, '2026-01-31'));
        $this->assertSame(0, $transaction->GetBankCashBalanceByBranchBankCashIdDate(1, 2, '', ''));

        $this->assertSame([], $transaction->GetUniqueIncomeExpenseHeadByBranch(0));
        $this->assertSame([], $transaction->GetUniqueIncomeExpenseHeadByBranch(1, '2026-01-01', null));
        $this->assertSame([], $transaction->GetUniqueIncomeExpenseHeadByBranch(1, null, '2026-01-31'));
        $this->assertSame([], $transaction->GetUniqueIncomeExpenseHeadByBranch(0, '2026-01-01', null));
        $this->assertSame([], $transaction->GetUniqueIncomeExpenseHeadByBranch(null));
        $this->assertSame([], $transaction->GetUniqueIncomeExpenseHeadByBranch(1, '', ''));
    }

    public function test_header_image_endpoint_rejects_non_image_and_oversized_uploads_before_database_access()
    {
        $this->withoutMiddleware();

        $invalidType = LaravelUploadedFile::fake()->create('header.txt', 10, 'text/plain');
        $this->post('/settings/general/update', [
            'company_name' => 'Test Company',
            'header_image' => $invalidType,
        ])->assertSessionHasErrors('header_image');

        $oversizedImage = LaravelUploadedFile::fake()->image('large-header.png')->size(5121);
        $this->post('/settings/general/update', [
            'company_name' => 'Test Company',
            'header_image' => $oversizedImage,
        ])->assertSessionHasErrors('header_image');
    }

    public function test_settings_image_storage_replacement_preview_path_and_safe_deletion()
    {
        $controller = new SettingsController();
        [$originalPublicPath, $temporaryPublicPath] = $this->useTemporaryPublicPath();
        $store = new \ReflectionMethod(SettingsController::class, 'storeSettingsImage');
        $store->setAccessible(true);
        $delete = new \ReflectionMethod(SettingsController::class, 'deleteSettingsImage');
        $delete->setAccessible(true);

        $storedPaths = [];
        $temporaryFiles = [];
        try {
            foreach ([1, 2] as $copy) {
                $temporary = tempnam(sys_get_temp_dir(), 'ayu-header-test-');
                $temporaryFiles[] = $temporary;
                file_put_contents($temporary, file_get_contents(public_path('favicon.png')));
                $upload = new UploadedFile($temporary, 'client-name.png', 'image/png', null, true);
                $storedPaths[] = $store->invoke($controller, $upload, 'upload/header-image');
            }

            $firstPath = public_path($storedPaths[0]);
            $secondPath = public_path($storedPaths[1]);
            $this->assertStringStartsWith('upload/header-image/', $storedPaths[0]);
            $this->assertNotSame($storedPaths[0], $storedPaths[1]);
            $this->assertFileExists($firstPath);
            $this->assertFileExists($secondPath);
            $this->assertFileExists(public_path($storedPaths[0])); // same path consumed by invoice/receipt preview views

            $delete->invoke($controller, $storedPaths[0], 'upload/header-image');
            $this->assertFileDoesNotExist($firstPath);
            $this->assertFileExists($secondPath);

            $outsidePath = tempnam(sys_get_temp_dir(), 'ayu-outside-test-');
            $temporaryFiles[] = $outsidePath;
            file_put_contents($outsidePath, 'preserve');
            $delete->invoke($controller, '../' . basename($outsidePath), 'upload/header-image');
            $this->assertFileExists($outsidePath);
        } finally {
            foreach ($storedPaths as $storedPath) {
                $fullPath = public_path($storedPath);
                if (is_file($fullPath)) {
                    unlink($fullPath);
                }
            }
            foreach ($temporaryFiles as $temporary) {
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
            $this->restoreTemporaryPublicPath($originalPublicPath, $temporaryPublicPath);
        }
    }

    public function test_company_logo_upload_retains_the_configured_public_path()
    {
        $controller = new SettingsController();
        [$originalPublicPath, $temporaryPublicPath] = $this->useTemporaryPublicPath();
        $store = new \ReflectionMethod(SettingsController::class, 'storeSettingsImage');
        $store->setAccessible(true);
        $temporary = tempnam(sys_get_temp_dir(), 'ayu-logo-test-');
        $storedPath = null;

        try {
            file_put_contents($temporary, file_get_contents(public_path('favicon.png')));
            $upload = new UploadedFile($temporary, 'client-logo.png', 'image/png', null, true);
            $storedPath = $store->invoke($controller, $upload, 'upload/company-logo');

            $this->assertStringStartsWith('upload/company-logo/', $storedPath);
            $this->assertFileExists(public_path($storedPath));
        } finally {
            if ($storedPath && is_file(public_path($storedPath))) {
                unlink(public_path($storedPath));
            }
            if (is_file($temporary)) {
                unlink($temporary);
            }
            $this->restoreTemporaryPublicPath($originalPublicPath, $temporaryPublicPath);
        }
    }
    private function useTemporaryPublicPath(): array
    {
        $originalPublicPath = app()->make('path.public');
        $temporaryPublicPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ayu-public-test-' . bin2hex(random_bytes(8));
        if (!mkdir($temporaryPublicPath, 0777, true)) {
            throw new \RuntimeException('Unable to create isolated temporary public path.');
        }
        if (!copy($originalPublicPath . DIRECTORY_SEPARATOR . 'favicon.png', $temporaryPublicPath . DIRECTORY_SEPARATOR . 'favicon.png')) {
            rmdir($temporaryPublicPath);
            throw new \RuntimeException('Unable to prepare the isolated temporary public path.');
        }
        app()->instance('path.public', $temporaryPublicPath);

        return [$originalPublicPath, $temporaryPublicPath];
    }

    private function restoreTemporaryPublicPath(string $originalPublicPath, string $temporaryPublicPath): void
    {
        app()->instance('path.public', $originalPublicPath);
        foreach (['header-image', 'company-logo'] as $directory) {
            $target = $temporaryPublicPath . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . $directory;
            if (is_dir($target)) {
                foreach (glob($target . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($target);
            }
        }
        $uploadDirectory = $temporaryPublicPath . DIRECTORY_SEPARATOR . 'upload';
        if (is_dir($uploadDirectory)) {
            rmdir($uploadDirectory);
        }
        $favicon = $temporaryPublicPath . DIRECTORY_SEPARATOR . 'favicon.png';
        if (is_file($favicon)) {
            unlink($favicon);
        }
        if (is_dir($temporaryPublicPath)) {
            rmdir($temporaryPublicPath);
        }
    }
}

class FakeTransactionModel extends Transaction
{
    public static function where(...$arguments)
    {
        return new FakeVoucherBulkQuery();
    }
}

class FakeVoucherBulkQuery
{
    public function where(...$arguments)
    {
        return $this;
    }

    public function withTrashed()
    {
        return $this;
    }

    public function onlyTrashed()
    {
        return $this;
    }

    public function get()
    {
        return collect([new \stdClass()]);
    }
}

trait RecordsVoucherBulkOperations
{
    public $operations = [];

    public function destroy($id)
    {
        $this->operations[] = ['destroy', $id];
    }

    public function restore($id)
    {
        $this->operations[] = ['restore', $id];
    }

    public function kill($id)
    {
        $this->operations[] = ['kill', $id];
    }
}

class TestDrVoucherController extends \App\Http\Controllers\DrVoucherController
{
    use RecordsVoucherBulkOperations;
}

class TestCrVoucherController extends \App\Http\Controllers\CrVoucherController
{
    use RecordsVoucherBulkOperations;
}

class TestJournalVoucherController extends \App\Http\Controllers\JournalVoucherController
{
    use RecordsVoucherBulkOperations;
}

class TestContraVoucherController extends \App\Http\Controllers\ContraVoucherController
{
    use RecordsVoucherBulkOperations;
}
