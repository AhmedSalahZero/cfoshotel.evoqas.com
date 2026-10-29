<?php

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — الدراسات: الرسائل (العربية)
//  Location: lang/ar/studies.php
//  Keep key-for-key in sync with lang/en/studies.php (a test checks it).
// ══════════════════════════════════════════════════════════════════

return [
    'messages' => [
        'created'    => 'تم إنشاء الدراسة «:name».',
        'saved'      => 'تم حفظ الدراسة «:name».',
        'copied'     => 'تم إنشاء نسخة: «:name».',
        'deleted'    => 'تم حذف الدراسة «:name».',
        'shared'     => 'تم تحديث مشاركة «:name».',
        'view_only'  => 'هذه الدراسة مشاركة معك للاطّلاع فقط.',
        'owner_only' => 'هذا الإجراء متاح لصاحب الدراسة أو لمدير الشركة فقط.',
        'excel_not_ready' => 'لا يمكن حساب هذه الدراسة بعد: أضف الغرف أو الإيرادات الأخرى والتواريخ قبل تنزيل نموذج Excel.',
        'excel_failed' => 'تعذر بناء نموذج Excel لهذه الدراسة. راجع أرقامها وحاول مرة أخرى.',
    ],

    'errors' => [
        'start_month'      => 'اختر شهر بداية الدراسة.',
        'type_required'    => 'اختر نوعًا من القائمة.',
        'type_twice'       => 'هذا النوع مستخدم بالفعل. يمكن استخدام كل نوع مرة واحدة.',
        'channel_required' => 'اختر قناة حجز واحدة على الأقل، أو أوقف قنوات الحجز.',
        'channel_twice'    => 'يمكن اختيار قناة الحجز مرة واحدة فقط.',
    ],

    'channels' => [
        'direct-reservations'   => 'الحجز المباشر',
        'agency'                => 'وكالات السفر',
        'online-booking-engine' => 'محرك الحجز الإلكتروني',
        'corporates'            => 'الشركات',
        'others-channels'       => 'قنوات أخرى',
    ],

    'fields' => [
        'study_name'                 => 'اسم الدراسة',
        'property_name'              => 'اسم العقار',
        'property_status'            => 'حالة العقار',
        'star_rating'                => 'تصنيف النجوم',
        'country_id'                 => 'الدولة',
        'state_id'                   => 'المحافظة',
        'region'                     => 'المنطقة',
        'study_start'                => 'شهر بداية الدراسة',
        'duration_in_years'          => 'مدة الدراسة',
        'development_start_month'    => 'بداية التطوير',
        'development_duration'       => 'مدة التطوير',
        'operation_start_month'      => 'بداية التشغيل',
        'financial_year_start_month' => 'شهر بداية السنة المالية',
        'main_functional_currency'   => 'العملة الرئيسية',
        'additional_currency'        => 'العملة الإضافية',
        'exchange_rate'              => 'سعر الصرف',
        'corporate_taxes_rate'       => 'معدل ضريبة الشركات',
        'investment_return_rate'     => 'العائد المطلوب على الاستثمار',
        'perpetual_growth_rate'      => 'معدل النمو الدائم',
        'copy_name'                  => 'اسم النسخة',
        'channels'                   => 'قنوات الحجز',
        'count_rooms'                => 'عدد الغرف',
        'value_rooms'                => 'عدد النزلاء لكل غرفة',
        'type_rooms'                 => 'نوع الغرفة',
        'rows_rooms'                 => 'الغرف',
        'count_foods'                => 'عدد المرافق',
        'value_foods'                => 'سعة النزلاء',
        'type_foods'                 => 'نوع المطعم أو المقهى',
        'rows_foods'                 => 'مرافق الأغذية والمشروبات',
        'count_casinos'              => 'عدد المرافق',
        'value_casinos'              => 'سعة النزلاء',
        'type_casinos'               => 'نوع مرفق الألعاب',
        'rows_casinos'               => 'مرافق الألعاب',
        'count_meetings'             => 'عدد المرافق',
        'value_meetings'             => 'سعة النزلاء',
        'type_meetings'              => 'نوع قاعة الاجتماعات',
        'rows_meetings'              => 'مرافق الاجتماعات',
        'count_others'               => 'عدد المرافق',
        'value_others'               => 'سعة النزلاء',
        'type_others'                => 'نوع الإيراد الآخر',
        'rows_others'                => 'مرافق الإيرادات الأخرى',
    ],
];
