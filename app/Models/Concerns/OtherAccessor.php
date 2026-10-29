<?php

namespace App\Models\Concerns;

use App\Models\OtherName;

trait OtherAccessor
{
    public function getId(): int
    {
        return $this->id;
    }

    public function getOtherCount()
    {
        return $this->other_count;
    }

    public function getOtherTypeId()
    {
        return $this->other_type_id;
    }

    public function getTypeId()
    {
        return $this->getOtherTypeId();
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

    public static function getOtherIdentifierColumnName()
    {
        return 'other_type_id';
    }

    public function getOtherIdentifier()
    {
        return $this->getOtherTypeId();
    }

    public function getName(): string
    {
        $otherIdentifier = $this->getOtherIdentifier();

        return OtherName::find($otherIdentifier)->getName();
        // return getOtherTypes()[$otherIdentifier]['title'] ?? __('General Type') ;
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

    public function getChargesPerGuest()
    {
        return $this->charges_per_guest ?: 0;
    }

    public function getChosenCurrency()
    {
        return $this->chosen_other_currency;
    }

    public function getChargesPerGuestEscalationRate()
    {
        return $this->charges_per_guest_escalation_rate ?: 0;
    }

    public function getChargesPerGuestAtOperationDate()
    {
        return $this->charges_per_guest_at_operation_date ?: 0;
    }

    public function getChargesPerGuestAnnualEscalationRate()
    {
        return $this->charges_per_guest_annual_escalation_rate ?: 0;
    }

    public function getIdentifier()
    {
        return $this->getOtherIdentifier();
    }

    public function getAnnualEscalationPercentage()
    {
        return $this->getChargesPerGuestEscalationRate();
    }

    public function getBaseValueBeforeEscalation()
    {
        return $this->getChargesPerGuestAtOperationDate();
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

    // meetings
    public function setGuestCaptureCoverPercentageAttribute($jsonValue)
    {
        $this->attributes['guest_capture_cover_percentage'] = repeatJson($jsonValue, true);
    }

    public function setPercentageFromRoomsRevenuesAttribute($jsonValue)
    {
        $this->attributes['percentage_from_rooms_revenues'] = repeatJson($jsonValue, true);

    }

    public function getCollectionPolicyValue()
    {
        $collectionPolicyValue = convertJsonToArray($this->collection_policy_value);

        return $collectionPolicyValue;
    }

    public static function getIdentifierColumnName()
    {
        return static::getOtherIdentifierColumnName();
    }
}
