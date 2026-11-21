<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionPlansAndSiteBuilderTables extends Migration
{
    public function up()
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->decimal('monthly_price', 15, 2)->default(0);
            $table->unsignedInteger('yearly_price')->default(0);
            $table->text('description')->nullable();
            $table->text('features')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key_name', 100)->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::create('site_slides', function (Blueprint $table) {
            $table->increments('id');
            $table->string('eyebrow')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('primary_label')->nullable();
            $table->string('primary_url')->nullable();
            $table->string('secondary_label')->nullable();
            $table->string('secondary_url')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        foreach ([
            ['Basic', 'basic', 7500, 75000, 'For independent businesses starting with organised finance.', false, 1],
            ['Standard', 'standard', 15000, 150000, 'For growing teams that need connected operations and reports.', true, 2],
            ['Premium', 'premium', 30000, 300000, 'For established organisations requiring maximum control.', false, 3],
        ] as $plan) {
            DB::table('subscription_plans')->insert([
                'name' => $plan[0], 'slug' => $plan[1], 'monthly_price' => $plan[2],
                'yearly_price' => $plan[3], 'description' => $plan[4],
                'features' => json_encode([
                    'Accounting and financial reports',
                    'Secure business workspace',
                    'Customer and supplier management',
                ]),
                'is_featured' => $plan[5], 'is_active' => true, 'sort_order' => $plan[6],
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        foreach ([
            'brand_name' => 'AYULEDGERS',
            'tagline' => 'Business clarity, built in.',
            'hero_title' => 'Run every part of your business with confidence.',
            'hero_text' => 'Bring accounting, inventory, customers, suppliers, payroll and reporting together in one secure workspace.',
            'features_title' => 'Built for modern business operations',
            'cta_title' => 'Start building a stronger business today.',
            'cta_text' => 'Set up your workspace in minutes and keep every financial decision connected.',
        ] as $key => $value) {
            DB::table('site_settings')->insert([
                'key_name' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        DB::table('site_slides')->insert([
            'eyebrow' => 'Complete financial workspace',
            'title' => 'Know where your business stands.',
            'description' => 'Real-time ledgers, reports and dashboards help you act with confidence.',
            'primary_label' => 'Start free setup', 'primary_url' => '/register',
            'secondary_label' => 'Explore plans', 'secondary_url' => '/#pricing',
            'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('site_slides');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('subscription_plans');
    }
}
