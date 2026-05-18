<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->morphs('escrowable');
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('held_amount', 12, 2)->default(0);
            $table->decimal('released_amount', 12, 2)->default(0);
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->decimal('penalty_deducted', 12, 2)->default(0);
            $table->enum('status', [
                'held', 'released', 'refunded',
                'frozen_under_investigation', 'partially_released',
            ])->default('held');
            $table->timestamp('held_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('frozen_at')->nullable();
            $table->timestamp('scheduled_release_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index('customer_id');
            $table->index('provider_id');
            $table->index(['escrowable_type', 'escrowable_id'], 'escrowable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_transactions');
    }
};
