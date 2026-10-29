<?php

namespace App\Calculations;

class SeasonalityService
{
    public function salesSeasonality(array $revenueItem, array $duration_months_in_years, array $dateWithMonthNumber)
    {
        $flatSeasonalityRate = 1 / 12;

        // @vars $revenueItem
        // [
        // 	'seasonality' => 'flat',
        // 	'quarters' => [
        //		50 , 10,10,30
        // ] // must be 100,
        // 'distribution_months_values'=>[

        // ]
        // ];

        /*
         @vars $duration_months_in_years is like
        [
          2024 => array:12 [
          "01-01-2024" => 0
          "01-02-2024" => 0
          ],
          2025 => array:12 [
            "01-01-2025" => 1,
            "01-02-2025" => 1
            ]
        ]

        */
        $seasonality_type = $revenueItem['seasonality'];

        // Final Array
        $sales_seasonality_rates = [];
        foreach ($duration_months_in_years as $year => $months) {
            // In case of Flat Seasonality
            if ($seasonality_type == 'flat' || $seasonality_type == 'flat-seasonality') {
                array_walk($months, function (&$value, $date) use ($flatSeasonalityRate) {
                    $value = $flatSeasonalityRate * $value;
                });

                $total_year_percentages = array_sum($months);
                array_walk($months, function (&$value, $date) use ($total_year_percentages, &$sales_seasonality_rates) {
                    $sales_seasonality_rates[$date] = $total_year_percentages == 0 ? 0 : $value / $total_year_percentages;
                });
            }
            // In case of Flate Distribute Quarterly
            elseif ($seasonality_type == 'distribute_quarterly' || $seasonality_type == 'quarterly-seasonality') {
                $first_quarter_key = isset($revenueItem['quarters']['quarter-one']) ? 'quarter-one' : 0;
                $second_quarter_key = isset($revenueItem['quarters']['quarter-two']) ? 'quarter-two' : 1;
                $third_quarter_key = isset($revenueItem['quarters']['quarter-three']) ? 'quarter-three' : 2;
                $fourth_quarter_key = isset($revenueItem['quarters']['quarter-four']) ? 'quarter-four' : 3;
                $first_quarter = $revenueItem['quarters'][$first_quarter_key] ?? 0;
                $second_quarter = $revenueItem['quarters'][$second_quarter_key] ?? 0;
                $third_quarter = $revenueItem['quarters'][$third_quarter_key] ?? 0;
                $fourth_quarter = $revenueItem['quarters'][$fourth_quarter_key] ?? 0;

                $first_quarter = ($first_quarter / 100) / 3;
                $second_quarter = ($second_quarter / 100) / 3;
                $third_quarter = ($third_quarter / 100) / 3;
                $fourth_quarter = ($fourth_quarter / 100) / 3;
                array_walk($months, function (&$value, $date) use ($first_quarter, $second_quarter, $third_quarter, $fourth_quarter) {
                    // First Quarter OF year
                    $month = date('m', strtotime($date));
                    if ($month == 1 || $month == 2 || $month == 3) {
                        $value = $first_quarter * $value;
                    }
                    // Second Quarter OF year
                    if ($month == 4 || $month == 5 || $month == 6) {
                        $value = $second_quarter * $value;
                    }
                    // Third Quarter OF year
                    if ($month == 7 || $month == 8 || $month == 9) {
                        $value = $third_quarter * $value;
                    }
                    // Fourth Quarter OF year
                    if ($month == 10 || $month == 11 || $month == 12) {
                        $value = $fourth_quarter * $value;
                    }
                });
                $total_year_percentages = array_sum($months);

                array_walk($months, function (&$value, $date) use ($total_year_percentages, &$sales_seasonality_rates) {
                    $sales_seasonality_rates[$date] = $total_year_percentages == 0 ? 0 : $value / $total_year_percentages;
                });
            }

            // In case of Flate Distribute Monthly
            if ($seasonality_type == 'distribute_monthly' || $seasonality_type == 'monthly-seasonality') {
                array_walk($months, function (&$value, $date) use ($revenueItem, $dateWithMonthNumber) {
                    $month = date('F', strtotime($date));
                    $month_name = strtolower($month);
                    $month_number = $dateWithMonthNumber[$date];
                    // $month_number = getMonthNumberFromDate($date,-1);
                    $monthValue = $revenueItem['distribution_months_values'][$month_number] ?? 0;
                    $month_rate = $monthValue / 100;
                    $value = $value * $month_rate;
                });
                $total_year_percentages = array_sum($months);
                array_walk($months, function (&$value, $date) use ($total_year_percentages, &$sales_seasonality_rates) {
                    $sales_seasonality_rates[$date] = $total_year_percentages == 0 ? 0 : $value / $total_year_percentages;
                });
            }

        }

        return $sales_seasonality_rates;
    }

    public function years($end_date, $starting_date, $duration, $type = null)
    {
        //  type = years to return years array for the target section destribution

        $start_date = date('01-m-Y', strtotime($starting_date));

        $start_month = date('m', strtotime($start_date));
        // Years Between Start And End Date
        $getRangeYears = range(gmdate('Y', strtotime($start_date)), gmdate('Y', strtotime($end_date)));
        if ($type == 'years_only') {
            return $getRangeYears;
        } elseif ($type == 'years') {

            $duration_monthes_in_years = [];

            // If the month is in the duration of the sales plan ; the month value will be 1 else 0
            foreach ($getRangeYears as $key => $year) {

                for ($i = 1; $i <= 12; $i++) {

                    $current_date = '01-'.$i.'-'.$year;
                    $current_date = date('d-m-Y', strtotime($current_date));
                    // && strtotime($current_date) <= strtotime($end_date)
                    if (strtotime($current_date) >= strtotime($start_date)) {
                        $duration_monthes_in_years[$year][$current_date] = 1;
                    } else {
                        $duration_monthes_in_years[$year][$current_date] = 0;
                    }
                }
            }

            return $duration_monthes_in_years;
        }
    }
}
