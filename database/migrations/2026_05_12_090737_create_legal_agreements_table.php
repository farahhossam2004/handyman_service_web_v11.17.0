<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_agreements', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->text('content_ar');
            $table->text('content_en')->nullable();
            $table->string('version', 20)->default('1.0');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['type', 'version']);
            $table->index('type');
            $table->index('is_active');
        });

        Schema::create('agreement_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_agreement_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('accepted_at');
            $table->unique(['user_id', 'legal_agreement_id'], 'user_agreement_unique');
            $table->index('user_id');
            $table->index('legal_agreement_id');
            $table->timestamps();
        });

        DB::table('legal_agreements')->insert([
            [
                'type'       => 'provider_agreement',
                'content_ar' => 'أقر أنا الفني بمسؤوليتي الكاملة عن جودة العمل المقدم للعميل. أتعهد بالالتزام بمعايير الجودة والأمان المعتمدة في منصة سند. أقر بأن أي مخالفة ستؤدي إلى خصم من التأمين أو تجميد الحساب وفقاً لسياسة المنصة.',
                'content_en' => 'I, the technician, acknowledge full responsibility for the quality of work provided to the customer. I commit to adhering to the quality and safety standards approved by the Sand platform. I acknowledge that any violation will result in insurance deduction or account suspension according to platform policy.',
                'version'    => '1.0',
                'is_active'  => true,
                'created_by' => 1,
            ],
            [
                'type'       => 'customer_agreement',
                'content_ar' => 'نحن في سند نضمن لك جودة الخدمة المقدمة. في حال وجود أي مشكلة، يرجى التواصل مع فريق الدعم خلال 24 ساعة من انتهاء الخدمة. سيتم الاحتفاظ بالمبلغ في حساب ضمان حتى تأكيد رضاك عن الخدمة.',
                'content_en' => 'At Sand, we guarantee the quality of service provided. If there is any issue, please contact the support team within 24 hours of service completion. The amount will be held in escrow until your satisfaction with the service is confirmed.',
                'version'    => '1.0',
                'is_active'  => true,
                'created_by' => 1,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_acceptances');
        Schema::dropIfExists('legal_agreements');
    }
};
