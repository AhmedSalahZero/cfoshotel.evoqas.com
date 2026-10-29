<?php

namespace App\Calculations;

use App\Support\LegacyCarbon;

class CalculateDurationService
{
    public function calculateMonthsDurationPerYear(string $itemStartDate, string $studyEndDate, float $studyDuration, ?string $limitationDate = null)
    {
        return $this->getStudyYears($studyEndDate, $itemStartDate, (($studyDuration * 12) - 1), 'years', $limitationDate);
    }

    public function getStudyYears($end_date, $starting_date, $duration, $type = null, $limitationDate = null)
    {
        //  type = years to return years array for the target section destribution

        $start_date = date('01-m-Y', strtotime($starting_date));

        // $start_month = date("m", strtotime($start_date));
        // Years Between Start And End Date
        // $end_date =  str_replace('/','-',$end_date);
        // $start_date =  str_replace('/','-',$start_date);
        $getRangeYears = generateDatesBetweenTwoDates(LegacyCarbon::make($start_date), LegacyCarbon::make($end_date), 'addMonth', 'd-m-Y');
        if (count($getRangeYears)) {
            $getRangeYears = getYearsFromDates($getRangeYears);
        }

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
                    try {
                        LegacyCarbon::make($limitationDate);
                    } catch (\Exception $e) {
                        $limitationDate = explode('/', $limitationDate)[0].'-'.explode('/', $limitationDate)[1].'-'.explode('/', $limitationDate)[2];
                    }
                    $start_date = $limitationDate ? LegacyCarbon::make($limitationDate)->format('d-m-Y') : $start_date;
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
