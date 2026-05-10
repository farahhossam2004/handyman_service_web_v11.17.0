<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Harden Inspection Workflow Migration
 *
 * Consolidates all booking status and payment_status ENUM values so that
 * every workflow stage is a valid database value.
 *
 * Booking status state machine:
 *   pending_inspection → waiting_quote → quoted → quote_approved
 *   → in_progress → completed
 *   (quote_rejected is a side-step from quoted, looping back to waiting_quote)
 *
 * payment_status on bookings (escrow column):
 *   null → pending → escrow → pending_release → released
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Consolidate booking.status ENUM ────────────────────────────────
        // We must list ALL possible values across the old system AND the new
        // workflow so no existing row is invalid.
        DB::statement("
            ALTER TABLE bookings
            MODIFY COLUMN status ENUM(
                'pending',
                'accept',
                'on_the_way',
                'in_progress',
                'hold',
                'pending_approval',
                'completed',
                'cancelled',
                'rejected',
                'pending_inspection',
                'waiting_quote',
                'quoted',
                'quote_approved',
                'quote_rejected',
                -- legacy values from 2026_05_07 migration (kept for backward compat)
                'inspection_requested',
                'inspected',
                'quote_submitted',
                'payment_held',
                'released'
            ) DEFAULT 'pending'
        ");

        // ── 2. Fix booking.payment_status ENUM ────────────────────────────────
        // The 2026_05_07 migration created it with (held, released, refunded).
        // We need to expand it to support the full escrow lifecycle.
        if (Schema::hasColumn('bookings', 'payment_status')) {
            DB::statement("
                ALTER TABLE bookings
                MODIFY COLUMN payment_status ENUM(
                    'pending',
                    'paid',
                    'escrow',
                    'pending_release',
                    'released',
                    'refunded',
                    'held',
                    'advanced_paid'
                ) NULL DEFAULT NULL
            ");
        }

        // ── 3. Ensure `reason` column exists (used by rejectQuote) ────────────
        if (! Schema::hasColumn('bookings', 'reason')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->text('reason')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        // Restore original payment_status enum
        if (Schema::hasColumn('bookings', 'payment_status')) {
            DB::statement("
                ALTER TABLE bookings
                MODIFY COLUMN payment_status ENUM('held','released','refunded') NULL DEFAULT NULL
            ");
        }

        // Restore booking status to varchar (safest rollback)
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status VARCHAR(191) DEFAULT NULL");
    }
};
