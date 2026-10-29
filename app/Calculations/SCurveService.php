<?php

namespace App\Calculations;

use App\Support\LegacyMath;

class SCurveService
{
    public function __calculate(float $amount, int $duration, int $startDateAsIndex, $quartersFactors = [], ?int $thirdInt = null, $initialFactor = 8): array
    {
        $durationIterations = $this->getDurationIterations($duration, $startDateAsIndex);
        $quarterEnds = $this->getQuarterEnds($duration);
        $quartersFactors = count($quartersFactors) ? $quartersFactors : $this->getQuarterFactors();
        $thirdInt = $thirdInt ? $thirdInt : 4;
        $thirdQuarterDurationFactor = LegacyMath::round(($quarterEnds[3] - $quarterEnds[2]) / $thirdInt, 0);

        return $this->getExecutionFactors($durationIterations, $quarterEnds, $quartersFactors, $thirdQuarterDurationFactor, $amount, $initialFactor);
    }

    protected function getDurationIterations(int $duration, int $startDateAsIndex)
    {
        $durationIteration = [];
        for ($i = 1; $i <= $duration; $i++) {
            $durationIteration[$startDateAsIndex] = $i;
            $startDateAsIndex = $startDateAsIndex + 1;
        }

        return $durationIteration;
    }

    protected function getQuarterEnds(int $duration): array
    {
        return [
            $duration / 4,
            $duration / 2,
            $duration / 4 * 3,
            $duration,
        ];
    }

    protected function getQuarterFactors(): array
    {
        return [
            1,
            1,
            -0.75,
            0.5,
        ];
    }

    protected function getExecutionFactors(array $durationIterations, array $quarterEnds, array $quartersFactors, float $thirdQuarterDurationFactor, float $amount, $initialFactor = 8): array
    {

        $initialExecutionFactor = $initialFactor;
        $executionFactors = [];
        $sumExecutionFactors = 0;
        $firstIteration = false;
        $sCurve48Amounts = [];
        foreach ($durationIterations as $dateAsIndex => $durationIteration) {
            $lastIndex = array_key_last($executionFactors);
            if ($durationIteration <= $quarterEnds[0]) {
                if (! $firstIteration) {
                    $executionFactors[$dateAsIndex] = $initialExecutionFactor;
                } else {
                    // $lastIndex = count($executionFactors) - 1;
                    $executionFactors[$dateAsIndex] = $executionFactors[$lastIndex] + $quartersFactors[0];
                }
                $firstIteration = true;
            } elseif ($durationIteration <= $quarterEnds[1]) {
                // $lastIndex = array_key_last($durationIterations);
                // $lastIndex = count($executionFactors) - 1;
                $executionFactors[$dateAsIndex] = $executionFactors[$lastIndex] + $quartersFactors[1];
            } elseif ($durationIteration <= ($quarterEnds[2] + $thirdQuarterDurationFactor)) {
                // $lastIndex = count($executionFactors) - 1;
                // $lastIndex = array_key_last($durationIterations);
                $executionFactors[$dateAsIndex] = $executionFactors[$lastIndex] + $quartersFactors[2];
            } elseif ($durationIteration <= $quarterEnds[3]) {
                // $lastIndex = count($executionFactors) - 1;
                // $lastIndex = array_key_last($durationIterations);
                $executionFactors[$dateAsIndex] = $executionFactors[$lastIndex] + $quartersFactors[3];
            }
        }
        $sumExecutionFactors = array_sum($executionFactors);
        $executionFactorAmount = $sumExecutionFactors ? $amount / $sumExecutionFactors : $amount;
        foreach ($executionFactors as $dateAsIndex => $executionFactor) {
            $sCurve48Amounts[$dateAsIndex] = $executionFactorAmount * $executionFactor;
        }

        return $sCurve48Amounts;
    }
}
