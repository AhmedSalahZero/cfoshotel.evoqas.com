<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\RoomName;
use Illuminate\Support\Collection;

trait RoomAccessor
{
    public function getId(): int
    {
        return $this->id;
    }

    public function getRoomCount()
    {
        return $this->room_count;
    }

    public function getRoomTypeId()
    {
        return $this->room_type_id;
    }

    public function getTypeId()
    {
        return $this->getRoomTypeId();
    }

    public function getRoomIdentifier()
    {
        return $this->getRoomTypeId();
    }

    public static function getRoomIdentifierColumnName()
    {
        return 'room_type_id';
    }

    public static function getIdentifierColumnName()
    {
        return static::getRoomIdentifierColumnName();
    }

    public function getGuestPerRoom()
    {
        return $this->guest_per_room;
    }

    public function getName(): string
    {
        $roomId = $this->room_type_id;
        if (is_null($roomId)) {
            $roomId = Company::findOrFail($this->hospitalitySector->company_id)->getTotalRoomId();
        }

        return RoomName::find($roomId)->getName();
    }

    public function getAverageDailyRate(): float
    {
        return $this->average_daily_rate ? getAmount($this->average_daily_rate) : 0;
    }

    public function getChosenCurrency()
    {
        return $this->chosen_room_currency;
    }

    public function getAverageDailyRateEstimationDate()
    {
        return $this->average_daily_rate_estimation_date;
    }

    public function getAverageDailyRateEscalationRate()
    {
        return $this->average_daily_rate_escalation_rate;
    }

    public function getAverageDailyRateAtOperationDate()
    {
        return $this->average_daily_rate_at_operation_date;
    }

    public function getAverageDailyRateAnnualEscalationRate()
    {
        return $this->average_daily_rate_annual_escalation_rate ?: 0;
    }

    public function isOccupancyRatePerRoom()
    {
        return $this->occupancy_rate_type == 'occupancy_rate_per_room';
    }

    public function isGeneralOccupancyRate()
    {
        return $this->occupancy_rate_type == 'general_occupancy_rate';
    }

    public function getOccupancyRateForRoomAtYear(int $year)
    {
        $occupancyRateForRoomAtYear = convertJsonToArray($this->occupancy_rate_per_room);
        $occupancyRateForRoomAtYear = arrayToValueIndexes($occupancyRateForRoomAtYear);

        return $occupancyRateForRoomAtYear && isset($occupancyRateForRoomAtYear[$year]) ? $occupancyRateForRoomAtYear[$year] : 0;
    }

    public function getPerRoomSeasonality()
    {
        if ($this->hospitalitySector->isGeneralSeasonalityType()) {
            return $this->hospitalitySector->getGeneralSeasonality();
        }
        $seasonality = $this->seasonality;
        if (! $seasonality) {
            return 0;
        }
        $seasonality = (array) json_decode($seasonality);

        return $seasonality;
    }

    public function getPerRoomSeasonalityForMonthOrQuarter($monthOrQuarter)
    {
        $seasonality = $this->seasonality;
        if (! $seasonality) {
            return 0;
        }
        $seasonalityArray = (array) json_decode($seasonality);
        $seasonalityValue = isset($seasonalityArray[$monthOrQuarter]) ? $seasonalityArray[$monthOrQuarter] : 0;

        $seasonalityValue = $seasonalityValue ?: (isset($seasonalityArray[getMonthNumberFromMonthFullName($monthOrQuarter)]) ? $seasonalityArray[getMonthNumberFromMonthFullName($monthOrQuarter)] : 0);

        return eval('return '.$seasonalityValue.';');
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

    public function getOccupancyRatePerRoom()
    {
        if ($this->isGeneralOccupancyRate()) {
            return $this->hospitalitySector->getGeneralOccupancyRate();
        }
        $occupancyRatePerRoom = (array) json_decode($this->occupancy_rate_per_room);

        return arrayToValueIndexes($occupancyRatePerRoom);
    }

    public function getAnnualEscalationPercentage()
    {
        return $this->getAverageDailyRateAnnualEscalationRate();
    }

    public function getBaseValueBeforeEscalation()
    {
        return $this->getAverageDailyRateAtOperationDate();
    }

    public function getIdentifier()
    {
        return $this->getRoomIdentifier();
    }

    // mutators

    public function setOccupancyRatePerRoomAttribute($oldJson)
    {
        $this->attributes['occupancy_rate_per_room'] = repeatJson($oldJson);
    }

    public function getCollectionPolicyValue()
    {
        $collectionPolicyValue = convertJsonToArray($this->collection_policy_value);

        return $collectionPolicyValue;
    }

    public static function isTotalRooms(Collection $rooms)
    {
        return count($rooms) == 1 && $rooms->first()->id == 0;
    }
}
