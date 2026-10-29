<?php

// Messages of the revenue pages of a study and of the lists of types (server side).
// Screen words are in resources/js/lang/en.js. Keep this file key-for-key like lang/ar/revenue.php.
return [
    'messages' => [
        'rooms_saved' => 'The rooms revenue is saved.',
        'foods_saved'      => 'The food and beverage revenue is saved.',
        'casinos_saved'    => 'The gaming revenue is saved.',
        'meetings_saved'   => 'The meeting spaces revenue is saved.',
        'others_saved'     => 'The other revenue is saved.',
        'types_saved' => 'The list of :list is saved.',
    ],

    'errors' => [
        'room_twice'       => 'Each room type can appear once.',
        'facility_twice'   => 'Each facility can appear once.',
        'plan_total'       => 'The shares of the seasonality plan must add up to 100%.',
        'share_total'      => 'In :year the shares of the reservation channels must add up to 100%.',
        'collection_total' => 'The four collection rates must add up to 100%.',
        'type_in_use'      => 'These types are used in studies and cannot be removed: :names.',
        'type_unknown'     => 'This type is not in your company\'s list.',
        'type_twice'       => 'This name is already in the list.',
    ],

    'lists' => [
        'rooms'    => 'rooms types',
        'foods'    => 'F&B facility types',
        'casinos'  => 'gaming facility types',
        'meetings' => 'meeting facility types',
        'others'   => 'other facility types',
    ],

    // Names of the boxes, used inside the messages ("The :attribute field is required").
    'fields' => [
        'currency'           => 'currency',
        'adr'                => 'average daily rate',
        'escalation'         => 'escalation rate',
        'annual'             => 'annual escalation rate',
        'collection_rate'    => 'collection rate',
        'due_days'           => 'due in days',
        'exchange_year'      => 'exchange rate in :year',
        'occupancy_year'     => 'occupancy in :year',
        'share_year'         => 'revenue share in :year',
        'discount_year'      => 'discount in :year',
        'seasonality_month'  => 'maximum occupancy of month :month',
        'type_title'         => 'name',
        'cover_value'        => 'cover value',
        'method'             => 'projection method',
        'capture_year'       => 'guest capture in :year',
        'meals_year'         => 'meals per guest in :year',
        'target_year'        => 'cover count in :year',
        'rooms_percent_year' => 'percentage of rooms revenue in :year',
        'charges_value'      => 'charges value',
        'guests_target_year' => 'guests per day in :year',
        'meeting_occupancy_year' => 'occupancy in :year',
        'fb_percent_year'    => 'percentage of food and beverage revenue in :year',
        'seasonality_share_month' => 'share of month :month',
    ],
];
