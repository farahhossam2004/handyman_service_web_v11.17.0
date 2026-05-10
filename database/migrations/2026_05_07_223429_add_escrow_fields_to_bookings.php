<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'payment_status')) {
                $table->enum('payment_status', ['held', 'released', 'refunded'])->nullable();
            }
            if (!Schema::hasColumn('bookings', 'quote_id')) {
                $table->unsignedBigInteger('quote_id')->nullable();
                $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'quote_id')) {
                $table->dropForeign(['quote_id']);
                $table->dropColumn('quote_id');
            }
            if (Schema::hasColumn('bookings', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};
