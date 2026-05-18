<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->constrained('users')->cascadeOnDelete();
            $table->text('dispute_reason');
            $table->enum('status', [
                'open', 'under_investigation', 'resolved', 'closed',
            ])->default('open');
            $table->enum('resolution', [
                'pending', 'released_to_provider', 'refunded_to_customer',
                'partial_refund', 'penalty_deducted', 'dismissed',
            ])->default('pending');
            $table->decimal('penalty_amount', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->text('admin_notes')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index('booking_id');
        });

        Schema::create('investigation_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')
                  ->constrained('investigation_logs')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
            $table->index('investigation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigation_activities');
        Schema::dropIfExists('investigation_logs');
    }
};
