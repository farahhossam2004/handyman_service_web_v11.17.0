<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_transaction_queue', function (Blueprint $table) {
            $table->id();
            $table->string('financial_trace_id', 100)->index();
            $table->string('operation_type', 50)->comment('escrow_hold / escrow_release / refund / commission / insurance');
            $table->morphs('operable');
            $table->decimal('amount', 12, 2)->default(0);
            $table->json('context')->nullable();
            $table->text('error_message')->nullable();
            $table->text('error_trace')->nullable();
            $table->tinyInteger('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->enum('status', ['pending_retry', 'processing', 'permanently_failed', 'recovered'])->default('pending_retry');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('operation_type');
            $table->index('next_retry_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_transaction_queue');
    }
};
