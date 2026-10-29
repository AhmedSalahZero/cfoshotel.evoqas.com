<?php

namespace App\Calculations;

class SteadyGrowthMethod
{
    public function calculateSteadyGrowthAmount(float $amount, int $startDateAsIndex, int $duration)
    {
        $steadyGrowthCount = [];
        for ($i = 1; $i <= $duration; $i++) {
            $steadyGrowthCount[] = $i;
        }
        $steadyGrowthFactor = array_sum($steadyGrowthCount);
        $steadyFactorAmount = $steadyGrowthFactor != 0 ? $amount / $steadyGrowthFactor : $amount;
        $steadyGrowthAmount = [];
        $steadyGrowthDate = $startDateAsIndex; // 01-02-2023
        foreach ($steadyGrowthCount as $steadyGrowthCountElement) {
            $steadyGrowthAmount[$steadyGrowthDate] = $steadyFactorAmount * $steadyGrowthCountElement;
            $steadyGrowthDate = $steadyGrowthDate + 1; // [$steadGrowthRate = 01-02-2023]
        }

        return $steadyGrowthAmount;
    }
}
