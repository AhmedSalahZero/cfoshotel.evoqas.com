<?php

namespace App\Calculations;

class SteadyDeclineMethod
{
    public function calculateSteadyDeclineAmount(float $amount, int $startDateAsIndex, int $duration)
    {
        $steadyDeclineCount = [];
        for ($i = $duration; $i >= 1; $i--) {
            $steadyDeclineCount[] = $i;
        }
        $steadyDeclineFactor = array_sum($steadyDeclineCount);
        $steadyFactorAmount = $steadyDeclineFactor != 0 ? $amount / $steadyDeclineFactor : $amount;
        $steadyDeclineAmount = [];
        $steadyDeclineDate = $startDateAsIndex; // 01-02-2023
        foreach ($steadyDeclineCount as $steadyDeclineCountElement) {
            $steadyDeclineAmount[$steadyDeclineDate] = $steadyFactorAmount * $steadyDeclineCountElement;
            $steadyDeclineDate = $steadyDeclineDate + 1; // [$steadDeclineRate = 01-02-2023]
        }

        return $steadyDeclineAmount;
    }
}
