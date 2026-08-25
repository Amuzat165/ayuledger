<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

trait GeneratesInvoiceNumber
{
    public static function bootGeneratesInvoiceNumber()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::nextInvoiceNumber();
            }
        });
    }

    public static function nextInvoiceNumber()
    {
        return DB::transaction(function () {
            $sequence = DB::table('invoice_sequences')
                ->where('document_type', static::INVOICE_SEQUENCE)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                DB::table('invoice_sequences')->insert([
                    'document_type' => static::INVOICE_SEQUENCE,
                    'current_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $currentNumber = 0;
            } else {
                $currentNumber = $sequence->current_number;
            }

            $nextNumber = $currentNumber + 1;
            DB::table('invoice_sequences')
                ->where('document_type', static::INVOICE_SEQUENCE)
                ->update(['current_number' => $nextNumber, 'updated_at' => now()]);

            return str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    public static function nextInvoiceNumberPreview()
    {
        $sequence = DB::table('invoice_sequences')
            ->where('document_type', static::INVOICE_SEQUENCE)
            ->first();

        return str_pad(($sequence ? $sequence->current_number : 0) + 1, 4, '0', STR_PAD_LEFT);
    }
}
