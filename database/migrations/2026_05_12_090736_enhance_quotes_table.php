<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('handyman_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->after('provider_id');

            $table->integer('estimated_duration')
                  ->nullable()
                  ->comment('Estimated job duration in minutes')
                  ->after('price');

            $table->text('inspection_notes')
                  ->nullable()
                  ->after('notes');

            $table->timestamp('approved_at')
                  ->nullable()
                  ->after('status');

            $table->timestamp('rejected_at')
                  ->nullable()
                  ->after('approved_at');

            $table->text('rejection_reason')
                  ->nullable()
                  ->after('rejected_at');

            $table->index('status');
            $table->index(['booking_id', 'status'], 'quotes_booking_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['handyman_id']);
            $table->dropColumn([
                'handyman_id',
                'estimated_duration',
                'inspection_notes',
                'approved_at',
                'rejected_at',
                'rejection_reason',
            ]);
            $table->dropIndex('quotes_booking_status_idx');
        });
    }
};
