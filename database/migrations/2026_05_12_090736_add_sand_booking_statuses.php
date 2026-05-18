<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM(
            'pending_inspection',
            'inspected',
            'waiting_quote',
            'quote_submitted',
            'quoted',
            'quote_approved',
            'quote_rejected',
            'payment_held',
            'in_progress',
            'completed',
            'released',
            'cancelled',
            'pending',
            'confirmed',
            'disputed',
            'under_investigation',
            'resolved',
            'on_the_way',
            'hold',
            'pending_approval',
            'rejected',
            'accept'
        ) NOT NULL DEFAULT 'pending_inspection'");

        DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_status ENUM(
            'pending', 'paid', 'escrow', 'pending_release',
            'released', 'refunded', 'held', 'advanced_paid',
            'frozen_under_investigation', 'partially_released'
        ) DEFAULT 'pending'");

        Schema::table('bookings', function (Blueprint $table) {
            $table->text('dispute_reason')->nullable()->after('reason');
            $table->text('investigation_notes')->nullable()->after('dispute_reason');
            $table->timestamp('frozen_until')->nullable()->after('investigation_notes');
            $table->foreignId('investigated_by')->nullable()
                  ->constrained('users')->nullOnDelete()->after('frozen_until');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('investigated_by');
            $table->dropColumn(['dispute_reason', 'investigation_notes', 'frozen_until']);
        });
    }
};
