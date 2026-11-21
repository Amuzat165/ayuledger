<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SupplierInvoiceNumberService
{
    public function next(): string
    {
        $now = now();
        DB::table('invoice_sequences')->insertOrIgnore([
            'document_type' => 'supplier_invoice', 'current_number' => 0,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $sequence = DB::table('invoice_sequences')
            ->where('document_type', 'supplier_invoice')->lockForUpdate()->first();
        if (!$sequence) {
            throw new \RuntimeException('Supplier Invoice numbering is unavailable.');
        }
        $next = (int) $sequence->current_number + 1;
        DB::table('invoice_sequences')->where('document_type', 'supplier_invoice')->update([
            'current_number' => $next, 'updated_at' => $now,
        ]);
        return 'SI-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
