<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SandNotificationTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'    => 'inspection_requested',
                'label'   => 'Inspection Requested',
                'type'    => 'inspection_requested',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم طلب معاينة جديدة للخدمة #{booking_id}',
                        'mail' => 'تم تقديم طلب معاينة جديد. يرجى مراجعة التفاصيل.',
                    ],
                    'en' => [
                        'push' => 'New inspection requested for booking #{booking_id}',
                        'mail' => 'A new inspection request has been submitted.',
                    ],
                ],
            ],
            [
                'name'    => 'quote_submitted',
                'label'   => 'Quote Submitted',
                'type'    => 'quote_submitted',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم تقديم عرض سعر بقيمة {quote_price} ريال للخدمة #{booking_id}',
                        'mail' => 'قام المزود بتقديم عرض سعر. يرجى مراجعته والموافقة عليه.',
                    ],
                    'en' => [
                        'push' => 'Quote of {quote_price} SAR submitted for booking #{booking_id}',
                        'mail' => 'The provider has submitted a quote. Please review and approve.',
                    ],
                ],
            ],
            [
                'name'    => 'quote_approved',
                'label'   => 'Quote Approved',
                'type'    => 'quote_approved',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم الموافقة على عرض السعر للخدمة #{booking_id}. يرجى البدء في العمل.',
                        'mail' => 'وافق العميل على عرض السعر. يمكنك الآن بدء الخدمة.',
                    ],
                    'en' => [
                        'push' => 'Quote approved for booking #{booking_id}. You can start the job.',
                        'mail' => 'The customer has approved the quote. You may now begin.',
                    ],
                ],
            ],
            [
                'name'    => 'quote_rejected',
                'label'   => 'Quote Rejected',
                'type'    => 'quote_rejected',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم رفض عرض السعر للخدمة #{booking_id}',
                        'mail' => 'العملاء رفض عرض السعر. يمكنك تقديم عرض معدل.',
                    ],
                    'en' => [
                        'push' => 'Quote rejected for booking #{booking_id}',
                        'mail' => 'The customer rejected the quote. You may submit a revised one.',
                    ],
                ],
            ],
            [
                'name'    => 'payment_held',
                'label'   => 'Payment Held in Escrow',
                'type'    => 'payment_held',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم حجز مبلغ {amount} ريال في حساب الضمان للخدمة #{booking_id}',
                        'mail' => 'تم تأمين مبلغ الخدمة في حساب الضمان. يمكنك البدء بأمان.',
                    ],
                    'en' => [
                        'push' => '{amount} SAR held in escrow for booking #{booking_id}',
                        'mail' => 'The service amount is secured in escrow. Proceed safely.',
                    ],
                ],
            ],
            [
                'name'    => 'payment_released',
                'label'   => 'Payment Released',
                'type'    => 'payment_released',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم الإفراج عن مبلغ {amount} ريال من حساب الضمان للخدمة #{booking_id}',
                        'mail' => 'تم تحويل المبلغ إلى محفظتك. شكراً لاستخدامك سند.',
                    ],
                    'en' => [
                        'push' => '{amount} SAR released from escrow for booking #{booking_id}',
                        'mail' => 'The amount has been transferred to your wallet. Thank you.',
                    ],
                ],
            ],
            [
                'name'    => 'investigation_opened',
                'label'   => 'Investigation Opened',
                'type'    => 'investigation_opened',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم فتح تحقيق للخدمة #{booking_id}. يرجى متابعة البريد الإلكتروني.',
                        'mail' => 'تم فتح تحقيق بخصوص الخدمة #{booking_id}. سيتم التواصل معك قريباً.',
                    ],
                    'en' => [
                        'push' => 'Investigation opened for booking #{booking_id}. Check your email.',
                        'mail' => 'An investigation has been opened. We will contact you.',
                    ],
                ],
            ],
            [
                'name'    => 'insurance_deducted',
                'label'   => 'Insurance Deducted',
                'type'    => 'insurance_deducted',
                'status'  => 1,
                'content' => [
                    'ar' => [
                        'push' => 'تم خصم {amount} ريال من التأمين الخاص بك.',
                        'mail' => 'تم خصم مبلغ من التأمين الخاص بك. لمزيد من التفاصيل، يرجى مراجعة لوحة التحكم.',
                    ],
                    'en' => [
                        'push' => '{amount} SAR deducted from your insurance deposit.',
                        'mail' => 'Insurance deducted. Please check the dashboard for details.',
                    ],
                ],
            ],
        ];

        foreach ($templates as $template) {
            $existing = DB::table('notification_templates')
                ->where('type', $template['type'])
                ->first();

            $id = $existing
                ? $existing->id
                : DB::table('notification_templates')->insertGetId([
                    'name'       => $template['name'],
                    'label'      => $template['label'],
                    'type'       => $template['type'],
                    'status'     => $template['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            foreach ($template['content'] as $locale => $channels) {
                foreach ($channels as $channel => $message) {
                    DB::table('notification_template_content_mappings')->updateOrInsert(
                        [
                            'notification_template_id' => $id,
                            'language'                 => $locale,
                            'channel'                  => $channel,
                        ],
                        [
                            'message'    => $message,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }
}
