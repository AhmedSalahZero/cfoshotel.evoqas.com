<?php

namespace App\Models;

use App\Models\Concerns\CompanyScope;
use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Loan extends Model implements AllowsLazyLoading
{
    use CompanyScope;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
    ];

    public static function isWithoutCapitalization($loanType): bool
    {
        return in_array($loanType, self::graceTypes());
    }

    public static function isWithCapitalization($loanType): bool
    {
        return Str::contains($loanType, 'with_capitalization');
    }

    public static function getCapitalizationType($loanType)
    {
        if (self::isWithoutCapitalization($loanType)) {
            return 'without_capitalization';
        }
        if (self::isWithCapitalization($loanType)) {
            return 'with_capitalization';
        }

        return null;

    }

    public static function stepUpTypes(): array
    {
        return [
            'step-up', 'grace_step-up_with_capitalization', 'grace_step-up_without_capitalization',
        ];
    }

    public static function stepDownTypes(): array
    {
        return [
            'step-down', 'grace_step-down_with_capitalization', 'grace_step-down_without_capitalization',
        ];
    }

    public static function graceTypes(): array
    {
        return [
            'grace_step-up_without_capitalization', 'grace_step-down_without_capitalization',
            'grace_period_without_capitalization',
        ];
    }

    public static function getStepRate($loanType, $stepUpRate, $stepDownRate): float
    {
        if (! in_array($loanType, array_merge(self::stepDownTypes(), self::stepUpTypes()))) {
            return 0;
        }

        return in_array($loanType, self::stepUpTypes()) ? $stepUpRate : $stepDownRate;
    }

    public static function getAppliedStepIntervalName($loanType, $stepUpInterval, $stepDownInterval): ?string
    {
        return in_array($loanType, self::stepUpTypes()) ? $stepUpInterval : $stepDownInterval;
    }

    public function acquisition()
    {
        return $this->belongsTo(Acquisition::class, 'acquisition_id', 'id');
    }

    public function getStartDate()
    {
        return $this->start_date;
    }

    // public function scopeOnlyForSection($query,string $sectionName)
    // {
    // 	return $query->where('section_name',$sectionName);
    // }
    public function getLoanType(): string
    {
        return $this->loan_type;
    }

    public function getPricing(): float
    {
        $baseRate = $this->getBaseRate();
        $marginRate = $this->getMarginRate();

        return $baseRate + $marginRate;
    }

    public function getGracePeriod(): int
    {
        return $this->grace_period ?: 0;
    }

    public function getLoanAmount(): float
    {
        return $this->loan_amount ?: 0;
    }

    public function getInstallmentInterval(): ?string
    {
        return $this->installment_interval;
    }

    public function getTenor(): int
    {
        return $this->period ?: 0;
    }

    public function getMarginRate()
    {
        return $this->margin_rate ?: 0;
    }

    public function getBaseRate()
    {
        return $this->base_rate ?: 0;
    }

    public function getStepUpRate()
    {
        return $this->step_up_rate ?: 0;
    }

    public function getStepUpIntervalName()
    {
        return $this->step_up_interval;
    }

    public function getStepDownRate()
    {
        return $this->step_down_rate ?: 0;
    }

    public function getStepDownIntervalName()
    {
        return $this->step_down_interval;
    }

    public function hospitalitySector()
    {
        return $this->belongsTo(HospitalitySector::class, 'hospitality_sector_id', 'id');
    }
}
