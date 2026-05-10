<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add quote_price and quote_description directly to the bookings table.
 *
 * These columns store the provider's quoted price and description so that
 * Flutter can read them directly from the booking object without an extra
 * join to the quotes table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'quote_price')) {
                $table->decimal('quote_price', 10, 2)->nullable()->after('status');
            }
            if (! Schema::hasColumn('bookings', 'quote_description')) {
                $table->text('quote_description')->nullable()->after('quote_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'quote_description')) {
                $table->dropColumn('quote_description');
            }
            if (Schema::hasColumn('bookings', 'quote_price')) {
                $table->dropColumn('quote_price');
            }
        });
    }
};
