<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Add inspection-based workflow statuses to booking_statuses table.
 *
 * New flow:
 *   pending_inspection → waiting_quote → quoted → quote_approved
 *   → quote_rejected → in_progress → completed
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $newStatuses = [
            [
                'label'      => 'Pending Inspection',
                'value'      => 'pending_inspection',
                'sequence'   => 12,
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label'      => 'Waiting Quote',
                'value'      => 'waiting_quote',
                'sequence'   => 13,
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label'      => 'Quoted',
                'value'      => 'quoted',
                'sequence'   => 14,
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label'      => 'Quote Approved',
                'value'      => 'quote_approved',
                'sequence'   => 15,
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label'      => 'Quote Rejected',
                'value'      => 'quote_rejected',
                'sequence'   => 16,
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($newStatuses as $status) {
            $exists = DB::table('booking_statuses')
                ->where('value', $status['value'])
                ->exists();

            if (! $exists) {
                DB::table('booking_statuses')->insert($status);
            }
        }
    }

    public function down(): void
    {
        $values = [
            'pending_inspection',
            'waiting_quote',
            'quoted',
            'quote_approved',
            'quote_rejected',
        ];

        DB::table('booking_statuses')->whereIn('value', $values)->delete();
    }
};
