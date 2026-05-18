<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('insurance_balance', 12, 2)
                  ->default(0)
                  ->after('loyalty_points')
                  ->comment('Current refundable insurance balance');

            $table->decimal('insurance_target', 12, 2)
                  ->default(100.00)
                  ->after('insurance_balance')
                  ->comment('Required insurance amount (default 100 SAR)');

            $table->enum('insurance_status', [
                'unpaid', 'partial', 'active', 'frozen', 'refunded',
            ])->default('unpaid')->after('insurance_target');

            $table->decimal('frozen_amount', 12, 2)
                  ->default(0)
                  ->after('insurance_status')
                  ->comment('Amount frozen during investigation');

            $table->index('insurance_status', 'users_insurance_status_idx');
        });

        Schema::create('insurance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('related');
            $table->decimal('amount', 12, 2);
            $table->enum('type', [
                'deposit', 'deduction', 'refund',
                'gradual_deduction', 'penalty',
            ]);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->text('reason')->nullable();
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('user_id');
            $table->index('type');
        });

        DB::table('settings')->updateOrInsert(
            ['type' => 'insurance', 'key' => 'insurance-config'],
            ['value' => json_encode([
                'default_target'      => 100,
                'currency'            => 'SAR',
                'allow_gradual'       => true,
                'gradual_percentage'  => 10,
                'auto_deduct'         => true,
            ])]
        );
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'insurance_balance',
                'insurance_target',
                'insurance_status',
                'frozen_amount',
            ]);
            $table->dropIndex('users_insurance_status_idx');
        });

        Schema::dropIfExists('insurance_transactions');

        DB::table('settings')
            ->where('type', 'insurance')
            ->delete();
    }
};
