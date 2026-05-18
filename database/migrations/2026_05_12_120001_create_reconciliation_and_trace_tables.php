<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->enum('status', ['balanced', 'warning', 'critical_mismatch'])->default('balanced');
            $table->json('summary')->nullable();
            $table->json('wallet_checks')->nullable();
            $table->json('escrow_checks')->nullable();
            $table->json('insurance_checks')->nullable();
            $table->json('ledger_checks')->nullable();
            $table->json('discrepancies')->nullable();
            $table->integer('total_checked')->default(0);
            $table->integer('total_errors')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('report_date');
        });

        Schema::create('financial_trace_logs', function (Blueprint $table) {
            $table->id();
            $table->string('financial_trace_id', 100)->index();
            $table->string('operation_type', 50);
            $table->string('step', 50)->comment('before_execution / after_execution / failure / rollback');
            $table->string('service', 100)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->json('context')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['financial_trace_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_trace_logs');
        Schema::dropIfExists('reconciliation_reports');
    }
};
