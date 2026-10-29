<?php

namespace App\Models\Concerns;

use App\Models\FoodName;

trait FoodAccessor
{
    public function getId(): int
    {
        return $this->id;
    }

    public function getFoodCount()
    {
        return $this->food_count;
    }

    public function getFoodCover()
    {
        return $this->food_cover;
    }

    public function getFoodTypeId()
    {
        return $this->food_type_id;
    }

    public function getTypeId()
    {
        return $this->getFoodTypeId();
    }

    public static function getFoodIdentifierColumnName()
    {
        return 'food_type_id';
    }

    public function getFoodIdentifier()
    {
        return $this->getFoodTypeId();
    }

    public function getName(): string
    {
        $foodsId = $this->getFoodIdentifier();

        return FoodName::find($foodsId)->getName();
        // return getFoodsTypes(getCurrentCompany())[$foodsId]['title'] ?? __('Main Food');
    }

    public function getGuestPerFood()
    {
        return $this->guest_per_food ?: 0;
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

    public function getDailyCoverCountPerFacility()
    {
        return $this->getFoodCount() * $this->getFoodCover();
    }

    public function getTotalGuestCapacityCount()
    {
        return $this->getFoodCount() * $this->getFoodCover();
    }

    public function getCoverValue()
    {
        return $this->cover_value ?: 0;
    }

    public function getChosenCurrency()
    {
        return $this->chosen_food_currency;
    }

    public function getCoverValueEscalationRate()
    {
        return $this->cover_value_escalation_rate ?: 0;
    }

    public function getCoverValueAtOperationDate()
    {
        return $this->cover_value_at_operation_date;
    }

    public function getCoverValueAnnualEscalationRate()
    {
        return $this->cover_value_annual_escalation_rate ?: 0;
    }

    public function getFAndBFacilities()
    {
        return $this['f&b_facilities'];
    }

    //
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

    public function getMealPerGuest(int $year)
    {
        $mealPerGuestAtYear = $this->meal_per_guest;
        $mealPerGuestAtYear = arrayToValueIndexes($mealPerGuestAtYear);

        return $mealPerGuestAtYear && isset($mealPerGuestAtYear[$year]) ? $mealPerGuestAtYear[$year] : 0;
    }

    public function getCoverPerDay(int $year)
    {
        $coverPerDayAtYear = $this->cover_per_day;
        $coverPerDayAtYear = arrayToValueIndexes($coverPerDayAtYear);

        return $coverPerDayAtYear && isset($coverPerDayAtYear[$year]) ? $coverPerDayAtYear[$year] : 0;

    }

    public function getAnnualEscalationPercentage()
    {
        return $this->getCoverValueAnnualEscalationRate();
    }

    public function getBaseValueBeforeEscalation()
    {
        return $this->getCoverValueAtOperationDate();
    }

    public function getIdentifier()
    {
        return $this->getFoodIdentifier();
    }

    public function getDailyCountTarget($year)
    {
        return $this->getCoverPerDay($year);
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

    public function setGuestCaptureCoverPercentageAttribute($jsonValue)
    {
        $this->attributes['guest_capture_cover_percentage'] = repeatJson($jsonValue);
    }

    public function setCoverPerDayAttribute($jsonValue)
    {
        $this->attributes['cover_per_day'] = repeatJson($jsonValue);
    }

    public function setMealPerGuestAttribute($jsonValue)
    {
        $this->attributes['meal_per_guest'] = repeatJson($jsonValue);
    }

    public function setPercentageFromRoomsRevenuesAttribute($jsonValue)
    {
        $this->attributes['percentage_from_rooms_revenues'] = repeatJson($jsonValue);
    }

    public function getCollectionPolicyValue()
    {
        $collectionPolicyValue = convertJsonToArray($this->collection_policy_value);

        return $collectionPolicyValue;
    }

    public static function getIdentifierColumnName()
    {
        return static::getFoodIdentifierColumnName();
    }
}
