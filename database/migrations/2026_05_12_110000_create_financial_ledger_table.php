<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50)->comment('commission / payout / refund / escrow_hold / escrow_release / insurance_deduction / adjustment');
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('balance_before', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('booking_id / dispute_id / escrow_id');
            $table->string('reference_type', 50)->nullable()->comment('booking / dispute / escrow');
            $table->string('status', 20)->default('confirmed')->comment('pending / confirmed');
            $table->text('description')->nullable();
            $table->string('transaction_key', 100)->nullable()->unique()->comment('Idempotency key');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('transaction_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_ledger');
    }
};
