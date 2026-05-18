<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_action_locks', function (Blueprint $table) {
            $table->id();
            $table->string('lock_key', 100)->unique()->comment('e.g., refund:booking:123, release:escrow:456');
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('action_type', 50);
            $table->morphs('lockable');
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('lock_key');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_action_locks');
    }
};
