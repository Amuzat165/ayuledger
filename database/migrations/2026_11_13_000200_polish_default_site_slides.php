<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PolishDefaultSiteSlides extends Migration
{
    public function up()
    {
        DB::table('site_slides')
            ->where('title', 'Easily prepare accounting ledgers for your business')
            ->update([
                'eyebrow' => 'Invoicing and receipts',
                'title' => 'Create invoices and receipts in minutes',
                'description' => 'Build professional invoices, send receipts and track payment status from one clear workspace.',
                'primary_label' => 'Get started',
                'primary_url' => '/register',
                'updated_at' => now(),
            ]);

        DB::table('site_slides')
            ->where('title', 'Hello')
            ->update([
                'eyebrow' => 'Connected operations',
                'title' => 'Keep your business moving forward',
                'description' => 'Bring sales, purchases, inventory and financial reports together in one secure workspace.',
                'primary_label' => 'Get started',
                'primary_url' => '/register',
                'updated_at' => now(),
            ]);

        DB::table('site_slides')->where('primary_label', 'Start Here')->where('primary_url', '/login')
            ->update(['primary_label' => 'Get started', 'primary_url' => '/register', 'updated_at' => now()]);
    }

    public function down()
    {
    }
}
