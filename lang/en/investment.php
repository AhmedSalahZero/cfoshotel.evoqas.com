<?php

// Messages of the investment pages of a study (server side): property, land & construction, FF&E. Screen words are in resources/js/lang/en.js.
// Keep this file key-for-key like lang/ar/investment.php.
return [
    'messages' => [
        'land_saved' => 'Land & Constructions Costs has been saved successfully',
        'property_saved' => 'Property Acquisition Costs has been saved successfully',
        'ffe_saved' => 'FFE Cost has been saved successfully',
    ],

    'errors' => [
        'down_payments' => 'The two down payments together must be 100% or less.',
        'rates_total' => 'The collection rates must add up to 100%.',
        'balances' => 'The down payment and the first balance together must be 100% or less.',
        'hard_end' => 'Hard Construction End Date Must Be Less Than Or Equal Operation Start Date',
        'soft_end' => 'Soft Construction End Date Must Be Less Than Or Equal Operation Start Date',
        'ffe_end' => 'FFE End Date Must Be Less Than Or Equal Operation Start Date',
        'grace' => 'The grace period must leave at least two months of the loan tenor.',
        'breakdown_total' => 'The land, building and furniture shares must add up to 100%.',
        'name_twice' => 'Each item name can appear once.',
    ],

    'fields' => [
        'purchase_date' => 'Purchase Date',
        'purchase_cost' => 'Purchase Cost',
        'contingency' => 'Contingency Rate (%)',
        'contingency_short' => 'Contingency Rate %',
        'land_method' => 'Land Payment Method',
        'property_method' => 'Property Payment Method',
        'equity' => 'Equity Funding %',
        'first_down' => 'First Down payment %',
        'second_down' => 'Second Down payment %',
        'after_month' => 'After Month',
        'installment_count' => 'Installment Count',
        'installment_interval' => 'Installment Interval',
        'collection_rate' => 'Collection Rate %',
        'due_in_days' => 'Due In Days',
        'construction_cost' => 'Construction Cost',
        'start_date' => 'Start Date',
        'duration' => 'Duration (Months)',
        'execution_method' => 'Execution Method',
        'down_payment' => 'Down payment %',
        'balance_one' => 'Balance Rate One %',
        'due_one' => 'Due One',
        'due_two' => 'Due Two',
        'as_percentage' => 'As % Property Cost',
        'depreciation' => 'Depreciation Duration',
        'replacement_rate' => 'Replacement Cost Rate',
        'replacement_interval' => 'Replacement Interval',
        'currency' => 'Choose Currency',
        'item_cost' => 'Item Cost',
    ],

    'loan' => [
        'loan_type' => 'Fixed Loan Type',
        'base_rate' => 'Base Rate %',
        'margin_rate' => 'Margin Rate %',
        'period' => 'Tenor (Duration In Months)',
        'grace_period' => 'Grace Period',
        'installment_interval' => 'Installment Payment Interval',
        'step_up_rate' => 'Step-up Rate',
        'step_up_interval' => 'Step-up Interval',
        'step_down_rate' => 'Step-down Rate',
        'step_down_interval' => 'Step-down Interval',
    ],
];
