<?php

namespace App\Calculations;

class StraightMethodService
{
    public function calculateStraightAmount(float $amount, int $startDateAsIndex, int $duration)
    {
        $steadyGrowthCount = [];
        for ($i = 1; $i <= $duration; $i++) {
            $steadyGrowthCount[] = $duration;
        }
        // $steadyGrowthFactor = array_sum($steadyGrowthCount);
        // $steadyFactorAmount = $steadyGrowthFactor != 0 ? $amount / $steadyGrowthFactor : $amount  ;
        $straightAmount = [];
        $straightDate = $startDateAsIndex; // 01-02-2023
        foreach ($steadyGrowthCount as $steadyGrowthCountElement) {
            $straightAmount[$straightDate] = $amount / $duration;
            $straightDate = $straightDate + 1;  // [$steadGrowthRate = 01-02-2023]

        }

        return $straightAmount;
    }
}
