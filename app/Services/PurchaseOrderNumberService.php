<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseOrderNumberService
{
    /** Must be called inside the Purchase Order database transaction. */
    public function next(): string
    {
        $now = now();
        DB::table('invoice_sequences')->insertOrIgnore([
            'document_type' => 'purchase_order',
            'current_number' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sequence = DB::table('invoice_sequences')
            ->where('document_type', 'purchase_order')
            ->lockForUpdate()
            ->first();

        if (!$sequence) {
            throw new RuntimeException('The Purchase Order number sequence is unavailable.');
        }

        $next = ((int) $sequence->current_number) + 1;
        DB::table('invoice_sequences')
            ->where('document_type', 'purchase_order')
            ->update(['current_number' => $next, 'updated_at' => $now]);

        return 'PO-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}