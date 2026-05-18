<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('available_balance', 12, 2)->default(0)->after('amount')
                  ->comment('Withdrawable balance');
            $table->decimal('escrow_balance', 12, 2)->default(0)->after('available_balance')
                  ->comment('Held in escrow for active jobs');
            $table->decimal('insurance_balance', 12, 2)->default(0)->after('escrow_balance')
                  ->comment('Insurance deposit held by platform');
            $table->decimal('frozen_balance', 12, 2)->default(0)->after('insurance_balance')
                  ->comment('Frozen due to dispute/investigation');
        });

        DB::statement("UPDATE wallets SET available_balance = COALESCE(amount, 0)");
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['available_balance', 'escrow_balance', 'insurance_balance', 'frozen_balance']);
        });
    }
};
