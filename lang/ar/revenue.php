<?php

// رسائل صفحات الإيرادات في الدراسة وقوائم الأنواع (من الخادم).
// كلمات الشاشات في resources/js/lang/ar.js. يجب أن يطابق هذا الملف lang/en/revenue.php مفتاحًا بمفتاح.
return [
    'messages' => [
        'rooms_saved' => 'تم حفظ إيرادات الغرف.',
        'foods_saved'      => 'تم حفظ إيرادات الأغذية والمشروبات.',
        'casinos_saved'    => 'تم حفظ إيرادات الألعاب.',
        'meetings_saved'   => 'تم حفظ إيرادات قاعات الاجتماعات.',
        'others_saved'     => 'تم حفظ الإيرادات الأخرى.',
        'types_saved' => 'تم حفظ قائمة :list.',
    ],

    'errors' => [
        'room_twice'       => 'يمكن استخدام كل نوع غرفة مرة واحدة فقط.',
        'facility_twice'   => 'يمكن أن يظهر كل منفذ مرة واحدة فقط.',
        'plan_total'       => 'يجب أن يكون مجموع حصص خطة الموسمية 100%.',
        'share_total'      => 'في :year يجب أن يكون مجموع حصص قنوات الحجز 100%.',
        'collection_total' => 'يجب أن يكون مجموع نسب التحصيل الأربع 100%.',
        'type_in_use'      => 'هذه الأنواع مستخدمة في دراسات ولا يمكن حذفها: :names.',
        'type_unknown'     => 'هذا النوع غير موجود في قائمة شركتك.',
        'type_twice'       => 'هذا الاسم موجود بالفعل في القائمة.',
    ],

    'lists' => [
        'rooms'    => 'أنواع الغرف',
        'foods'    => 'منافذ الأغذية والمشروبات',
        'casinos'  => 'مناطق الألعاب',
        'meetings' => 'قاعات الاجتماعات',
        'others'   => 'أنواع الإيرادات الأخرى',
    ],

    'fields' => [
        'currency'           => 'العملة',
        'adr'                => 'متوسط سعر الغرفة اليومي',
        'escalation'         => 'نسبة الزيادة',
        'annual'             => 'نسبة الزيادة السنوية',
        'collection_rate'    => 'نسبة التحصيل',
        'due_days'           => 'الاستحقاق بالأيام',
        'exchange_year'      => 'سعر الصرف في :year',
        'occupancy_year'     => 'الإشغال في :year',
        'share_year'         => 'حصة الإيراد في :year',
        'discount_year'      => 'الخصم في :year',
        'seasonality_month'  => 'الحد الأقصى للإشغال في الشهر :month',
        'type_title'         => 'الاسم',
        'cover_value'        => 'قيمة الوجبة',
        'method'             => 'طريقة الإسقاط',
        'capture_year'       => 'نسبة استقطاب النزلاء في :year',
        'meals_year'         => 'الوجبات لكل نزيل في :year',
        'target_year'        => 'عدد الوجبات في :year',
        'rooms_percent_year' => 'النسبة من إيرادات الغرف في :year',
        'charges_value'      => 'قيمة الرسوم',
        'guests_target_year' => 'عدد النزلاء يوميًا في :year',
        'meeting_occupancy_year' => 'الإشغال في :year',
        'fb_percent_year'    => 'النسبة من إيرادات الأغذية والمشروبات في :year',
        'seasonality_share_month' => 'حصة الشهر :month',
    ],
];
