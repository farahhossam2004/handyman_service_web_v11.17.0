<?php

return [

    /*
     *
     * Shared translations.
     *
     */
    'title' => 'المثبت',
    'next' => 'الخطوة التالية',
    'back' => 'السابق',
    'finish' => 'تثبيت',
    'forms' => [
        'errorTitle' => 'The Following errors occurred:',
    ],

    /*
     *
     * Home page translations.
     *
     */
    'welcome' => [
        'templateTitle' => 'مرحباً',
        'title'   => 'المثبت',
        'message' => 'معالج التثبيت والإعداد السهل.',
        'next'    => 'التحقق من المتطلبات',
    ],

    /*
     *
     * Requirements page translations.
     *
     */
    'requirements' => [
        'templateTitle' => 'الخطوة 1 | متطلبات الخادم',
        'title' => 'متطلبات الخادم',
        'next'    => 'التحقق من الصلاحيات',
    ],

    /*
     *
     * Permissions page translations.
     *
     */
    'permissions' => [
        'templateTitle' => 'الخطوة 2 | الصلاحيات',
        'title' => 'الصلاحيات',
        'next' => 'إعداد البيئة',
    ],

    /*
     *
     * Environment page translations.
     *
     */
    'environment' => [
        'menu' => [
            'templateTitle' => 'الخطوة 3 | إعدادات البيئة',
            'title' => 'إعدادات البيئة',
            'desc' => 'يرجى تحديد كيفية تكوين ملف <code>.env</code> للتطبيق.',
            'wizard-button' => 'إعداد المعالج',
            'classic-button' => 'محرر النص الكلاسيكي',
        ],
        'wizard' => [
            'templateTitle' => 'الخطوة 3 | إعدادات البيئة | المعالج الموجه',
            'title' => 'معالج <code>.env</code> الموجه',
            'tabs' => [
                'environment' => 'البيئة',
                'database' => 'قاعدة البيانات',
                'application' => 'التطبيق',
            ],
            'form' => [
                'name_required' => 'اسم البيئة مطلوب.',
                'app_name_label' => 'اسم التطبيق',
                'app_name_placeholder' => 'اسم التطبيق',
                'app_environment_label' => 'بيئة التطبيق',
                'app_environment_label_local' => 'محلي',
                'app_environment_label_developement' => 'تطوير',
                'app_environment_label_qa' => 'اختبار',
                'app_environment_label_production' => 'إنتاج',
                'app_environment_label_other' => 'أخرى',
                'app_environment_placeholder_other' => 'أدخل بيئتك...',
                'app_debug_label' => 'تصحيح الأخطاء',
                'app_debug_label_true' => 'صحيح',
                'app_debug_label_false' => 'خطأ',
                'app_log_level_label' => 'مستوى سجل التطبيق',
                'app_log_level_label_debug' => 'تصحيح',
                'app_log_level_label_info' => 'معلومات',
                'app_log_level_label_notice' => 'إشعار',
                'app_log_level_label_warning' => 'تحذير',
                'app_log_level_label_error' => 'خطأ',
                'app_log_level_label_critical' => 'حرج',
                'app_log_level_label_alert' => 'تنبيه',
                'app_log_level_label_emergency' => 'طوارئ',
                'app_url_label' => 'رابط التطبيق',
                'app_url_placeholder' => 'رابط التطبيق',
                'db_connection_failed' => 'تعذر الاتصال بقاعدة البيانات.',
                'db_connection_label' => 'اتصال قاعدة البيانات',
                'db_connection_label_mysql' => 'mysql',
                'db_connection_label_sqlite' => 'sqlite',
                'db_connection_label_pgsql' => 'pgsql',
                'db_connection_label_sqlsrv' => 'sqlsrv',
                'db_host_label' => 'مضيف قاعدة البيانات',
                'db_host_placeholder' => 'مضيف قاعدة البيانات',
                'db_port_label' => 'منفذ قاعدة البيانات',
                'db_port_placeholder' => 'منفذ قاعدة البيانات',
                'db_name_label' => 'اسم قاعدة البيانات',
                'db_name_placeholder' => 'اسم قاعدة البيانات',
                'db_username_label' => 'اسم مستخدم قاعدة البيانات',
                'db_username_placeholder' => 'اسم مستخدم قاعدة البيانات',
                'db_password_label' => 'كلمة مرور قاعدة البيانات',
                'db_password_placeholder' => 'كلمة مرور قاعدة البيانات',

                'app_tabs' => [
                    'more_info' => 'مزيد من المعلومات',
                    'broadcasting_title' => 'البث، التخزين المؤقت، الجلسة، والطابور',
                    'broadcasting_label' => 'مشغل البث',
                    'broadcasting_placeholder' => 'مشغل البث',
                    'cache_label' => 'مشغل التخزين المؤقت',
                    'cache_placeholder' => 'مشغل التخزين المؤقت',
                    'session_label' => 'مشغل الجلسة',
                    'session_placeholder' => 'مشغل الجلسة',
                    'queue_label' => 'مشغل الطابور',
                    'queue_placeholder' => 'مشغل الطابور',
                    'redis_label' => 'مشغل Redis',
                    'redis_host' => 'مضيف Redis',
                    'redis_password' => 'كلمة مرور Redis',
                    'redis_port' => 'منفذ Redis',

                    'mail_label' => 'البريد',
                    'mail_driver_label' => 'مشغل البريد',
                    'mail_driver_placeholder' => 'مشغل البريد',
                    'mail_host_label' => 'مضيف البريد',
                    'mail_host_placeholder' => 'مضيف البريد',
                    'mail_port_label' => 'منفذ البريد',
                    'mail_port_placeholder' => 'منفذ البريد',
                    'mail_username_label' => 'اسم مستخدم البريد',
                    'mail_username_placeholder' => 'اسم مستخدم البريد',
                    'mail_password_label' => 'كلمة مرور البريد',
                    'mail_password_placeholder' => 'كلمة مرور البريد',
                    'mail_encryption_label' => 'تشفير البريد',
                    'mail_encryption_placeholder' => 'تشفير البريد',

                    'pusher_label' => 'Pusher',
                    'pusher_app_id_label' => 'معرف تطبيق Pusher',
                    'pusher_app_id_palceholder' => 'معرف تطبيق Pusher',
                    'pusher_app_key_label' => 'مفتاح تطبيق Pusher',
                    'pusher_app_key_palceholder' => 'مفتاح تطبيق Pusher',
                    'pusher_app_secret_label' => 'سر تطبيق Pusher',
                    'pusher_app_secret_palceholder' => 'سر تطبيق Pusher',
                ],
                'buttons' => [
                    'setup_database' => 'إعداد قاعدة البيانات',
                    'setup_application' => 'إعداد التطبيق',
                    'install' => 'تثبيت',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => 'الخطوة 3 | إعدادات البيئة | المحرر الكلاسيكي',
            'title' => 'محرر البيئة الكلاسيكي',
            'save' => 'حفظ .env',
            'back' => 'استخدام معالج النموذج',
            'install' => 'حفظ وتثبيت',
        ],
        'success' => 'تم حفظ إعدادات ملف .env الخاصة بك.',
        'errors' => 'تعذر حفظ ملف .env، يرجى إنشاؤه يدوياً.',
    ],

    'install' => 'تثبيت',

    /*
     *
     * Installed Log translations.
     *
     */
    'installed' => [
        'success_log_message' => 'تم تثبيت المثبت بنجاح على ',
    ],

    /*
     *
     * Final page translations.
     *
     */
    'final' => [
        'title' => 'اكتمل التثبيت',
        'templateTitle' => 'اكتمل التثبيت',
        'finished' => 'تم تثبيت التطبيق بنجاح.',
        'migration' => 'مخرجات الترحيل والبذر:',
        'console' => 'مخرجات وحدة التحكم:',
        'log' => 'إدخال سجل التثبيت:',
        'env' => 'ملف .env النهائي:',
        'exit' => 'موقع المستخدم',
        'admin_panel' => 'موقع الإدارة',
    ],

    /*
     *
     * Update specific translations
     *
     */
    'updater' => [
        /*
         *
         * Shared translations.
         *
         */
        'title' => 'Laravel Updater',

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'welcome' => [
            'title'   => 'مرحباً بك في أداة التحديث',
            'message' => 'مرحباً بك في معالج التحديث.',
        ],

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'overview' => [
            'title'   => 'نظرة عامة',
            'message' => 'يوجد تحديث واحد.|يوجد :number تحديثات.',
            'install_updates' => 'تثبيت التحديثات',
        ],

        /*
         *
         * Final page translations.
         *
         */
        'final' => [
            'title' => 'انتهى',
            'finished' => 'تم تحديث قاعدة بيانات التطبيق بنجاح.',
            'exit' => 'انقر هنا للخروج',
        ],

        'log' => [
            'success_message' => 'تم تحديث المثبت بنجاح على ',
        ],
    ],
];
