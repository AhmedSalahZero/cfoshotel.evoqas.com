<?php

namespace App\Models\Concerns;

use App\Models\MeetingName;

trait MeetingAccessor
{
    public function getId(): int
    {
        return $this->id;
    }

    public function getMeetingCount()
    {
        return $this->meeting_count;
    }

    public function getMeetingCover()
    {
        return $this->meeting_cover;
    }

    public function getMeetingTypeId()
    {
        return $this->meeting_type_id;
    }

    public function getTypeId()
    {
        return $this->getMeetingTypeId();
    }

    public static function getMeetingIdentifierColumnName()
    {
        return 'meeting_type_id';
    }

    public function getMeetingIdentifier()
    {
        return $this->getMeetingTypeId();
    }

    public function getName(): string
    {
        $meetingId = $this->getMeetingIdentifier();
        $meeting = MeetingName::find($meetingId);

        return $meeting ? $meeting->getName() : 'N/A';
        // return getMeetingTypes()[$meetingId]['title'] ?? __('General Meeting') ;
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

    public function getTotalGuestCapacityCount()
    {
        return $this->getMeetingCover() * $this->getMeetingCount();
    }

    public function getChosenCurrency()
    {
        return $this->chosen_meeting_currency;
    }

    public function getChargesValueEscalationRate()
    {
        return $this->charges_value_escalation_rate ?: 0;
    }

    public function getChargesValueAtOperationDate()
    {
        return $this->charges_value_at_operation_date ?: 0;
    }

    public function getChargesValueAnnualEscalationRate()
    {
        return $this->charges_value_annual_escalation_rate ?: 0;
    }

    public function getChargesValuePerGuest()
    {
        return $this->charges_value_per_guest ?: 0;
    }

    public function getGuestFacilityOccupancyRateAtYear(int $year)
    {
        $guestCaptureCoverPercentageAtYear = $this->guest_capture_cover_percentage;
        $guestCaptureCoverPercentageAtYear = arrayToValueIndexes($guestCaptureCoverPercentageAtYear);

        return $guestCaptureCoverPercentageAtYear && isset($guestCaptureCoverPercentageAtYear[$year]) ? $guestCaptureCoverPercentageAtYear[$year] : 0;
    }

    public function getGuestFacilityOccupancyRate()
    {
        $guestFacilityOccupancyRates = $this->guest_capture_cover_percentage ?: [];

        $guestFacilityOccupancyRates = arrayToValueIndexes($guestFacilityOccupancyRates);

        return $guestFacilityOccupancyRates;
    }

    public function getPercentageFromRevenue(int $year)
    {
        $percentageFromRevenue = $this->percentage_from_f_and_b_revenues;
        $percentageFromRevenue = arrayToValueIndexes($percentageFromRevenue);

        return $percentageFromRevenue && isset($percentageFromRevenue[$year]) ? $percentageFromRevenue[$year] : 0;
    }

    public function getFAndBFacilities()
    {
        return $this['f&b_facilities'];
    }

    public function getCoverValue()
    {
        return $this->cover_value ?: 0;
    }

    public function getGuestPerMeetingSeasonalityForMonthOrQuarter($monthOrQuarter)
    {
        $seasonality = $this->guest_seasonality;

        if (! $seasonality) {
            return 0;
        }
        $seasonality = isset($seasonality[$monthOrQuarter]) ? $seasonality[$monthOrQuarter] : 0;

        return eval('return '.$seasonality.';');
    }

    public function getGuestPerMeetingSeasonality()
    {
        $seasonality = $this->guest_seasonality;

        return $seasonality;
    }

    public function getRentPerMeetingSeasonality()
    {
        $seasonality = $this->rent_seasonality;

        return $seasonality;
    }

    public function getRentPerMeetingSeasonalityForMonthOrQuarter($monthOrQuarter)
    {
        $seasonality = $this->rent_seasonality;
        if (! $seasonality) {
            return 0;
        }

        $seasonality = isset($seasonality[$monthOrQuarter]) ? $seasonality[$monthOrQuarter] : 0;

        return eval('return '.$seasonality.';');
    }

    public function getIdentifier()
    {
        return $this->getMeetingIdentifier();
    }

    public function getBaseValueBeforeEscalation()
    {
        return $this->getChargesValueAtOperationDate();
    }

    public function getAnnualEscalationPercentage()
    {
        return $this->getChargesValueAnnualEscalationRate();
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

    public function setRentSeasonalityAttribute($jsonValue)
    {
        $this->attributes['rent_seasonality'] = repeatJson($jsonValue, true);
    }

    public function setGuestSeasonalityAttribute($jsonValue)
    {
        $this->attributes['guest_seasonality'] = repeatJson($jsonValue, true);
    }

    public function setPercentageFromFAndBRevenuesAttribute($jsonValue)
    {
        $this->attributes['percentage_from_f_and_b_revenues'] = repeatJson($jsonValue, true);
    }

    public function getCollectionPolicyValue()
    {
        $collectionPolicyValue = convertJsonToArray($this->collection_policy_value);

        return $collectionPolicyValue;
    }

    public static function getIdentifierColumnName()
    {
        return static::getMeetingIdentifierColumnName();
    }
}
