<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'price')) {
                $table->decimal('price', 10, 2)->nullable();
            }
        });

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM(
            'inspection_requested',
            'inspected',
            'quote_submitted',
            'quote_approved',
            'quote_rejected',
            'payment_held',
            'in_progress',
            'completed',
            'released'
        ) DEFAULT 'inspection_requested'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'price')) {
                $table->dropColumn('price');
            }
        });

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status VARCHAR(191) DEFAULT NULL");
    }
};
