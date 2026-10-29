<?php

// Messages of the expense pages of a study (server side). Screen words are in resources/js/lang/en.js.
// Keep this file key-for-key like lang/ar/expenses.php.
return [
    'messages' => [
        'saved' => 'The expenses are saved.',
        'startup_saved' => 'The start-up cost is saved.',
        'management_saved' => 'The management fees are saved.',
    ],

    'errors' => [
        'percent_total' => 'In :year the percentages of the expenses must add up to 100% or less.',
        'taxes_total'   => 'Salary taxes and social insurance together must be less than 100%.',
        'name_twice'    => 'Each expense name can appear once in a table.',
    ],

    'fields' => [
        'cost'       => 'Cost Amount',
        'date'       => 'Date',
        'year'       => 'Percentage in :year',
        'amount'     => 'Expense amount',
        'escalation' => 'Escalation Rate %',
        'annual'     => 'Annual Escalation Rate',
        'currency'   => 'Choose Currency',
        'beginning'  => 'Beginning Inventory Balance Value',
        'coverage'   => 'Inventory Coverage Days',
        'cash'       => 'Cash Payment %',
        'due_days'   => 'Due Days',
        'salary'     => 'Current Net Salary',
        'taxes'      => 'Salary Taxes %',
        'insurance'  => 'Social Insurance %',
        'terms'      => 'Payment Terms',
        'percentage' => 'Percentage From Fixed Assets',
        'month'      => 'Payment Month',
    ],
];
