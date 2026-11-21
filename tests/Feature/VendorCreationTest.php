<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class VendorCreationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('PDO SQLite is unavailable; refusing to use the configured MySQL database.');
        }

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        View::share('is_rtl', false);

        Schema::create('branches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('income_expense_heads', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('vendors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reg_number');
            $table->string('name');
            $table->string('address');
            $table->string('phone_number');
            $table->string('account');
            $table->string('terms_of_payment');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('branches')->insert(['name' => 'Test branch']);
        DB::table('income_expense_heads')->insert(['name' => 'Purchases']);
        DB::table('vendors')->insert([
            ['reg_number' => '10001', 'name' => 'Existing vendor one', 'address' => 'Address 1', 'phone_number' => '111', 'account' => 'Purchases', 'terms_of_payment' => 'cod'],
            ['reg_number' => '10002', 'name' => 'Existing vendor two', 'address' => 'Address 2', 'phone_number' => '222', 'account' => 'Purchases', 'terms_of_payment' => 'cod'],
        ]);

        require_once base_path('database/migrations/2026_09_24_000001_add_branch_id_to_vendors_table.php');
        $migration = new \AddBranchIdToVendorsTable();
        $migration->up();
    }

    public function test_migration_adds_nullable_unsigned_branch_reference_and_preserves_existing_vendors(): void
    {
        $column = collect(DB::select("PRAGMA table_info('vendors')"))->firstWhere('name', 'branch_id');
        $foreignKey = collect(DB::select("PRAGMA foreign_key_list('vendors')"))->firstWhere('from', 'branch_id');

        $this->assertNotNull($column);
        $this->assertSame('INTEGER', strtoupper($column->type));
        $this->assertSame(0, (int) $column->notnull);
        $this->assertNotNull($foreignKey);
        $this->assertSame('branches', $foreignKey->table);
        $this->assertSame('id', $foreignKey->to);
        $this->assertSame('SET NULL', strtoupper($foreignKey->on_delete));

        $this->assertSame(2, DB::table('vendors')->count());
        $this->assertSame(2, DB::table('vendors')->whereNull('branch_id')->count());
        $this->assertSame(['Existing vendor one', 'Existing vendor two'], DB::table('vendors')->orderBy('id')->pluck('name')->all());
    }

    public function test_vendor_creation_with_a_valid_branch_redirects_with_existing_success_message(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class);

        $response = $this->post(route('vendors.store'), $this->validVendorInput());

        $response->assertRedirect(route('vendors.index'));
        $response->assertSessionHas('success', 'Vendor created successfully.');
        $this->assertDatabaseHas('vendors', [
            'name' => 'New vendor',
            'branch_id' => 1,
            'account' => 'Purchases',
        ]);
        $this->assertSame(4, DB::table('vendors')->count());
        $this->assertSame(2, DB::table('vendors')->whereNull('branch_id')->count());
    }

    public function test_vendor_creation_rejects_an_invalid_branch_without_writing_a_vendor(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class);
        $input = $this->validVendorInput();
        $input['branch_id'] = 999;

        $response = $this->from(route('vendors.create'))->post(route('vendors.store'), $input);

        $response->assertRedirect(route('vendors.create'));
        $response->assertSessionHasErrors('branch_id');
        $this->assertSame(2, DB::table('vendors')->count());
    }

    public function test_vendor_creation_rejects_a_missing_branch_without_writing_a_vendor(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class);
        $input = $this->validVendorInput();
        unset($input['branch_id']);

        $response = $this->from(route('vendors.create'))->post(route('vendors.store'), $input);

        $response->assertRedirect(route('vendors.create'));
        $response->assertSessionHasErrors('branch_id');
        $this->assertSame(2, DB::table('vendors')->count());
    }

    public function test_add_vendor_page_renders_validation_errors_and_retains_old_values(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class);

        $response = $this->from(route('vendors.create'))->post(route('vendors.store'), [
            'name' => 'Retained vendor',
            'address' => 'Retained address',
            'phone_number' => '12345',
            'account' => 'Purchases',
            'terms_of_payment' => 'days',
            'days' => '0',
            'branch_id' => 999,
        ]);
        $response->assertRedirect(route('vendors.create'));

        $page = $this->get(route('vendors.create'));
        $page->assertOk();
        $page->assertSee('The selected branch id is invalid.');
        $page->assertSee('Retained vendor');
        $page->assertSee('Retained address');
        $page->assertSee('value="days" selected', false);
        $page->assertSee('id="days-input-container" style="display: none;"', false);
        $page->assertSee("if (termsOfPaymentSelect.value === 'days')", false);
        $page->assertSee('value="0"', false);
    }

    private function validVendorInput(): array
    {
        return [
            'branch_id' => 1,
            'name' => 'New vendor',
            'address' => 'New address',
            'phone_number' => '12345',
            'account' => 'Purchases',
            'terms_of_payment' => 'cod',
        ];
    }
}
