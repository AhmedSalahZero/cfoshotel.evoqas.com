<?php

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Studies: messages (English)
//  Location: lang/en/studies.php
//  Keep key-for-key in sync with lang/ar/studies.php (a test checks it).
// ══════════════════════════════════════════════════════════════════

return [
    'messages' => [
        'created'    => 'The study ":name" was created.',
        'saved'      => 'The study ":name" was saved.',
        'copied'     => 'A copy was created: ":name".',
        'deleted'    => 'The study ":name" was deleted.',
        'shared'     => 'Sharing of ":name" was updated.',
        'view_only'  => 'This study is shared with you to view only.',
        'owner_only' => 'Only the owner of the study or the company administrator can do this.',
        'excel_not_ready' => 'This study cannot be calculated yet: add its rooms or other revenue and its dates before downloading the Excel model.',
        'excel_failed' => 'The Excel model could not be built for this study. Please check its figures and try again.',
    ],

    'errors' => [
        'start_month'      => 'Choose the month the study starts.',
        'type_required'    => 'Choose a type from the list.',
        'type_twice'       => 'This type is already used. Each type can appear once.',
        'channel_required' => 'Choose at least one reservation channel, or switch the channels off.',
        'channel_twice'    => 'A reservation channel can be chosen once.',
    ],

    'channels' => [
        'direct-reservations'   => 'Direct reservations',
        'agency'                => 'Agency',
        'online-booking-engine' => 'Online booking engine',
        'corporates'            => 'Corporates',
        'others-channels'       => 'Other channels',
    ],

    // Names of the boxes, used inside the messages ("The :attribute field is required").
    'fields' => [
        'study_name'                 => 'study name',
        'property_name'              => 'property name',
        'property_status'            => 'property status',
        'star_rating'                => 'star rating',
        'country_id'                 => 'country',
        'state_id'                   => 'state',
        'region'                     => 'region',
        'study_start'                => 'study start month',
        'duration_in_years'          => 'study duration',
        'development_start_month'    => 'development start',
        'development_duration'       => 'development duration',
        'operation_start_month'      => 'operation start',
        'financial_year_start_month' => 'financial year start month',
        'main_functional_currency'   => 'main currency',
        'additional_currency'        => 'additional currency',
        'exchange_rate'              => 'exchange rate',
        'corporate_taxes_rate'       => 'corporate taxes rate',
        'investment_return_rate'     => 'required investment return',
        'perpetual_growth_rate'      => 'perpetual growth rate',
        'copy_name'                  => 'name of the copy',
        'channels'                   => 'reservation channels',
        'count_rooms'                => 'room count',
        'value_rooms'                => 'guests per room',
        'type_rooms'                 => 'room type',
        'rows_rooms'                 => 'rooms',
        'count_foods'                => 'facility count',
        'value_foods'                => 'guest capacity',
        'type_foods'                 => 'food and beverage type',
        'rows_foods'                 => 'food and beverage facilities',
        'count_casinos'              => 'facility count',
        'value_casinos'              => 'guest capacity',
        'type_casinos'               => 'gaming type',
        'rows_casinos'               => 'gaming facilities',
        'count_meetings'             => 'facility count',
        'value_meetings'             => 'guest capacity',
        'type_meetings'              => 'meeting type',
        'rows_meetings'              => 'meeting facilities',
        'count_others'               => 'facility count',
        'value_others'               => 'guest capacity',
        'type_others'                => 'other revenue type',
        'rows_others'                => 'other revenue facilities',
    ],
];
