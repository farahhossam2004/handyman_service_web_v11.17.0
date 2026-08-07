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
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('purpose', 50)->default('registration');
            $table->string('account_type', 50)->default('customer');
            $table->string('msegat_request_id')->nullable()->index();
            // Temporary payload needed to complete registration after verification.
            // Never stores the OTP code itself or MSEGAT credentials.
            $table->json('metadata')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cooldown_until')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['phone', 'purpose']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};