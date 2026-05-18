<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investigation_logs', function (Blueprint $table) {
            $table->foreignId('provider_id')->nullable()->after('booking_id')
                  ->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->after('provider_id')
                  ->constrained('users')->cascadeOnDelete();
        });

        DB::statement("ALTER TABLE investigation_logs MODIFY COLUMN status ENUM(
            'open', 'under_investigation', 'under_review', 'resolved', 'closed', 'rejected'
        ) NOT NULL DEFAULT 'open'");

        Schema::table('investigation_activities', function (Blueprint $table) {
            $table->json('evidence')->nullable()->after('attachment_path')
                  ->comment('JSON array of evidence file paths/URLs');
        });
    }

    public function down(): void
    {
        Schema::table('investigation_logs', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['provider_id', 'customer_id']);
        });

        Schema::table('investigation_activities', function (Blueprint $table) {
            $table->dropColumn('evidence');
        });
    }
};
