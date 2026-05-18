<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $newStatuses = [
            ['value' => 'disputed',              'label' => 'Disputed',              'status' => 1, 'sequence' => 0],
            ['value' => 'under_investigation',    'label' => 'Under Investigation',  'status' => 1, 'sequence' => 0],
            ['value' => 'resolved',              'label' => 'Resolved',              'status' => 1, 'sequence' => 0],
            ['value' => 'inspection_requested',  'label' => 'Inspection Requested',  'status' => 1, 'sequence' => 0],
            ['value' => 'inspected',             'label' => 'Inspected',             'status' => 1, 'sequence' => 0],
            ['value' => 'quote_submitted',       'label' => 'Quote Submitted',       'status' => 1, 'sequence' => 0],
            ['value' => 'payment_held',          'label' => 'Payment Held',          'status' => 1, 'sequence' => 0],
            ['value' => 'released',              'label' => 'Released',              'status' => 1, 'sequence' => 0],
        ];

        foreach ($newStatuses as $status) {
            DB::table('booking_statuses')->updateOrInsert(
                ['value' => $status['value']],
                $status
            );
        }
    }

    public function down(): void
    {
        DB::table('booking_statuses')
          ->whereIn('value', [
              'disputed', 'under_investigation', 'resolved',
              'inspection_requested', 'inspected', 'quote_submitted',
              'payment_held', 'released',
          ])
          ->delete();
    }
};
