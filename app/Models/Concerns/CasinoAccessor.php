<?php

namespace App\Models\Concerns;

use App\Models\CasinoName;

trait CasinoAccessor
{
    public function getId(): int
    {
        return $this->id;
    }

    public function getCasinoCount()
    {
        return $this->casino_count;
    }

    public function getCasinoCover()
    {
        return $this->casino_cover;
    }

    public function getCasinoTypeId()
    {
        return $this->casino_type_id;
    }

    public function getTypeId()
    {
        return $this->getCasinoTypeId();
    }

    public static function getCasinoIdentifierColumnName()
    {
        return 'casino_type_id';
    }

    public function getCasinoIdentifier()
    {
        return $this->getCasinoTypeId();
    }

    public function getName(): string
    {
        $casinoId = $this->getCasinoIdentifier();

        return CasinoName::find($casinoId)->getName();
        // return getCasinoTypes()[$casinoId]['title'] ?? __('General Gaming');
    }


    public function getCompanyId(): int
    {
        return (int) ($this->hospitalitySector->company_id ?? 0);
    }


    public function getCompanyName(): string
    {
        return $this->hospitalitySector?->company?->getName() ?? '';
    }


    public function getCreatorName(): string
    {
        return $this->hospitalitySector?->creator?->name ?? __('N/A');
    }

    public function getFAndBFacilities()
    {
        return $this['f&b_facilities'];
    }

    public function getTotalGuestCapacityCount()
    {
        return $this->getCasinoCover() * $this->getCasinoCount();
    }

    public function getChosenCurrency()
    {
        return $this->chosen_casino_currency;
    }

    public function getChargesValueEscalationRate()
    {
        return $this->charges_value_escalation_rate ?: 0;
    }

    public function getChargesValueAtOperationDate()
    {
        return $this->charges_value_at_operation_date;
    }

    public function getChargesValueAnnualEscalationRate()
    {
        return $this->charges_value_annual_escalation_rate ?: 0;
    }

    public function getCoverValue()
    {
        return $this->cover_value ?: 0;
    }

    public function getGuestCaptureCoverPercentage(int $year)
    {
        $guestCaptureCoverPercentageAtYear = $this->guest_capture_cover_percentage;
        $guestCaptureCoverPercentageAtYear = arrayToValueIndexes($guestCaptureCoverPercentageAtYear);

        return $guestCaptureCoverPercentageAtYear && isset($guestCaptureCoverPercentageAtYear[$year]) ? $guestCaptureCoverPercentageAtYear[$year] : 0;
    }

    public function getPercentageFromRevenue(int $year)
    {
        $percentageFromRevenue = $this->percentage_from_rooms_revenues;
        $percentageFromRevenue = arrayToValueIndexes($percentageFromRevenue);

        return $percentageFromRevenue && isset($percentageFromRevenue[$year]) ? $percentageFromRevenue[$year] : 0;
    }

    public function getIdentifier()
    {
        return $this->getCasinoIdentifier();
    }

    public function getAnnualEscalationPercentage()
    {
        return $this->getChargesValueAnnualEscalationRate();
    }

    public function getBaseValueBeforeEscalation()
    {
        return $this->getChargesValueAtOperationDate();
    }

    public function getDailyCountTarget($year)
    {
        return $this->getGuestCaptureCoverPercentage($year);
    }

    public function getCollectionPolicyType()
    {
        return $this->collection_policy_type;
    }

    public function collectionPolicyInterval()
    {
        return $this->collection_policy_interval;
    }

    public function isSystemDefaultCollectionPolicy()
    {
        return $this->getCollectionPolicyType() == 'system_default';
    }

    public function isCustomizeCollectionPolicy()
    {
        return $this->getCollectionPolicyType() == 'customize';
    }

    public function getSalesChannelRateAndDueInDays(int $index, $type)
    {
        if (! $this->isCustomizeCollectionPolicy()) {
            return [
                'rate' => 0,
                'due_in_days' => 0,
            ][$type];
        }

        return [
            'rate' => ((array) json_decode($this->collection_policy_value))['rate'][$index] ?? 0,
            'due_in_days' => ((array) json_decode($this->collection_policy_value))['due_in_days'][$index] ?? 0,
        ][$type];
    }

    // mutators
    public function setPercentageFromRoomsRevenuesAttribute($jsonValue)
    {
        $this->attributes['percentage_from_rooms_revenues'] = repeatJson($jsonValue, true);

    }

    public function setCoverPerDayAttribute($jsonValue)
    {
        $this->attributes['cover_per_day'] = repeatJson($jsonValue, true);
    }

    public function setGuestCaptureCoverPercentageAttribute($jsonValue)
    {
        $this->attributes['guest_capture_cover_percentage'] = repeatJson($jsonValue, true);
    }

    public function getCollectionPolicyValue()
    {
        $collectionPolicyValue = convertJsonToArray($this->collection_policy_value);

        return $collectionPolicyValue;
    }

    public static function getIdentifierColumnName()
    {
        return static::getCasinoIdentifierColumnName();
    }
}
