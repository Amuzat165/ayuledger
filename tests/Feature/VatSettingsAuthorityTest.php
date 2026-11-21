<?php

namespace Tests\Feature;

use App\Http\Controllers\SettingsController;
use App\Http\Middleware\Settings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VatSettingsAuthorityTest extends TestCase
{
    private $transactionStarted = false;

    protected function setUp(): void
    {
        parent::setUp();

        $testDatabase = getenv('AYU_TEST_DATABASE');
        $configuredDatabase = config('database.connections.mysql.database');
        if (!$testDatabase || $testDatabase === $configuredDatabase || !preg_match('/^ayuglobal_step6h2_test_[a-z0-9_]+$/i', $testDatabase)) {
            $this->markTestSkipped('Set AYU_TEST_DATABASE to a dedicated Step 6H.2 MySQL test database; configured application data is never used by this test.');
        }

        config(['database.connections.mysql.database' => $testDatabase]);
        config(['cache.default' => 'array']);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
        $this->createSettingsTableIfNeeded();
        if (!Schema::hasColumn('settings', 'vat_percentage')) {
            $this->markTestSkipped('The dedicated test database must include the settings VAT compatibility column.');
        }
        DB::beginTransaction();
        $this->transactionStarted = true;
        DB::table('settings')->whereIn('settings_name', ['General Settings', 'System Settings'])->delete();
        DB::table('settings')->insert([
            ['settings_name' => 'General Settings', 'content' => json_encode(['company_name' => 'AYUGLOBAL', 'vat_percentage' => '7.50']), 'vat_percentage' => '0.00'],
            ['settings_name' => 'System Settings', 'content' => json_encode(['date_format' => 'd m Y', 'timezone' => 'Africa/Lagos', 'currency_code' => 'NGN', 'currency_symbol' => '₦', 'is_code' => 'code', 'currency_position' => 'Prefix', 'fixed_asset_schedule_starting_date' => '2026-10-01']), 'vat_percentage' => '0.00'],
        ]);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        if ($this->transactionStarted) {
            DB::rollBack();
        }
        Cache::flush();
        parent::tearDown();
    }

    public function test_sync_migration_preserves_json_vat_and_is_idempotent()
    {
        require_once base_path('database/migrations/2026_09_25_000001_sync_scalar_vat_with_general_settings.php');
        $migration = new \SyncScalarVatWithGeneralSettings();
        $migration->up();
        $migration->up();

        $row = DB::table('settings')->where('settings_name', 'General Settings')->first();
        $this->assertSame('7.50', (string) $row->vat_percentage);
        $this->assertSame('7.50', (string) json_decode($row->content, true)['vat_percentage']);
        $this->assertSame(1, DB::table('settings')->where('settings_name', 'General Settings')->count());
    }

    public function test_admin_update_synchronizes_scalar_json_cache_and_next_request_config()
    {
        $request = Request::create('/settings/general/update', 'POST', [
            'company_name' => 'AYUGLOBAL', 'vat_percentage' => '10.00', 'discount_enabled' => '1',
        ]);

        app(SettingsController::class)->general_update($request);

        $row = DB::table('settings')->where('settings_name', 'General Settings')->first();
        $this->assertSame('10.00', (string) $row->vat_percentage);
        $this->assertSame('10.00', (string) json_decode($row->content, true)['vat_percentage']);
        $this->assertSame('10.00', (string) json_decode(Cache::get('general_settings'), true)['vat_percentage']);
        $this->assertTrue(json_decode($row->content, true)['discount_enabled']);

        $response = app(Settings::class)->handle(Request::create('/'), function () {
            return response('ok');
        });
        $this->assertSame('ok', $response->getContent());
        $this->assertSame(10.0, (float) config('settings.vat_percentage'));
        $this->assertTrue(config('settings.discount_enabled'));
    }

    private function createSettingsTableIfNeeded(): void
    {
        if (Schema::hasTable('settings')) {
            return;
        }

        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('settings_name')->nullable();
            $table->longText('content');
            $table->string('header_image')->nullable();
            $table->decimal('vat_percentage', 5, 2)->default(0);
            $table->string('company_color')->default('#00897B');
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
