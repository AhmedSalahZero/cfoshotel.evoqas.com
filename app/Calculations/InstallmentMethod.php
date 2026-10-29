<?php

namespace App\Calculations;

class InstallmentMethod
{
    public function __calculate(
        int $startDate,
        float $amount,
        float $downPaymentOneRate,
        float $balanceRate,
        float $installmentCount,
        string $installmentInterval,
        float $downPaymentTwoRate = 0,
        int $downPaymentTwoMonth = 0
    ): array {
        $downPaymentOneAmount = $downPaymentOneRate / 100 * $amount;
        $downPaymentOneDateAsIndex = $startDate;
        $downPaymentTwoDateAsIndex = $startDate + $downPaymentTwoMonth;
        $downPaymentTwoAmount = $downPaymentTwoRate / 100 * $amount;
        $balanceAmount = $balanceRate / 100 * $amount;
        $installmentAmount = $balanceAmount / $installmentCount;
        if ($downPaymentTwoMonth == 0) {
            $installmentsDates = [
                $downPaymentOneDateAsIndex => $downPaymentOneAmount + $downPaymentTwoAmount,
            ];
        } else {
            $installmentsDates = [
                $downPaymentOneDateAsIndex => $downPaymentOneAmount,
                $downPaymentTwoDateAsIndex => $downPaymentTwoAmount,
            ];
        }

        $installmentDuration = ($installmentInterval == 'monthly' ? 1 : ($installmentInterval == 'quarterly' ? 3 : ($installmentInterval == 'semi-annually' ? 6 : ($installmentInterval == 'annually' ? 12 : 0))));
        // one installment every interval after the second down payment; the old loop kept writing the first date, so only one installment was ever recorded
        for ($n = 1; $n <= $installmentCount; $n++) {
            $currentInstallmentDateAsIndex = $downPaymentTwoDateAsIndex + $n * $installmentDuration;
            $installmentsDates[$currentInstallmentDateAsIndex] = ($installmentsDates[$currentInstallmentDateAsIndex] ?? 0) + $installmentAmount;
        }
        ksort($installmentsDates);

        return $installmentsDates;
    }
}
