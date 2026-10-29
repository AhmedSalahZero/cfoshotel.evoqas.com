<?php

namespace App\Models\Concerns;

use App\Support\HArr;
use App\Models\Currency;
use App\Models\DepartmentExpense;
use App\Models\ManagementFee;
use App\Models\Room;
use App\Models\SalesChannel;
use App\Calculations\CalculateDurationService;
use App\Calculations\CalculateFixedLoanAtEndService;
use App\Calculations\CollectionPolicyService;
use App\Calculations\Date;
use App\Calculations\InventoryCoverageDays;
use App\Calculations\ReceivableEndBalanceService;
use App\Calculations\SeasonalityService;
use App\Calculations\StartUpCostService;
use App\Calculations\SupplierPayableEndBalance;
use App\Support\LegacyCarbon;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait HospitalitySectorAccessor
{
    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->study_name ?: __('');
    }

    public function getStudyName(): string
    {
        return $this->getName();
    }

    public function getPropertyName(): ?string
    {
        return $this->property_name;
    }

    public function getPropertyStatus()
    {
        return $this->property_status;
    }

    public function getStarRating(): ?string
    {
        return $this->star_rating;
    }

    public function getCountryId(): ?int
    {
        return $this->country_id;
    }

    public function getStateId(): ?int
    {
        return $this->state_id;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getStudyStartDate(): ?string
    {
        return $this->study_start_date;
    }

    public function getStudyStartDateFormattedForView(): string
    {
        $studyStartDate = $this->getStudyStartDate();

        return dateFormating($studyStartDate, 'M\' Y');
    }

    public function getStudyStartDateAsIndex()
    {
        $datesAsStringAndIndex = $this->getDatesAsStringAndIndex();
        // $operationStartDateFormatted = $this->getOperationStartDateFormatted();
        // $dateWithDateIndex = $this->getDateWithDateIndex();
        // $operationStartDateAsIndex = $this->getOperationStartDateAsIndex($dateWithDateIndex,$operationStartDateFormatted);
        $studyStartDateFormatted = $this->getStudyStartDateFormatted();

        // $studyEndDateAsIndex = $this->getStudyEndDateAsIndex($dateWithDateIndex,$studyStartDateFormatted);
        return $studyStartDateFormatted ? $datesAsStringAndIndex[$studyStartDateFormatted] : null;
    }

    public function getStudyStartDateFormatted(): string
    {
        $studyStartDate = $this->getStudyStartDate();

        return LegacyCarbon::make($studyStartDate)->format('d-m-Y');
    }

    public function getDurationInYears(): ?int
    {
        return $this->duration_in_years;
    }

    public function getStudyEndDate(): ?string
    {
        return $this->study_end_date;
    }

    public function getStudyEndDateFormatted()
    {
        return LegacyCarbon::make($this->getStudyEndDate())->format('d-m-Y');
    }

    public function getDateWithDateIndex(): array
    {
        $datesAndIndexesHelpers = $this->getDatesIndexesHelper();

        return $datesAndIndexesHelpers['dateWithDateIndex'];
    }

    public function getStudyEndDateAsIndex(): ?int
    {
        $datesAsStringAndIndex = $this->getDatesAsStringAndIndex();
        // $operationStartDateFormatted = $this->getOperationStartDateFormatted();
        // $dateWithDateIndex = $this->getDateWithDateIndex();
        // $operationStartDateAsIndex = $this->getOperationStartDateAsIndex($dateWithDateIndex,$operationStartDateFormatted);
        $studyEndDateAsString = $this->getStudyEndDateFormatted();

        // $studyEndDateAsIndex = $this->getStudyEndDateAsIndex($dateWithDateIndex,$studyEndDateAsString);
        return $studyEndDateAsString ? $datesAsStringAndIndex[$studyEndDateAsString] : null;
    }

    public function getDevelopmentStartMonth(): ?string
    {
        return $this->development_start_month ?: 0;
    }

    public function getDevelopmentStartDateAsIndex(): ?int
    {
        return $this->development_start_date;
    }

    public function getDevelopmentStartDateAsString(): ?string
    {
        return app('dateIndexWithDate')[$this->getDevelopmentStartDateAsIndex()];
    }

    public function getDevelopmentStartDateFormatted(): ?string
    {
        return LegacyCarbon::make($this->getDevelopmentStartDateAsString())->format('d-m-Y');
    }

    public function getDevelopmentDuration(): ?int
    {
        return $this->development_duration ?: 0;
    }

    public function getOperationStartMonth(): ?int
    {
        return $this->operation_start_month ?: 0;
    }

    public function getOperationStartDate(): ?string
    {
        $startDate = $this->operation_start_date;

        return $startDate;
    }

    public function getOperationStartDateFormatted()
    {
        $operationStartDate = $this->getOperationStartDate();

        return $operationStartDate ? LegacyCarbon::make($operationStartDate)->format('d-m-Y') : null;
    }

    public function getOperationStartDateAsIndex(): ?int
    {
        $datesAsStringAndIndex = $this->getDatesAsStringAndIndex();
        $operationStartDateFormatted = $this->getOperationStartDateFormatted();

        return $operationStartDateFormatted ? $datesAsStringAndIndex[$operationStartDateFormatted] : null;
    }

    public function financialYearStartMonth(): ?string
    {
        return $this->financial_year_start_month;
    }

    public function getMainFunctionalCurrency(): ?int
    {
        $value = $this->main_functional_currency;

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function getMainFunctionalCurrencyFormatted(): ?string
    {
        return $this->currencyNameByPosition($this->getMainFunctionalCurrency());
    }

    /**
     * The study stores a currency as its position in the list of currencies (1 = the first one).
     * The old application looked it up in the list sorted by number; this does the same.
     */
    protected function currencyNameByPosition($position): ?string
    {
        if ($position === null || $position === '') {
            return null;
        }

        return Currency::query()->orderBy('id')->pluck('name')->values()->get(((int) $position) - 1);
    }

    public function getAdditionalCurrency(): ?int
    {
        // the column is text and an empty text means "no additional currency"
        $value = $this->additional_currency;

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function getAdditionalCurrencyFormatted(): ?string
    {
        return $this->currencyNameByPosition($this->getAdditionalCurrency());
    }

    public function getExchangeRate()
    {
        return $this->exchange_rate ?: 1;
    }

    public function getCorporateTaxesRate()
    {
        return $this->corporate_taxes_rate ?: 0;
    }

    public function getInvestmentReturnRate()
    {
        return $this->investment_return_rate ?: 1;
    }

    public function getPerpetualGrowthRate()
    {
        return $this->perpetual_growth_rate ?: 0;
    }

    public function getRoomsCount()
    {
        return $this->rooms_count;
    }

    public function getAverageGuestCount()
    {
        return $this->average_guest_count;
    }

    public function isTotalRooms(): bool
    {
        return (bool) $this->is_total_rooms;
    }

    public function getTotalFAndBFacilityCount()
    {
        return $this['total_f&b_facility_count'];
    }

    public function getTotalFAndBCoverCount()
    {
        return $this['total_f&b_cover_count'];
    }

    public function isTotalFood()
    {
        return (bool) $this->is_total_foods;
    }

    public function hasOtherSection()
    {
        return $this->has_other_section;
    }

    public function hasSalesChannels()
    {
        return $this->has_sales_channels;
    }

    public function isTotalOther()
    {
        return (bool) $this->is_total_other;
    }

    public function hasCasinoSection(): bool
    {
        return $this->has_casino_section;
    }

    public function getTotalCasinoFacilityCount()
    {
        return $this->total_casino_facility_count;
    }

    public function getTotalCasinoCoverCount()
    {
        return $this->total_casino_cover_count;
    }

    public function isTotalCasino(): bool
    {
        return (bool) $this->is_total_casinos;
    }

    public function hasMeetingSection()
    {
        return $this->has_meeting_section;
    }

    public function getTotalMeetingFacilityCount()
    {
        return $this->total_meeting_facility_count;
    }

    public function getTotalMeetingCoverCount()
    {
        return $this->total_meeting_cover_count;
    }

    public function isTotalMeeting()
    {
        return (bool) $this->is_total_meetings;
    }

    public function getDurationType(): string
    {
        return $this->duration_type;
    }

    public function getCompanyId(): int
    {
        return $this->company->id ?? 0;
    }

    public function getCompanyName(): string
    {
        return $this->company->getName();
    }

    public function getCreatorName(): string
    {
        return $this->creator->name ?? __('N/A');
    }

    protected function getMaxDate(array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate, array $dateWithMonthNumber)
    {
        $studyDurationPerMonth = $this->getStudyDurationPerMonth($datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber);

        return $studyDurationPerMonth[array_key_last($studyDurationPerMonth)];
    }

    public function getOperationDurationPerYear(array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate, array $dateWithMonthNumber, $asIndexes = true, $maxYearIsStudyEndDate = true)
    {
        $calculateDurationService = new CalculateDurationService;
        $operationStartDate = $this->getOperationStartDateFormatted();
        if ($maxYearIsStudyEndDate) {
            $maxDate = $this->getStudyEndDate();
        } else {
            $maxDate = $this->getMaxDate($datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber);
        }
        $studyDurationInYears = $this->getDurationInYears();
        $operationDurationPerYear = $calculateDurationService->calculateMonthsDurationPerYear($operationStartDate, $maxDate, $studyDurationInYears);

        $operationDurationPerYear = $this->removeZeroValuesFromTwoDimArr($operationDurationPerYear);
        if ($asIndexes) {
            return $this->convertMonthAndYearsToIndexes($operationDurationPerYear, $datesAsStringAndIndex, $datesIndexWithYearIndex);
        }

        return $operationDurationPerYear;
    }

    public function getOperationDurationPerMonth(array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate, array $dateWithMonthNumber, $maxYearIsStudyEndDate = true)
    {
        $operationDurationPerMonth = [];
        $operationDurationPerYear = $this->getOperationDurationPerYear($datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber, false, $maxYearIsStudyEndDate);
        foreach ($operationDurationPerYear as $key => $values) {
            foreach ($values as $k => $v) {
                if ($v) {
                    $operationDurationPerMonth[$k] = $v;
                }
            }
        }

        return array_keys($operationDurationPerMonth);
    }

    public function getStudyDurationPerYear(array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate, array $dateWithMonthNumber, $asIndexes = true, $maxYearIsStudyEndDate = true, $repeatIndexes = true)
    {

        $calculateDurationService = new CalculateDurationService;
        $studyStartDate = $this->getStudyStartDate();
        $operationStartDate = $this->getOperationStartDate();
        if ($maxYearIsStudyEndDate) {
            $maxDate = $this->getStudyEndDate();
        } else {
            $maxDate = $this->getMaxDate($datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber);
        }

        $studyDurationInYears = $this->getDurationInYears();

        $limitationDate = $operationStartDate;
        $studyDurationPerYear = $calculateDurationService->calculateMonthsDurationPerYear($studyStartDate, $maxDate, $studyDurationInYears, $limitationDate);
        $studyDurationPerYear = $this->removeDatesBeforeDate($studyDurationPerYear, $studyStartDate);
        $dates = [];
        if ($asIndexes) {
            $dates = $this->convertMonthAndYearsToIndexes($studyDurationPerYear, $datesAsStringAndIndex, $datesIndexWithYearIndex);
        } else {
            $dates = $studyDurationPerYear;
        }
        if ($repeatIndexes) {
            return $this->addMoreIndexes($dates, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber, $asIndexes);
        } else {
            return $dates;
        }
        // return $this->removeZeroValuesFromTwoDimArr($dates);
    }

    protected function addMoreIndexes(array $yearAndDatesValues, array $yearIndexWithYear, array $dateIndexWithDate, array $dateWithMonthNumber, bool $asIndexes): array
    {
        $maxYearsCount = MAX_YEARS_COUNT;
        $lastYear = array_key_last($yearAndDatesValues);
        $firstYear = array_key_first($yearAndDatesValues);
        $maxYear = $firstYear + $maxYearsCount;
        $firstYearAfterLast = $lastYear + 1;
        for ($firstYearAfterLast; $firstYearAfterLast < $maxYear; $firstYearAfterLast++) {
            $dates = $this->replaceIndexWithItsStringDate($yearAndDatesValues[$lastYear], $dateIndexWithDate);
            if ($asIndexes) {
                $yearAndDatesValues[$firstYearAfterLast] = $this->replaceYearWithAnotherYear($dates, $yearIndexWithYear[$firstYearAfterLast], $asIndexes, $dateIndexWithDate, $dateWithMonthNumber);
            } else {
                $yearAndDatesValues[$firstYearAfterLast] = $this->replaceYearWithAnotherYear($dates, $firstYearAfterLast, $asIndexes, $dateIndexWithDate, $dateWithMonthNumber);
            }
        }

        return $yearAndDatesValues;
    }

    protected function replaceYearWithAnotherYear(array $dateAndValues, $newYear, bool $asIndexes, array $dateIndexWithDate, array $dateWithMonthNumber)
    {
        $newDatesAndValues = [];
        foreach ($dateAndValues as $date => $value) {
            $dateAsIndex = null;
            if ($asIndexes) {
                $dateAsIndex = $date;
                $date = $dateIndexWithDate[$date];
            }
            $day = getDayFromDate($date);

            $monthNumber = $dateWithMonthNumber[$date] ?? getMonthFromDate($date);
            $fullDate = $day.'-'.$monthNumber.'-'.$newYear;

            if ($asIndexes) {
                $newDatesAndValues[$dateAsIndex] = $value;
            } else {
                $newDatesAndValues[$fullDate] = $value;
            }
        }

        return $newDatesAndValues;
    }

    protected function removeZeroValuesFromTwoDimArr(array $dates)
    {
        $result = [];
        foreach ($dates as $year => $dateAndValues) {
            foreach ($dateAndValues as $date => $value) {
                if ($value) {
                    $result[$year][$date] = $value;
                }
            }
        }

        return $result;
    }

    public function getStudyDurationPerMonth(array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate, array $dateWithMonthNumber, $maxYearIsStudyEndDate = true, $repeatIndexes = true)
    {
        $studyDurationPerMonth = [];
        $studyDurationPerYear = $this->getStudyDurationPerYear($datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber, false, $maxYearIsStudyEndDate, true, $repeatIndexes);

        foreach ($studyDurationPerYear as $year => $values) {
            foreach ($values as $date => $value) {
                $studyDurationPerMonth[$date] = $value;
            }
        }

        return array_keys($studyDurationPerMonth);
    }

    protected function convertMonthAndYearsToIndexes(array $yearsAndItsDates, array $datesAsStringAndIndex, array $datesIndexWithYearIndex)
    {
        $result = [];

        foreach ($yearsAndItsDates as $yearNumber => $datesAndZeros) {
            foreach ($datesAndZeros as $date => $zeroOrOne) {
                $dateIndex = $datesAsStringAndIndex[$date];
                $yearIndex = $datesIndexWithYearIndex[$dateIndex];
                $result[$yearIndex][$dateIndex] = $zeroOrOne;
            }
        }

        return $result;
    }

    public function getSalesChannelsNames()
    {
        return $this->salesChannels->pluck('name')->toArray();
    }

    public function getBusinessSectorsPercentagesFormatted()
    {
        $percentagesFormatted = [];
        $salesChannels = $this->salesChannels;
        if (! count($salesChannels)) {
            return [];
        }
        foreach ($salesChannels as $salesChannel) {
            $salesChannelName = $salesChannel->name;
            $percentages = $salesChannel->getPercentages();
            foreach ($percentages as $year => $value) {
                $percentagesFormatted[$salesChannelName][$year] = $value;
            }
        }

        return $percentagesFormatted;
    }

    public function getBusinessSectorsDiscountsFormatted()
    {
        $discountOrCommissionsFormatted = [];
        $salesChannels = $this->salesChannels;
        if (! count($salesChannels)) {
            return [];
        }
        foreach ($salesChannels as $salesChannel) {
            $salesChannelName = $salesChannel->name;
            $discountOrCommissions = $salesChannel->discount_or_commission;
            if ($discountOrCommissions) {
                $discountOrCommissionsAsArray = (array) json_decode($discountOrCommissions);
                foreach ($discountOrCommissionsAsArray as $year => $value) {
                    $discountOrCommissionsFormatted[$salesChannelName][$year] = $value;
                }
            }
        }

        return $discountOrCommissionsFormatted;
    }

    public function getSelectedSalesRevenuesFormatted()
    {
        return [
            'Accommodation & Rooms Revenue Stream' => 1,
            'Food & Beverage (F&B) Revenue Stream' => 1,
            'Gaming Revenue Stream' => $this->has_casino_section,
            'Gathering & Meeting Space Revenue Stream' => $this->has_meeting_section,
            'Other Facilities Revenue Stream' => $this->has_other_section,
        ];
    }

    public function getSelectedSalesRevenues()
    {
        return (array) json_decode($this->selected_sales_revenues ?? '');
    }

    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    public function getFoods(): Collection
    {
        return $this->foods ?: collect([]);
    }

    public function getCasinos(): Collection
    {
        return $this->casinos ?: collect([]);
    }

    public function getMeetings(): Collection
    {
        return $this->meetings ?: collect([]);
    }

    public function getOthers(): Collection
    {
        return $this->others ?: collect([]);
    }

    public function getGeneralOccupancyRate()
    {
        return convertJsonToArray($this->general_occupancy_rate);
    }

    public function setGeneralOccupancyRateAttribute($oldJson)
    {
        $this->attributes['general_occupancy_rate'] = repeatJson($oldJson);
    }

    public function getGeneralOccupancyRateForYear(int $year)
    {
        $generalOccupancyRate = convertJsonToArray($this->general_occupancy_rate);

        return $generalOccupancyRate && isset($generalOccupancyRate[$year]) ? $generalOccupancyRate[$year] : 0;
    }

    public function getSeasonalityType()
    {
        return $this->seasonality_type;
    }

    public function isGeneralSeasonalityType()
    {
        return $this->getSeasonalityType() == 'general-seasonality';
    }

    public function getGuestMeetingSeasonalityType()
    {
        return $this->guest_meeting_seasonality_type;
    }

    public function getRentMeetingSeasonalityType()
    {
        return $this->rent_meeting_seasonality_type;
    }

    public function getSeasonalityInterval()
    {
        return $this->seasonality_interval;
    }

    public function getGuestMeetingSeasonalityInterval()
    {
        return $this->guest_meeting_seasonality_interval;
    }

    public function getRentMeetingSeasonalityInterval()
    {
        return $this->rent_meeting_seasonality_interval;
    }

    public function getGeneralSeasonality()
    {
        $generalSeasonality = $this->general_seasonality;
        if (! $generalSeasonality) {
            return 0;
        }

        return (array) json_decode($generalSeasonality);
    }

    public function getGeneralSeasonalityAtDateOrQuarter($monthOrQuarter)
    {
        $generalSeasonality = $this->general_seasonality;
        if (! $generalSeasonality) {
            return 0;
        }
        $generalSeasonality = (array) json_decode($generalSeasonality);
        $generalSeasonality = isset($generalSeasonality[$monthOrQuarter]) ? $generalSeasonality[$monthOrQuarter] : 0;

        return eval('return '.$generalSeasonality.';');
    }

    public function getGuestGeneralSeasonalityAtDateOrQuarter($monthOrQuarter)
    {
        $generalSeasonality = $this->guest_meeting_general_seasonality;
        if (! $generalSeasonality) {
            return 0;
        }

        $generalSeasonality = (array) json_decode($generalSeasonality);
        $generalSeasonality = isset($generalSeasonality[$monthOrQuarter]) ? $generalSeasonality[$monthOrQuarter] : 0;

        return eval('return '.$generalSeasonality.';');
    }

    public function getGuestGeneralSeasonality()
    {
        $generalSeasonality = $this->guest_meeting_general_seasonality;
        if (! $generalSeasonality) {
            return 0;
        }

        return (array) json_decode($generalSeasonality);
    }

    public function getRentGeneralSeasonality()
    {
        $generalSeasonality = $this->rent_meeting_general_seasonality;
        if (! $generalSeasonality) {
            return 0;
        }

        return (array) json_decode($generalSeasonality);
    }

    public function getRentGeneralSeasonalityAtDateOrQuarter($monthOrQuarter)
    {
        $generalSeasonality = $this->rent_meeting_general_seasonality;
        if (! $generalSeasonality) {
            return 0;
        }

        $generalSeasonality = (array) json_decode($generalSeasonality);
        $generalSeasonality = isset($generalSeasonality[$monthOrQuarter]) ? $generalSeasonality[$monthOrQuarter] : 0;

        return eval('return '.$generalSeasonality.';');
    }

    public function getAnnualAvailableRoomsNights(Collection $rooms, float $totalRoomsCount)
    {
        $result = [];
        if (count($rooms)) {
            foreach ($rooms as $room) {
                $result[$room->getRoomIdentifier()] = $room->getRoomCount() * 365;
            }
        } else {
            $result['Total Rooms Count'] = $totalRoomsCount * 365;
            $result[0] = $totalRoomsCount * 365;
        }

        return $result;
    }

    public function getTotalRoomsCount()
    {
        return $this->rooms_count ?: 0;
    }

    public function getAvgDailyRate()
    {
        return [];
    }

    public function getRoomCurrency()
    {
        return [];
    }

    public function getCurrenciesForSelect(): array
    {
        $result = [];
        $mainCurrencyId = $this->getMainFunctionalCurrency();
        $additionalCurrencyId = $this->getAdditionalCurrency();
        $currencies = formatOptionsForSelect(Currency::get(), 'getId', 'getName');
        foreach ($currencies as $index => $currencyArray) {
            if ($currencyArray['value'] == $mainCurrencyId) {
                $result[$mainCurrencyId] = $currencyArray['title'];
            }
            if ($currencyArray['value'] == $additionalCurrencyId) {
                $result[$additionalCurrencyId] = $currencyArray['title'];
            }
        }

        return $result;
    }

    public function isAddSalesChannelsShareDiscount()
    {
        return $this->add_sales_channels_share_discount;
    }

    public function getRoomsGeneralSeasonality()
    {
        $generalRoomsSeasonality = $this['general_seasonality'];

        return $generalRoomsSeasonality ? convertJsonToArray($generalRoomsSeasonality) : [];
    }

    public function getGeneralMeetingSeasonalityFormatted(string $type): array
    {
        $generalMeetingSeasonality = $this[$type.'_meeting_general_seasonality'];

        return $generalMeetingSeasonality ? convertJsonToArray($generalMeetingSeasonality) : [];
    }

    public function getDiffBetweenOperationStartDateAndStudyStartDate()
    {
        $studyStartDate = $this->getStudyStartDate();
        $operatingStartDate = $this->getOperationStartDate();
        $diffInDays = LegacyCarbon::diffInDays(LegacyCarbon::make($operatingStartDate), $studyStartDate);

        return $diffInDays / 365;
    }

    public function isRoomGeneralCollection()
    {
        return $this->room_collection_policy_type == 'general_collection_terms';
    }

    public function isRoomCollectionTermPerSalesChannel()
    {
        return $this->room_collection_policy_type == 'terms_per_sales_channel';
    }

    public function isCollectionTermPerSalesChannelFor($type)
    {
        switch ($type) {
            case 'rooms' :
                return $this->isRoomCollectionTermPerSalesChannel();
            case 'foods' :
                return $this->isFoodCollectionTermPerSalesChannel();
            case 'gaming':
                return $this->isCasinoCollectionTermPerSalesChannel();
            case 'meetings':
                return $this->isMeetingCollectionTermPerSalesChannel();
            case 'others':
                return $this->isOtherCollectionTermPerSalesChannel();
            default:
                throw new \InvalidArgumentException("Unknown collection type: {$type}");
        }
    }

    public function isFoodGeneralCollection()
    {
        return $this->food_collection_policy_type == 'general_collection_terms';
    }

    public function isFoodCollectionTermPerSalesChannel()
    {
        return $this->food_collection_policy_type == 'terms_per_sales_channel';
    }

    public function isCasinoGeneralCollection()
    {
        return $this->casino_collection_policy_type == 'general_collection_terms';
    }

    public function isCasinoCollectionTermPerSalesChannel()
    {
        return $this->casino_collection_policy_type == 'terms_per_sales_channel';
    }

    public function isMeetingGeneralCollection()
    {
        return $this->meeting_collection_policy_type == 'general_collection_terms';
    }

    public function isMeetingCollectionTermPerSalesChannel()
    {
        return $this->meeting_collection_policy_type == 'terms_per_sales_channel';
    }

    public function isOtherGeneralCollection()
    {
        return $this->other_collection_policy_type == 'general_collection_terms';
    }

    public function isOtherCollectionTermPerSalesChannel()
    {
        return $this->other_collection_policy_type == 'terms_per_sales_channel';
    }

    public function getMaxAvailableMonthlyNights(array $operationDurationPerYear, array $daysNumbersOfMonths)
    {

        // get Available Monthly Nights And Guest Count Per Room

        $maxAvailableMonthlyNights = [];
        $rooms = $this->getRoomsForLooping($this->rooms, $this);
        foreach ($operationDurationPerYear as $currentYear => $datesAndZerosOrOnes) {
            foreach ($datesAndZerosOrOnes as $date => $zeroOrOneAtDate) {
                $daysNumberAtDate = $daysNumbersOfMonths[$currentYear][$date] ?? 0;
                foreach ($rooms as $room) {
                    $maxAvailableMonthlyNights[$room->getRoomIdentifier()][$date] = $room->getRoomCount() * $daysNumberAtDate * $zeroOrOneAtDate;
                }
            }
        }

        return $maxAvailableMonthlyNights;
    }

    protected function getRoomsForLooping(Collection $rooms, $model): Collection
    {
        return $rooms;
    }

    protected function getSalesChannelsForLooping(Collection $salesChannels, $model)
    {
        return count($salesChannels) ? $salesChannels : collect([]);
    }

    public function getMaxPracticalAvailableNights(array $maxAvailableNightsNights, array $dateIndexWithMonthNumber)
    {
        $maxPracticalAvailableNights = [];
        // $seasonality = convertJsonToArray($this->seasonality);
        $rooms = $this->getRoomsForLooping($this->rooms->load('hospitalitySector'), $this);
        foreach ($maxAvailableNightsNights as $roomIdentifier => $dateAndValues) {
            foreach ($dateAndValues as $date => $maxAvailableRoomNightsValue) {
                $monthNum = $dateIndexWithMonthNumber[$date];
                $room = $rooms->where(Room::getRoomIdentifierColumnName(), $roomIdentifier)->first();
                $seasonality = convertJsonToArray($room->getPerRoomSeasonality());

                $seasonality = isset($seasonality[$monthNum]) ? $seasonality[$monthNum] / 100 : 0;
                $maxPracticalAvailableNights[$room->getRoomIdentifier()][$date] = $maxAvailableRoomNightsValue * $seasonality;
            }
        }

        return $maxPracticalAvailableNights;
    }

    public function getRoomsSoldNights(array $maxPracticalAvailableNights, array $datesIndexWithYearIndex)
    {
        $roomsSoldNights = [];
        $rooms = $this->getRoomsForLooping($this->rooms, $this);
        foreach ($maxPracticalAvailableNights as $roomIdentifier => $dateAndValues) {
            foreach ($dateAndValues as $date => $maxPracticalAvailableNightValue) {
                $room = $rooms->where(Room::getRoomIdentifierColumnName(), $roomIdentifier)->first();
                $occupancyRatePerYear = $room->getOccupancyRatePerRoom();
                $year = $datesIndexWithYearIndex[$date];
                $occupancyRateAtCurrentYear = $occupancyRatePerYear[$year] ?? 0;
                $roomsSoldNights[$roomIdentifier][$date] = $maxPracticalAvailableNightValue * $occupancyRateAtCurrentYear / 100;
            }
        }

        return $roomsSoldNights;
    }

    public function getInflationFixedRate(Collection $rooms, $dates, array $dateIndexWithMonthNumber, array $dateWithMonthNumber, string $directExpenseType = '')
    {
        $result = [];
        $roomOperationDate = $this->getOperationStartDate();

        foreach ($rooms as $room) {
            $power = 0;
            $canIncreasePower = false;
            $baseValue = $room->getBaseValueBeforeEscalation($directExpenseType) ?: 0;
            $roomOperationDate = LegacyCarbon::make($roomOperationDate)->format('d-m-Y');
            $monthOfIncreasing = $dateWithMonthNumber[$roomOperationDate];

            $percentage = $room->getAnnualEscalationPercentage($directExpenseType);

            foreach ($dates as $date) {
                $loopMonth = $dateIndexWithMonthNumber[$date];
                if ($monthOfIncreasing == $loopMonth) {
                    if ($canIncreasePower) {
                        $power = $power + 1;
                    }
                    $canIncreasePower = true;
                }
                $result[$room->getIdentifier()][$date] = applyInflationRate($baseValue, $percentage, $power);
            }
        }

        return $result;
    }

    public function getAdrPerSalesChannels(array $inflationRatesAtDates, array $operationDates, array $datesIndexWithYearIndex): array
    {
        $adrPerSalesChannel = [];
        $rooms = $this->getRoomsForLooping($this->rooms, $this);
        $salesChannels = $this->getSalesChannelsForLooping($this->salesChannels, $this);

        // if (Room::isTotalRooms($rooms) && SalesChannel::isNone($salesChannels)) {
        // 	foreach ($operationDates as $date) {
        // 	}
        // }
        foreach ($rooms as $room) {
            foreach ($salesChannels as $salesChannel) {
                foreach ($operationDates as $date) {
                    $roomIdentifier = $room->getRoomIdentifier();
                    $salesChannelIdentifier = $salesChannel->getSalesChannelIdentifier();
                    $inflationRateAtDate = $inflationRatesAtDates[$roomIdentifier][$date] ?? 0;
                    $year = $datesIndexWithYearIndex[$date];
                    $discountRateAtDate = $salesChannel->getDiscountOrCommissionAtYear($year) / 100;
                    $adrPerSalesChannel[$roomIdentifier][$salesChannelIdentifier][$date] = $inflationRateAtDate * (1 - $discountRateAtDate);
                }
            }
        }

        return $adrPerSalesChannel;
    }

    protected function handleRoomNightSold(&$revenueSharePerSalesChannels, &$roomNightsSoldForDirectChannel, $inflatedPrices, $adrPerSalesChannel, $perRoomSoldNights, $operationDates, $room, array $datesIndexWithYearIndex, $salesChannel = null)
    {
        foreach ($operationDates as $date) {
            $roomIdentifier = $room->getRoomIdentifier();

            $salesChannelIdentifier = $salesChannel ? $salesChannel->getSalesChannelIdentifier() : 0;
            $year = $datesIndexWithYearIndex[$date];
            $roomSoldNightAtDate = $perRoomSoldNights[$roomIdentifier][$date] ?? 0;
            $revenueShareAtDate = $salesChannel ? $salesChannel->getRevenueSharePercentageAtYear($year) / 100 : 1;
            $roomNightsSoldForDirectChannel[$roomIdentifier][$salesChannelIdentifier][$date] = $revenueShareAtDate * $roomSoldNightAtDate;
            $adrPerSalesChannelAtDate = ($salesChannel ? ($adrPerSalesChannel[$roomIdentifier][$salesChannelIdentifier][$date] ?? 0) : ($inflatedPrices[$roomIdentifier][$date] ?? 0));
            $revenueSharePerSalesChannels[$roomIdentifier][$salesChannelIdentifier][$date] = $adrPerSalesChannelAtDate * ($roomNightsSoldForDirectChannel[$roomIdentifier][$salesChannelIdentifier][$date] ?? 0);
            $revenueSharePerSalesChannelAtDate = $revenueSharePerSalesChannels[$roomIdentifier][$salesChannelIdentifier][$date] ?? 0;
            $revenueSharePerSalesChannels[$roomIdentifier]['total'][$date] = isset($revenueSharePerSalesChannels[$roomIdentifier]['total'][$date]) ? $revenueSharePerSalesChannels[$roomIdentifier]['total'][$date] + $revenueSharePerSalesChannelAtDate : $revenueSharePerSalesChannelAtDate;
            $revenueSharePerSalesChannels['total'][$salesChannelIdentifier][$date] = isset($revenueSharePerSalesChannels['total'][$salesChannelIdentifier][$date]) ? $revenueSharePerSalesChannels['total'][$salesChannelIdentifier][$date] + $revenueSharePerSalesChannelAtDate : $revenueSharePerSalesChannelAtDate;
            $revenueSharePerSalesChannels['total']['total'][$date] = isset($revenueSharePerSalesChannels['total']['total'][$date]) ? $revenueSharePerSalesChannels['total']['total'][$date] + $revenueSharePerSalesChannelAtDate : $revenueSharePerSalesChannelAtDate;
        }
    }

    public function getRoomNightSoldPerSalesChannelAndRevenueSharePerSalesChannels(array $adrPerSalesChannel, array $perRoomSoldNights, array $operationDates, $inflatedPrices, array $datesIndexWithYearIndex)
    {
        $revenueSharePerSalesChannels = [];
        $roomNightsSoldForDirectChannel = [];
        $rooms = $this->getRoomsForLooping($this->rooms, $this);
        $salesChannels = $this->getSalesChannelsForLooping($this->salesChannels, $this);

        if (count($salesChannels)) {
            foreach ($rooms as $room) {
                foreach ($salesChannels as $salesChannel) {
                    $this->handleRoomNightSold($revenueSharePerSalesChannels, $roomNightsSoldForDirectChannel, $inflatedPrices, $adrPerSalesChannel, $perRoomSoldNights, $operationDates, $room, $datesIndexWithYearIndex, $salesChannel);
                }
            }
        } else {
            foreach ($rooms as $room) {
                $this->handleRoomNightSold($revenueSharePerSalesChannels, $roomNightsSoldForDirectChannel, $inflatedPrices, $adrPerSalesChannel, $perRoomSoldNights, $operationDates, $room, $datesIndexWithYearIndex, null);
            }
        }

        return [

            'roomNightSoldPerSalesChannel' => $roomNightsSoldForDirectChannel,
            'revenueSharePerSalesChannels' => $revenueSharePerSalesChannels,
        ];
    }

    public function getGuestCountPerRoom(array $perRoomSoldNights, array $datesIndexWithYearIndex)
    {
        $guestCountPerRoom = [];

        foreach ($perRoomSoldNights as $roomIdentifier => $dateAndValues) {
            $room = $this->getRoomsForLooping($this->rooms, $this)->where(Room::getRoomIdentifierColumnName(), $roomIdentifier)->first();
            foreach ($dateAndValues as $date => $value) {
                $guestCountPerRoom[$roomIdentifier][$date] = $room->getGuestPerRoom() * $value;
                $guestCountPerRoom['total'][$date] = isset($guestCountPerRoom['total'][$date]) ? $guestCountPerRoom['total'][$date] + $guestCountPerRoom[$roomIdentifier][$date] : $guestCountPerRoom[$roomIdentifier][$date];
            }
        }
        $guestCountPerRoom['totalOfEachYear'] = sumForEachYear($guestCountPerRoom['total'] ?? [], $datesIndexWithYearIndex);

        return $guestCountPerRoom;
    }

    /** While building (not on the live server): one line in storage/logs/laravel.log, starting with CALC, for each section of the whole calculation. */
    private function calcLap(string $what, float $since): float
    {
        if (! app()->isProduction()) {
            try {
                \Illuminate\Support\Facades\Log::info(sprintf('CALC %s: %.2fs', $what, microtime(true) - $since));
            } catch (\Throwable) {
                // a log line must never stop a calculation
            }
        }

        return microtime(true);
    }

    public function calculateRoomRevenueAndGuestCount(): array
    {
        $__lap = microtime(true);
        $inventoryCoverageDays = new InventoryCoverageDays;
        $datesAndIndexesHelpers = $this->getDatesIndexesHelper();
        $datesIndexWithYearIndex = $datesAndIndexesHelpers['datesIndexWithYearIndex'];
        $yearIndexWithYear = $datesAndIndexesHelpers['yearIndexWithYear'];
        $dateIndexWithDate = $datesAndIndexesHelpers['dateIndexWithDate'];
        $dateIndexWithMonthNumber = $datesAndIndexesHelpers['dateIndexWithMonthNumber'];
        $dateWithMonthNumber = $datesAndIndexesHelpers['dateWithMonthNumber'];
        $dateWithDateIndex = $datesAndIndexesHelpers['dateWithDateIndex'];

        /**
         * @var array $dateWithDateIndex
         * @var array $dateWithMonthNumber
         * @var array $dateIndexWithMonthNumber
         * @var array $datesIndexWithYearIndex
         * @var array $yearIndexWithYear
         * @var array $dateIndexWithDate
         */
        $startUpCostService = new StartUpCostService;
        $collectionPolicyService = new CollectionPolicyService;
        $salesChannels = $this->salesChannels;
        $startUpAndPreOperationExpenses = [];
        $hospitalitySector = $this;
        $datesAsStringAndIndex = $this->getDatesAsStringAndIndex();
        $operationDurationPerYear = $hospitalitySector->getOperationDurationPerYear($datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber);
        $operationDurationPerYearAsArray = $operationDurationPerYear;
        $operationDurationPerYear = $this->getOperationDurationPerYear($datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber);
        $yearsOfOperationDuration = array_keys($operationDurationPerYear);

        $daysNumbersOfMonths = $this->getDaysNumbersOfMonth($datesAsStringAndIndex, $yearsOfOperationDuration, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate);

        $foods = $hospitalitySector->getFoods();
        $casinos = $hospitalitySector->getCasinos();
        $meetings = $hospitalitySector->getMeetings();
        $others = $hospitalitySector->getOthers();
        $maxAvailableNights = $hospitalitySector->getMaxAvailableMonthlyNights($operationDurationPerYear, $daysNumbersOfMonths);

        $directExpensesForStartUpCost = $hospitalitySector->getDirectExpenseForSection('start', start_up_cost, -1);
        $directExpensesForPreOperatingExpense = $hospitalitySector->getDirectExpenseForSection('start', pre_operating_expense, -1);
        $startUpAndPreOperationExpensesTotals = [];
        foreach ($directExpensesForStartUpCost as $directExpensesForStartUpCost) {
            $cashPayment = $directExpensesForStartUpCost->getCashPayment();
            $dueDays = $directExpensesForStartUpCost->getDueDays();
            $deferredPaymentPercentage = $directExpensesForStartUpCost->getDeferredPaymentPercentage();
            $costAmount = $directExpensesForStartUpCost->getStartUpCost();
            $dateAsIndex = $directExpensesForStartUpCost->getDateAsIndex($dateWithDateIndex);
            $studyDurationPerYear = $hospitalitySector->getStudyDurationPerYear($dateWithDateIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber, true, true, false);
            $studyDates = $hospitalitySector->getOnlyDatesOfActiveStudy($studyDurationPerYear, $dateIndexWithDate);
            $startUpAndPreOperationExpenses['start_up_cost'][$directExpensesForStartUpCost->id] = $startUpCostService->calculateStartUpCost($dueDays, $cashPayment, $deferredPaymentPercentage, $costAmount, $dateAsIndex, $studyDates, $dateIndexWithDate, $dateWithDateIndex, $hospitalitySector, $startUpAndPreOperationExpensesTotals);

        }
        foreach ($directExpensesForPreOperatingExpense as $directExpenseForPreOperatingExpense) {
            $studyDurationPerYear = $hospitalitySector->getStudyDurationPerYear($dateWithDateIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber, true, true, false);
            $studyDates = $hospitalitySector->getOnlyDatesOfActiveStudy($studyDurationPerYear, $dateIndexWithDate);
            $startUpAndPreOperationExpenses['pre_operating_expense'][$directExpenseForPreOperatingExpense->id] = $startUpCostService->calculatePreOperatingExpense((array) $directExpenseForPreOperatingExpense->manpower_payload, $studyDates, $dateIndexWithDate, $hospitalitySector, $startUpAndPreOperationExpensesTotals);

        }

        $__lap = $this->calcLap('dates, helpers, start-up and pre-operating costs', $__lap);
        $operationDates = getValueOfFirstKey($maxAvailableNights, true);

        $totalMaxAvailableNights = sumAllOfDates($maxAvailableNights);
        $maxPracticalAvailableNights = $hospitalitySector->getMaxPracticalAvailableNights($maxAvailableNights, $dateIndexWithMonthNumber);
        $totalMaxPracticalAvailableNights = sumAllOfDates($maxPracticalAvailableNights);
        $totalMaxPracticalAvailableNightsPerYear = sumForEachYear($totalMaxPracticalAvailableNights, $datesIndexWithYearIndex);

        $perRoomSoldNights = $hospitalitySector->getRoomsSoldNights($maxPracticalAvailableNights, $datesIndexWithYearIndex);
        $totalRoomsSoldNights = sumAllOfDates($perRoomSoldNights);
        $totalMaxAvailableNightsPerYear = sumForEachYear($totalMaxAvailableNights, $datesIndexWithYearIndex);
        $dates = array_keys($totalRoomsSoldNights);

        $totalRoomsSoldNightsPerYear = sumForEachYear($totalRoomsSoldNights, $datesIndexWithYearIndex);

        $exchangeRates = $this->getExchangeRates();

        $inflatedPrices = $hospitalitySector->getInflationFixedRate($hospitalitySector->getRooms(), $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber);
        $inflatedPrices = $this->calculatePricesAfterExchangeRate($inflatedPrices, $exchangeRates, 'rooms', $datesIndexWithYearIndex);
        $fAndBFacilityInflatedCoverValue = $this->getInflationFixedRate($hospitalitySector->getFoods(), $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber);
        $fAndBFacilityInflatedCoverValue = $this->calculatePricesAfterExchangeRate($fAndBFacilityInflatedCoverValue, $exchangeRates, 'foods', $datesIndexWithYearIndex);

        $casinoFacilityInflatedChargesValue = $this->getInflationFixedRate($hospitalitySector->getCasinos(), $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber);
        $casinoFacilityInflatedChargesValue = $this->calculatePricesAfterExchangeRate($casinoFacilityInflatedChargesValue, $exchangeRates, 'casinos', $datesIndexWithYearIndex);

        $meetingFacilityInflatedChargesValue = $this->getInflationFixedRate($hospitalitySector->getMeetings(), $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber);
        $meetingFacilityInflatedChargesValue = $this->calculatePricesAfterExchangeRate($meetingFacilityInflatedChargesValue, $exchangeRates, 'meetings', $datesIndexWithYearIndex);
        $otherFacilityCoverInflatedValue = $this->calculateFacilityCoverValue($others, $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber);
        $otherFacilityCoverInflatedValue = $this->calculatePricesAfterExchangeRate($otherFacilityCoverInflatedValue, $exchangeRates, 'others', $datesIndexWithYearIndex);

        $adrPerSalesChannel = $hospitalitySector->getAdrPerSalesChannels($inflatedPrices, $operationDates, $datesIndexWithYearIndex);
        $roomNightSoldPerSalesChannelAndRevenueSharePerSalesChannels = $hospitalitySector->getRoomNightSoldPerSalesChannelAndRevenueSharePerSalesChannels($adrPerSalesChannel, $perRoomSoldNights, $operationDates, $inflatedPrices, $datesIndexWithYearIndex);
        // $roomNightSoldPerSalesChannel = $roomNightSoldPerSalesChannelAndRevenueSharePerSalesChannels['roomNightSoldPerSalesChannel'];
        $revenueSharePerSalesChannels = $roomNightSoldPerSalesChannelAndRevenueSharePerSalesChannels['revenueSharePerSalesChannels'];
        $totalRoomsRevenue = $revenueSharePerSalesChannels['total']['total'] ?? [];
        $totalOfEachYear = sumForEachYear($totalRoomsRevenue, $datesIndexWithYearIndex);
        $totalRoomRevenueOfEachYear = $totalOfEachYear;
        $GuestCountPerRoomWithTotals = $hospitalitySector->getGuestCountPerRoom($perRoomSoldNights, $datesIndexWithYearIndex);
        $annualGuestCountPerRoom = $GuestCountPerRoomWithTotals['totalOfEachYear'] ?? [];
        $monthlyGuestCountForRoom = $GuestCountPerRoomWithTotals['total'] ?? [];
        $FBFacilityCoverInflatedValue = $fAndBFacilityInflatedCoverValue;
        $casinoFacilityChargesInflatedValue = $casinoFacilityInflatedChargesValue;

        // 1 $revenueSharePerSalesChannels[1]['total'] and name it revenuePerRoom

        // 2
        $fAndBFacilityRevenue = $this->calculateFAndBFacilityRevenue($foods, $monthlyGuestCountForRoom, $FBFacilityCoverInflatedValue, $totalRoomsRevenue, $fAndBFacilityInflatedCoverValue, $dates, $daysNumbersOfMonths, $operationDurationPerYear, $datesIndexWithYearIndex);
        $totalFAndBFacilityRevenue = $fAndBFacilityRevenue['total'] ?? [];
        // 3

        $casinoFacilityRevenue = $this->calculateFAndBFacilityRevenue($casinos, $monthlyGuestCountForRoom, $casinoFacilityChargesInflatedValue, $totalRoomsRevenue, $casinoFacilityInflatedChargesValue, $dates, $daysNumbersOfMonths, $operationDurationPerYear, $datesIndexWithYearIndex);
        $totalCasinoFacilityRevenue = $casinoFacilityRevenue['total'] ?? [];
        // 4 meeting facility revenue
        $daysCountPerYear = $this->calculateTotalOperatingDaysCountInEachYear($daysNumbersOfMonths, $operationDurationPerYear);
        $meetingFacilityRevenue = $this->calcMeetingFacilityRevenue($meetings, $meetingFacilityInflatedChargesValue, $dates, $operationDurationPerYearAsArray, $daysCountPerYear, $fAndBFacilityRevenue, $datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber);

        $totalMeetingFacilityRevenue = $meetingFacilityRevenue['total'] ?? [];
        $otherFacilityRevenue = $this->calculateOtherRevenuesFacility($others, $monthlyGuestCountForRoom, $otherFacilityCoverInflatedValue, $totalRoomsRevenue, $dates, $operationDurationPerYear, $datesIndexWithYearIndex);
        $totalOtherFacilityRevenue = $otherFacilityRevenue['total'] ?? [];

        $monthlyTotalHotelRevenue = totalOf5Arr($totalRoomsRevenue, $totalFAndBFacilityRevenue, $totalCasinoFacilityRevenue, $totalMeetingFacilityRevenue, $totalOtherFacilityRevenue, $dates);
        $annuallyTotalHotelRevenue = sumForEachYear($monthlyTotalHotelRevenue, $datesIndexWithYearIndex);

        $__lap = $this->calcLap('revenue of all streams', $__lap);
        // Start Rooms Direct Expense Calculation
        // en/31/hospitality-sector/158/rooms-direct-expenses
        // as % of revenues

        $directPercentageExpenseForRoomRevenues = $this->getDirectExpenseForSection('rooms', variable_expenses_as_percentage_from_rooms_revenues, -1);

        $monthlyRoomPercentageExpense = $this->calculateRevenuesPercentageExpenses($directPercentageExpenseForRoomRevenues, $totalRoomsRevenue, $datesIndexWithYearIndex);

        // as cost per night sold
        $directNightExpenseForRoomRevenues = $this->getDirectExpenseForSection('rooms', variable_expenses_as_cost_per_night_sold, -1);
        $nightExpenseInflatedValue = $this->calculateFacilityCoverValue($directNightExpenseForRoomRevenues, $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber, variable_expenses_as_cost_per_night_sold);
        $roomsNightExpense = $this->calculateRoomsNightExpense($totalRoomsSoldNights, $nightExpenseInflatedValue);

        // as cost per Guest
        $directGuestExpenseForRoomRevenues = $this->getDirectExpenseForSection('rooms', variable_expenses_as_cost_per_guest, -1);
        $guestExpenseInflatedValueForRoom = $this->calculateFacilityCoverValue($directGuestExpenseForRoomRevenues, $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber, variable_expenses_as_cost_per_guest);
        $guestNightExpense = $this->calculateRoomsGuestExpense($monthlyGuestCountForRoom, $guestExpenseInflatedValueForRoom);

        $PurchaseCostsForRoom = $this->calculatePurchaseCost($totalRoomsRevenue, $dates, 'rooms', $datesIndexWithYearIndex);
        // disposable Inventory Expenses
        $inventoryPurchasesAndEndBalanceForRoom = $inventoryCoverageDays->inventoryCoverageDaysValues($PurchaseCostsForRoom, $dateIndexWithDate, $dateWithDateIndex, $this, 'id');

        // salah
        $inventoryPurchasesAndEndBalanceForRoomForView = $inventoryCoverageDays->calculateForIntervals($inventoryPurchasesAndEndBalanceForRoom, $dateIndexWithDate, $hospitalitySector, true);
        // $inventoryPurchasesAndEndBalanceForRoomForView = $this->replaceDepartmentExpenseIdWithName($inventoryPurchasesAndEndBalanceForRoomForView);
        // end salah

        $disposablePaymentForRoom = $this->applyCollectionPolicy($inventoryPurchasesAndEndBalanceForRoom, $dateIndexWithDate, $dateWithDateIndex);
        $disposablePayableStatementForRoomForView = $this->getDisposablePayableStatement($inventoryPurchasesAndEndBalanceForRoom, $disposablePaymentForRoom, $dateIndexWithDate, true);

        // // salah function

        // $disposablePayableStatementForRoomForView = $this->replaceDepartmentExpenseIdWithName($disposablePayableStatementForRoom);

        // Rooms Manpower

        $roomManpowerExpense = $this->calculateManpowerExpense($dates, 'rooms', $dateIndexWithMonthNumber, $dateWithMonthNumber);
        // End  Rooms Direct Expense Calculation
        // ///////////////////////////////////////////////////////////////////////////////////////

        $__lap = $this->calcLap('rooms expenses', $__lap);
        // start foods direct Expense

        // en/31/hospitality-sector/158/foods-direct-expenses
        // as % of revenues
        $directPercentageExpenseForFoodRevenues = $this->getDirectExpenseForSection('foods', variable_expenses_as_percentage_from_rooms_revenues, -1);
        $monthlyFoodPercentageExpense = $this->calculateRevenuesPercentageExpenses($directPercentageExpenseForFoodRevenues, $totalFAndBFacilityRevenue, $datesIndexWithYearIndex);

        // expenses per guest
        $directGuestExpenseForFoodsRevenues = $this->getDirectExpenseForSection('foods', variable_expenses_as_cost_per_guest, -1);
        $guestExpenseInflatedValueForFood = $this->calculateFacilityCoverValue($directGuestExpenseForFoodsRevenues, $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber, variable_expenses_as_cost_per_guest);

        $monthlyFoodGuestCount = $this->calculateFoodsOrCasinoOrOtherGuestCount($foods, $monthlyGuestCountForRoom, $dates, $operationDurationPerYear, $daysNumbersOfMonths, $datesIndexWithYearIndex, true);

        $guestFoodExpense = $this->calculateRoomsGuestExpense($monthlyFoodGuestCount['total'] ?? [], $guestExpenseInflatedValueForFood, true);

        // disposable Inventory Expenses
        $PurchaseCostsForFood = $this->calculatePurchaseCost($totalFAndBFacilityRevenue, $dates, 'foods', $datesIndexWithYearIndex);
        $inventoryPurchasesAndEndBalanceForFood = $inventoryCoverageDays->inventoryCoverageDaysValues($PurchaseCostsForFood, $dateIndexWithDate, $dateWithDateIndex, $this, 'id');

        // salah
        $inventoryPurchasesAndEndBalanceForFoodForView = $inventoryCoverageDays->calculateForIntervals($inventoryPurchasesAndEndBalanceForFood, $dateIndexWithDate, $hospitalitySector, true);
        // $inventoryPurchasesAndEndBalanceForFoodForView = $this->replaceDepartmentExpenseIdWithName($inventoryPurchasesAndEndBalanceForFoodForView);
        // end salah

        $disposablePaymentForFood = $this->applyCollectionPolicy($inventoryPurchasesAndEndBalanceForFood, $dateIndexWithDate, $dateWithDateIndex);
        $disposablePayableStatementForFoodForView = $this->getDisposablePayableStatement($inventoryPurchasesAndEndBalanceForFood, $disposablePaymentForFood, $dateIndexWithDate, true);
        // $disposablePayableStatementForFoodForView = $this->replaceDepartmentExpenseIdWithName($disposablePayableStatementForFood);

        // Foods Manpower

        $foodManpowerExpense = $this->calculateManpowerExpense($dates, 'foods', $dateIndexWithMonthNumber, $dateWithMonthNumber);

        // end foods direct expense

        // ////////////////////////////////////////////////////////

        $__lap = $this->calcLap('F&B expenses', $__lap);
        // start gaming direct Expense

        // en/31/hospitality-sector/158/gaming-direct-expenses
        // as % of revenues
        $directPercentageExpenseForGamingRevenues = $this->getDirectExpenseForSection(gaming, variable_expenses_as_percentage_from_rooms_revenues, -1);
        $monthlyCasinoPercentageExpense = $this->calculateRevenuesPercentageExpenses($directPercentageExpenseForGamingRevenues, $totalCasinoFacilityRevenue, $datesIndexWithYearIndex);

        // expenses per guest
        $directGuestExpenseForCasinoRevenues = $this->getDirectExpenseForSection(gaming, variable_expenses_as_cost_per_guest, -1);
        $guestExpenseInflatedValueForCasino = $this->calculateFacilityCoverValue($directGuestExpenseForCasinoRevenues, $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber, variable_expenses_as_cost_per_guest, true);
        $monthlyCasinoGuestCount = $this->calculateFoodsOrCasinoOrOtherGuestCount($casinos, $monthlyGuestCountForRoom, $dates, $operationDurationPerYear, $daysNumbersOfMonths, $datesIndexWithYearIndex);

        // s

        // $foodRevenueMethod = $food->getFAndBFacilities();
        $guestCasinoExpense = $this->calculateRoomsGuestExpense($monthlyCasinoGuestCount['total'] ?? [], $guestExpenseInflatedValueForCasino);

        // disposable Inventory Expenses
        $PurchaseCostsForCasino = $this->calculatePurchaseCost($totalCasinoFacilityRevenue, $dates, gaming, $datesIndexWithYearIndex);
        $inventoryPurchasesAndEndBalanceForCasino = $inventoryCoverageDays->inventoryCoverageDaysValues($PurchaseCostsForCasino, $dateIndexWithDate, $dateWithDateIndex, $this, 'id');

        // salah
        $inventoryPurchasesAndEndBalanceForCasinoForView = $inventoryCoverageDays->calculateForIntervals($inventoryPurchasesAndEndBalanceForCasino, $dateIndexWithDate, $hospitalitySector, true);

        $disposablePaymentForCasino = $this->applyCollectionPolicy($inventoryPurchasesAndEndBalanceForCasino, $dateIndexWithDate, $dateWithDateIndex);
        $disposablePayableStatementForCasinoForView = $this->getDisposablePayableStatement($inventoryPurchasesAndEndBalanceForCasino, $disposablePaymentForCasino, $dateIndexWithDate, true);
        // $disposablePayableStatementForCasinoForView = $this->replaceDepartmentExpenseIdWithName($disposablePayableStatementForCasino);

        // Gaming Manpower

        $gamingManpowerExpense = $this->calculateManpowerExpense($dates, gaming, $dateIndexWithMonthNumber, $dateWithMonthNumber);

        // end gaming direct expense

        $__lap = $this->calcLap('gaming expenses', $__lap);
        // start Other Revenue Direct Expense

        // en/31/hospitality-sector/158/other-revenues-direct-expenses
        // as % of revenues
        $directPercentageExpenseForOtherRevenues = $this->getDirectExpenseForSection('other', variable_expenses_as_percentage_from_rooms_revenues, -1);
        $monthlyOtherRevenuePercentageExpense = $this->calculateRevenuesPercentageExpenses($directPercentageExpenseForOtherRevenues, $totalOtherFacilityRevenue, $datesIndexWithYearIndex);

        // expenses per guest
        $directGuestExpenseForOtherRevenues = $this->getDirectExpenseForSection('other', variable_expenses_as_cost_per_guest, -1);
        $guestExpenseInflatedValueForOtherRevenues = $this->calculateFacilityCoverValue($directGuestExpenseForOtherRevenues, $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber, variable_expenses_as_cost_per_guest);
        $monthlyOtherRevenuesGuestCount = $this->calculateFoodsOrCasinoOrOtherGuestCount($others, $monthlyGuestCountForRoom, $dates, $operationDurationPerYear, $daysNumbersOfMonths, $datesIndexWithYearIndex);
        $guestOtherRevenueExpense = $this->calculateRoomsGuestExpense($monthlyOtherRevenuesGuestCount['total'] ?? [], $guestExpenseInflatedValueForOtherRevenues);

        // Other Revenue Direct Expense Manpower

        $otherRevenueManpowerExpense = $this->calculateManpowerExpense($dates, 'other', $dateIndexWithMonthNumber, $dateWithMonthNumber);

        // end other revenue direct expense

        $__lap = $this->calcLap('other-revenue expenses', $__lap);
        // start Meeting  Direct Expense

        // en/31/hospitality-sector/158/meetings-direct-expenses
        // as % of revenues
        $directPercentageExpenseForMeetingRevenues = $this->getDirectExpenseForSection('meetings', variable_expenses_as_percentage_from_rooms_revenues, -1);
        $monthlyMeetingPercentageExpense = $this->calculateRevenuesPercentageExpenses($directPercentageExpenseForMeetingRevenues, $totalMeetingFacilityRevenue, $datesIndexWithYearIndex);

        // expenses per guest
        $directGuestExpenseForMeetingsRevenues = $this->getDirectExpenseForSection('meetings', variable_expenses_as_cost_per_guest, -1);
        $guestExpenseInflatedValueForMeeting = $this->calculateFacilityCoverValue($directGuestExpenseForMeetingsRevenues, $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber, variable_expenses_as_cost_per_guest);
        $monthlyMeetingGuestCount = $this->calculateMeetingGuestCount($meetings, $dates, $daysCountPerYear, $operationDurationPerYearAsArray, $monthlyFoodGuestCount, $datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber);
        $guestMeetingExpense = $this->calculateRoomsGuestExpense($monthlyMeetingGuestCount['total'] ?? [], $guestExpenseInflatedValueForMeeting);

        $__lap = $this->calcLap('meeting expenses', $__lap);
        // start Property Expenses
        // As % Of Revenues

        $directPercentageExpenseForPropertyExpenses = $this->getDirectExpenseForSection('property', variable_expenses_as_percentage_from_rooms_revenues, -1);
        $monthlyPropertyPercentageExpense = $this->calculateRevenuesPercentageExpenses($directPercentageExpenseForPropertyExpenses, $monthlyTotalHotelRevenue, $datesIndexWithYearIndex);
        // Fixed Repeating Expense

        $propertyFixedExpenses = $this->getDirectExpenseForSection('property', fixed_monthly_expenses, -1);
        $inflatedValueForPropertyFixedExpenses = $this->calculateFacilityCoverValue($propertyFixedExpenses, $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber, fixed_monthly_expenses);
        // $inflatedValueForPropertyFixedExpenses = multiplyWith($inflatedValueForPropertyFixedExpenses, $operationDurationPerYear, $datesIndexWithYearIndex);
        $paymentForPropertyFixedExpense = $this->calculatePaymentForEnergyFixedExpenses($collectionPolicyService, $inflatedValueForPropertyFixedExpenses, $dateIndexWithDate, $dateWithDateIndex);
        $prepaidExpenseStatementForPropertyForView = $this->getDisposablePayableStatement($inflatedValueForPropertyFixedExpenses, $paymentForPropertyFixedExpense, $dateIndexWithDate, true);
        // $prepaidExpenseStatementForPropertyForView = $this->replaceDepartmentExpenseIdWithName($prepaidExpenseStatementForProperty);

        // End Property Expenses

        // start Energy Expenses
        // As % Of Revenues

        $directPercentageExpenseForEnergyExpenses = $this->getDirectExpenseForSection('energy', variable_expenses_as_percentage_from_rooms_revenues, -1);
        $monthlyEnergyPercentageExpense = $this->calculateRevenuesPercentageExpenses($directPercentageExpenseForEnergyExpenses, $monthlyTotalHotelRevenue, $datesIndexWithYearIndex);
        // Fixed Repeating Expense

        $energyFixedExpenses = $this->getDirectExpenseForSection('energy', fixed_monthly_expenses, -1);
        $inflatedValueForEnergyFixedExpenses = $this->calculateFacilityCoverValue($energyFixedExpenses, $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber, fixed_monthly_expenses);
        // $inflatedValueForEnergyFixedExpenses = multiplyWith($inflatedValueForEnergyFixedExpenses, $operationDurationPerYear, $datesIndexWithYearIndex);
        $paymentForEnergyFixedExpense = $this->calculatePaymentForEnergyFixedExpenses($collectionPolicyService, $inflatedValueForEnergyFixedExpenses, $dateIndexWithDate, $dateWithDateIndex);
        $prepaidExpenseStatementForEnergyForView = $this->getDisposablePayableStatement($inflatedValueForEnergyFixedExpenses, $paymentForEnergyFixedExpense, $dateIndexWithDate, true);
        // $prepaidExpenseStatementForEnergyForView = $this->replaceDepartmentExpenseIdWithName($prepaidExpenseStatementForEnergy);
        // End Energy Expenses

        $__lap = $this->calcLap('property and energy expenses', $__lap);
        // start General Expense

        $directPercentageExpenseForGeneralRevenues = $this->getDirectExpenseForSection('general', variable_expenses_as_percentage_from_total_revenues, -1);
        $monthlyGeneralPercentageExpense = $this->calculateRevenuesPercentageExpenses($directPercentageExpenseForGeneralRevenues, $monthlyTotalHotelRevenue, $datesIndexWithYearIndex);
        // Fixed Monthly Expenses For General

        $generalFixedExpenses = $this->getDirectExpenseForSection('general', fixed_monthly_expenses, -1);
        $inflatedValueForGeneralFixedExpenses = $this->calculateFacilityCoverValue($generalFixedExpenses, $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber, fixed_monthly_expenses);
        $paymentForGeneralFixedExpense = $this->calculatePaymentForEnergyFixedExpenses($collectionPolicyService, $inflatedValueForGeneralFixedExpenses, $dateIndexWithDate, $dateWithDateIndex);
        $prepaidExpenseStatementForGeneralForView = $this->getDisposablePayableStatement($inflatedValueForGeneralFixedExpenses, $paymentForGeneralFixedExpense, $dateIndexWithDate, true);
        // $prepaidExpenseStatementForGeneralForView = $this->replaceDepartmentExpenseIdWithName($prepaidExpenseStatementForGeneral);
        // Fixed Monthly Expenses For Marketing

        $directPercentageExpenseForMarketingRevenues = $this->getDirectExpenseForSection('marketing', variable_expenses_as_percentage_from_total_revenues, -1);
        $monthlyMarketingPercentageExpense = $this->calculateRevenuesPercentageExpenses($directPercentageExpenseForMarketingRevenues, $monthlyTotalHotelRevenue, $datesIndexWithYearIndex);

        $marketingFixedExpenses = $this->getDirectExpenseForSection('marketing', fixed_monthly_expenses, -1);
        $inflatedValueForMarketingFixedExpenses = $this->calculateFacilityCoverValue($marketingFixedExpenses, $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber, fixed_monthly_expenses);
        // $inflatedValueForMarketingFixedExpenses = multiplyWith($inflatedValueForMarketingFixedExpenses, $operationDurationPerYear, $datesIndexWithYearIndex);
        $paymentForMarketingFixedExpense = $this->calculatePaymentForEnergyFixedExpenses($collectionPolicyService, $inflatedValueForMarketingFixedExpenses, $dateIndexWithDate, $dateWithDateIndex);
        $prepaidExpenseStatementForMarketingForView = $this->getDisposablePayableStatement($inflatedValueForMarketingFixedExpenses, $paymentForMarketingFixedExpense, $dateIndexWithDate, true);

        // $prepaidExpenseStatementForMarketingForView = $this->replaceDepartmentExpenseIdWithName($prepaidExpenseStatementForMarketing);
        // general Expense Manpower
        $generalManpowerExpense = $this->calculateManpowerExpense($dates, 'general', $dateIndexWithMonthNumber, $dateWithMonthNumber);
        // marketing Expense Manpower
        $marketingManpowerExpense = $this->calculateManpowerExpense($dates, 'marketing', $dateIndexWithMonthNumber, $dateWithMonthNumber);

        // end gaming direct expense

        $__lap = $this->calcLap('general and marketing expenses', $__lap);
        // Collection section
        $collectionPolicyService = new CollectionPolicyService;

        $revenuePerItem = [
            'rooms' => [
                'revenuePerItem' => $revenueSharePerSalesChannels['total'] ?? [],
                'items' => $salesChannels,
            ],
            'foods' => [
                'revenuePerItem' => $fAndBFacilityRevenue,
                'items' => $foods,
            ],
            'gaming' => [
                'revenuePerItem' => $casinoFacilityRevenue,
                'items' => $casinos,
            ],
            'meetings' => [
                'revenuePerItem' => $meetingFacilityRevenue,
                'items' => $meetings,
            ],
            'others' => [
                'revenuePerItem' => $otherFacilityRevenue,
                'items' => $others,
            ],
        ];

        $totalRevenuePerItem = [
            'rooms' => [
                'revenuePerItem' => $totalRoomsRevenue ?? [],

            ],
            'foods' => [
                'revenuePerItem' => $totalFAndBFacilityRevenue,
            ],
            'gaming' => [
                'revenuePerItem' => $totalCasinoFacilityRevenue,
            ],
            'meetings' => [
                'revenuePerItem' => $totalMeetingFacilityRevenue,
            ],
            'others' => [
                'revenuePerItem' => $totalOtherFacilityRevenue,
            ],
        ];

        // Rooms Collection
        $collectionPoliciesAndReceivableEndBalances = $this->calculateCollectionPolicyAndReceivableEndBalanceFor($collectionPolicyService, $revenuePerItem, $totalRevenuePerItem, $dateIndexWithDate, $dateWithDateIndex);
        $inventoryStatements = [
            'rooms' => $inventoryPurchasesAndEndBalanceForRoomForView,
            'foods' => $inventoryPurchasesAndEndBalanceForFoodForView,
            'gaming' => $inventoryPurchasesAndEndBalanceForCasinoForView,
        ];
        $disposablePaymentStatements = [
            'rooms' => $disposablePayableStatementForRoomForView,
            'foods' => $disposablePayableStatementForFoodForView,
            'gaming' => $disposablePayableStatementForCasinoForView,
        ];

        $calculateFixedLoanAtEndService = new CalculateFixedLoanAtEndService($hospitalitySector);
        $calculateFixedLoanAtEnd = $calculateFixedLoanAtEndService->calculateFixedAssetsLoans($hospitalitySector, $datesAsStringAndIndex, $dateIndexWithDate, $dateWithDateIndex);

        $contractPayments = $calculateFixedLoanAtEnd['contractPayments'];

        // property
        $totalPropertyPurchaseCost = $calculateFixedLoanAtEnd['totalPropertyPurchaseCost'];
        $propertyEquityPayment = $calculateFixedLoanAtEnd['propertyEquityPayment'];
        $propertyLoanWithdrawal = $calculateFixedLoanAtEnd['propertyLoanWithdrawal'];
        $propertyPayments = $calculateFixedLoanAtEnd['propertyPayments'];
        $propertyLoanInstallment = $calculateFixedLoanAtEnd['propertyLoanInstallment'];
        $propertyLoanInterestAmounts = $calculateFixedLoanAtEnd['propertyLoanInterestAmounts'];
        $propertyLoanWithdrawalInterest = $calculateFixedLoanAtEnd['propertyLoanWithdrawalInterest'];
        $propertyLoanAmount = $calculateFixedLoanAtEnd['propertyLoanAmount'];
        $propertyLoanPricing = $calculateFixedLoanAtEnd['propertyLoanPricing'];
        $propertyLoanEndBalanceAtStudyEndBalance = $calculateFixedLoanAtEnd['propertyLoanEndBalanceAtStudyEndBalance'];

        $__lap = $this->calcLap('collection policies and receivables (rooms to land)', $__lap);
        // land
        $totalLandPurchaseCost = $calculateFixedLoanAtEnd['totalLandPurchaseCost'];
        $landEquityPayment = $calculateFixedLoanAtEnd['landEquityPayment'];
        $landLoanWithdrawal = $calculateFixedLoanAtEnd['landLoanWithdrawal'];
        $landPayments = $calculateFixedLoanAtEnd['landPayments'];
        $landLoanInterestAmounts = $calculateFixedLoanAtEnd['landLoanInterestAmounts'];
        $landLoanInstallment = $calculateFixedLoanAtEnd['landLoanInstallment'];

        $landLoanAmount = $calculateFixedLoanAtEnd['landLoanAmount'];
        $landLoanPricing = $calculateFixedLoanAtEnd['landLoanPricing'];
        $landLoanEndBalanceAtStudyEndDate = $calculateFixedLoanAtEnd['landLoanEndBalanceAtStudyEndDate'];

        $hardConstructionEquityPayment = $calculateFixedLoanAtEnd['hardConstructionEquityPayment'];
        $hardConstructionLoanWithdrawal = $calculateFixedLoanAtEnd['hardConstructionLoanWithdrawal'];
        $hardConstructionLoanInstallment = $calculateFixedLoanAtEnd['hardConstructionLoanInstallment'];
        $hardConstructionLoanInterestAmounts = $calculateFixedLoanAtEnd['hardConstructionLoanInterestAmounts'];
        $hardLoanAmount = $calculateFixedLoanAtEnd['hardLoanAmount'];
        $hardLoanPricing = $calculateFixedLoanAtEnd['hardLoanPricing'];
        $hardConstructionLoanEndBalanceAtStudyEndDate = $calculateFixedLoanAtEnd['hardConstructionLoanEndBalanceAtStudyEndDate'];

        $hardConstructionExecutionAndPayment = $calculateFixedLoanAtEnd['hardConstructionExecutionAndPayment'];
        $softConstructionExecutionAndPayment = $calculateFixedLoanAtEnd['softConstructionExecutionAndPayment'];
        $ffeExecutionAndPayment = $calculateFixedLoanAtEnd['ffeExecutionAndPayment'];
        $ffeLoanAmount = $calculateFixedLoanAtEnd['ffeLoanAmount'];

        $hardWithdrawalInterestAmount = $calculateFixedLoanAtEnd['hardWithdrawalInterestAmounts'];

        $softConstructionEquityPayment = $calculateFixedLoanAtEnd['softConstructionEquityPayment'];

        $ffeEquityPayment = $calculateFixedLoanAtEnd['ffeEquityPayment'];
        $ffeLoanWithdrawal = $calculateFixedLoanAtEnd['ffeLoanWithdrawal'];
        $ffeLoanInstallment = $calculateFixedLoanAtEnd['ffeLoanInstallment'];
        $ffeLoanInterestAmounts = $calculateFixedLoanAtEnd['ffeLoanInterestAmounts'];
        $ffeLoanWithdrawalInterest = $calculateFixedLoanAtEnd['ffeLoanWithdrawalInterest'];
        $ffeLoanPricing = $calculateFixedLoanAtEnd['ffeLoanPricing'];
        $ffeLoanEndBalanceAtStudyEndDate = $calculateFixedLoanAtEnd['ffeLoanEndBalanceAtStudyEndDate'];

        $propertyLoanEndBalance = $calculateFixedLoanAtEnd['propertyLoanEndBalance'];
        $landLoanEndBalance = $calculateFixedLoanAtEnd['landLoanEndBalance'];
        $hardConstructionLoanEndBalance = $calculateFixedLoanAtEnd['hardConstructionLoanEndBalance'];
        $ffeLoanEndBalance = $calculateFixedLoanAtEnd['ffeLoanEndBalance'];

        $baseManagementFeesAmounts['Base Management Fees'] = $this->calculateBaseManagementFeesAmounts($monthlyTotalHotelRevenue, $datesIndexWithYearIndex);
        $incentiveManagementFeesAmounts['Incentive Management Fees'] = [];

        $hardLoanWithdrawalEndBalance = $calculateFixedLoanAtEnd['hardLoanWithdrawalEndBalance'];
        // $hardLoanWithdrawalCapitalizedBalance = $calculateFixedLoanAtEnd['hardLoanWithdrawalCapitalizedInterest'];
        $ffeLoanWithdrawalEndBalance = $calculateFixedLoanAtEnd['ffeLoanWithdrawalEndBalance'];
        $propertyLoanWithdrawalEndBalance = $calculateFixedLoanAtEnd['propertyLoanWithdrawalEndBalance'];
        $landLoanWithdrawalEndBalance = $calculateFixedLoanAtEnd['landLoanWithdrawalEndBalance'];
        $landLoanWithdrawalEndBalance = $calculateFixedLoanAtEnd['landLoanWithdrawalEndBalance'];
        $hardLoanWithdrawalAmounts = $calculateFixedLoanAtEnd['hardLoanWithdrawalAmounts'];
        $ffeLoanWithdrawalAmounts = $calculateFixedLoanAtEnd['ffeLoanWithdrawalAmounts'];
        $propertyLoanWithdrawalAmounts = $calculateFixedLoanAtEnd['propertyLoanWithdrawalAmounts'];
        $landLoanWithdrawalAmounts = $calculateFixedLoanAtEnd['landLoanWithdrawalAmounts'];

        $propertyLandCapitalizedInterest = $calculateFixedLoanAtEnd['propertyLandCapitalizedInterest'];
        $propertyBuildingCapitalizedInterest = $calculateFixedLoanAtEnd['propertyBuildingCapitalizedInterest'];
        $propertyFFECapitalizedInterest = $calculateFixedLoanAtEnd['propertyFFECapitalizedInterest'];

        $landLoanCapitalizedInterest = $calculateFixedLoanAtEnd['landLoanCapitalizedInterest'];

        $startUpCostAndPerOperatingExpenses = $startUpAndPreOperationExpensesTotals['payments'] ?? [];

        $__lap = $this->calcLap('the rest (loans, taxes, statements) up to the result', $__lap);
        return [
            'startUpAndPreOperationExpensesTotals' => $startUpAndPreOperationExpensesTotals,
            'propertyLandCapitalizedInterest' => $propertyLandCapitalizedInterest,
            'propertyBuildingCapitalizedInterest' => $propertyBuildingCapitalizedInterest,
            'propertyFFECapitalizedInterest' => $propertyFFECapitalizedInterest,
            'landLoanCapitalizedInterest' => $landLoanCapitalizedInterest,
            'hardLoanWithdrawalEndBalance' => $hardLoanWithdrawalEndBalance,
            // 'hardLoanWithdrawalCapitalizedInterest'=>$hardLoanWithdrawalCapitalizedBalance,
            'ffeLoanWithdrawalEndBalance' => $ffeLoanWithdrawalEndBalance,
            'propertyLoanWithdrawalEndBalance' => $propertyLoanWithdrawalEndBalance,
            'landLoanWithdrawalEndBalance' => $landLoanWithdrawalEndBalance,
            'hardLoanWithdrawalAmounts' => $hardLoanWithdrawalAmounts,
            'ffeLoanWithdrawalAmounts' => $ffeLoanWithdrawalAmounts,
            'propertyLoanWithdrawalAmounts' => $propertyLoanWithdrawalAmounts,
            'landLoanWithdrawalAmounts' => $landLoanWithdrawalAmounts,
            'propertyLoanEndBalance' => $propertyLoanEndBalance,
            'hardConstructionLoanEndBalance' => $hardConstructionLoanEndBalance,
            'landLoanEndBalance' => $landLoanEndBalance,
            'ffeLoanEndBalance' => $ffeLoanEndBalance,
            'totalRoomRevenueOfEachYear' => $totalRoomRevenueOfEachYear,
            'totalRoomsSoldNightsPerYear' => $totalRoomsSoldNightsPerYear,
            'totalMaxAvailableNightsPerYear' => $totalMaxAvailableNightsPerYear,
            'totalMaxPracticalAvailableNightsPerYear' => $totalMaxPracticalAvailableNightsPerYear,

            'annualGuestCountPerRoom' => $annualGuestCountPerRoom,
            'totalOfEachYearOfRevenueSharePerSalesChannel' => $totalOfEachYear,
            'totalRoomsSoldNights' => $totalRoomsSoldNights,
            'totalMonthlyGuestCountForRooms' => $monthlyGuestCountForRoom,
            'FBFacilityCoverInflatedValue' => $FBFacilityCoverInflatedValue,
            'totalNightsSoldCountPerYear' => $totalRoomsSoldNightsPerYear,
            'dates' => $this->getOnlyDatesOfActiveOperation($operationDurationPerYear, $dateIndexWithDate),
            'monthlyRevenuePerRoom' => $revenueSharePerSalesChannels,
            'fAndBFacilityRevenue' => $fAndBFacilityRevenue,
            'casinoFacilityRevenue' => $casinoFacilityRevenue,
            'meetingFacilityRevenue' => $meetingFacilityRevenue,
            'otherRevenueFacilityRevenue' => $otherFacilityRevenue,
            'daysCountPerYear' => $daysCountPerYear,
            'annuallyTotalHotelRevenue' => $annuallyTotalHotelRevenue,
            'collectionPoliciesAndReceivableEndBalances' => $collectionPoliciesAndReceivableEndBalances,
            'inventoryStatements' => $inventoryStatements,
            'disposablePaymentStatements' => $disposablePaymentStatements,
            'prepaidExpenseStatementForEnergyForView' => $prepaidExpenseStatementForEnergyForView,
            'prepaidExpenseStatementForPropertyForView' => $prepaidExpenseStatementForPropertyForView,
            'prepaidExpenseStatementForGeneralForView' => $prepaidExpenseStatementForGeneralForView,
            'prepaidExpenseStatementForMarketingForView' => $prepaidExpenseStatementForMarketingForView,
            'hardConstructionExecutionAndPayment' => $hardConstructionExecutionAndPayment,
            'softConstructionExecutionAndPayment' => $softConstructionExecutionAndPayment,
            'ffeExecutionAndPayment' => $ffeExecutionAndPayment,
            'totalPropertyPurchaseCost' => $totalPropertyPurchaseCost,
            'totalLandPurchaseCost' => $totalLandPurchaseCost,
            'hardConstructionLoanInterestAmounts' => $hardConstructionLoanInterestAmounts,
            'hardWithdrawalInterestAmount' => $hardWithdrawalInterestAmount,
            'ffeLoanWithdrawalInterest' => $ffeLoanWithdrawalInterest,
            'propertyLoanWithdrawalInterest' => $propertyLoanWithdrawalInterest,
            'propertyLoanAmount' => $propertyLoanAmount,
            'landLoanAmount' => $landLoanAmount,
            'hardLoanAmount' => $hardLoanAmount,
            'ffeLoanAmount' => $ffeLoanAmount,
            'propertyLoanPricing' => $propertyLoanPricing,
            'landLoanPricing' => $landLoanPricing,
            'hardLoanPricing' => $hardLoanPricing,
            'ffeLoanPricing' => $ffeLoanPricing,
            'hardConstructionLoanEndBalanceAtStudyEndDate' => $hardConstructionLoanEndBalanceAtStudyEndDate,
            'propertyLoanEndBalanceAtStudyEndBalance' => $propertyLoanEndBalanceAtStudyEndBalance,
            'ffeLoanEndBalanceAtStudyEndDate' => $ffeLoanEndBalanceAtStudyEndDate,
            'landLoanEndBalanceAtStudyEndDate' => $landLoanEndBalanceAtStudyEndDate,

            // 'ffeLoanWithdrawal'=>$ffeLoanWithdrawal,
            'directExpenses' => [
                'Rooms Direct Expenses' => [
                    'Variable Exp. As % From Rooms Revenues' => $monthlyRoomPercentageExpense,
                    variable_expenses_as_cost_per_night_sold => $roomsNightExpense,
                    variable_expenses_as_cost_per_guest => $guestNightExpense,
                    'Disposable Expense' => $PurchaseCostsForRoom,
                    'Room Manpower Expense' => $roomManpowerExpense,
                ],
                'Foods Direct Expenses' => [
                    'Variable Exp. As % From F&B Revenues' => $monthlyFoodPercentageExpense,
                    variable_expenses_as_cost_per_guest => $guestFoodExpense,
                    // disposable_expenses_rate => $PurchaseCostsForFood,
                    'Disposable Expense' => $PurchaseCostsForFood,
                    'F&B Manpower Expense' => $foodManpowerExpense,

                ],
                'Gaming Direct Expenses' => [
                    'Variable Exp. As % From Gaming Revenues' => $monthlyCasinoPercentageExpense,
                    variable_expenses_as_cost_per_guest => $guestCasinoExpense,
                    // disposable_expenses_rate => $PurchaseCostsForCasino,
                    'Disposable Expense' => $PurchaseCostsForCasino,
                    'Gaming Manpower Expense' => $gamingManpowerExpense,
                ],
                'Meetings Direct Expenses' => [
                    'Variable Exp. As % From Meeting Spaces Revenues' => $monthlyMeetingPercentageExpense,
                    variable_expenses_as_cost_per_guest => $guestMeetingExpense,
                ],
                'Other Revenue Direct Expenses' => [
                    'Variable Exp. As % From Others Revenues' => $monthlyOtherRevenuePercentageExpense,
                    variable_expenses_as_cost_per_guest => $guestOtherRevenueExpense,
                    'Other Revenue Manpower Expense' => $otherRevenueManpowerExpense,
                ],
            ],
            'Undistributed Operating Expenses' => [
                'General And Administrative Expenses' => [
                    variable_expenses_as_percentage_from_total_revenues => $monthlyGeneralPercentageExpense,
                    fixed_monthly_expenses => $inflatedValueForGeneralFixedExpenses,
                    'General Manpower Expense' => $generalManpowerExpense,
                ],
                'Sales And Marketing Expenses' => [
                    variable_expenses_as_percentage_from_total_revenues => $monthlyMarketingPercentageExpense,
                    fixed_monthly_expenses => $inflatedValueForMarketingFixedExpenses,
                    'Sales & Marketing Manpower Expense' => $marketingManpowerExpense,
                ],
                'Property Expenses' => [
                    variable_expenses_as_percentage_from_total_revenues => $monthlyPropertyPercentageExpense,
                    fixed_monthly_expenses => $inflatedValueForPropertyFixedExpenses,
                ],
                'Energy Expenses' => [
                    variable_expenses_as_percentage_from_total_revenues => $monthlyEnergyPercentageExpense,
                    fixed_monthly_expenses => $inflatedValueForEnergyFixedExpenses,
                ],
            ],
            'CashInReport' => [
                'Equity Injection' => arrayMergeTwoDimArray($propertyEquityPayment, $landEquityPayment, $hardConstructionEquityPayment, $softConstructionEquityPayment, $ffeEquityPayment),
                'Loan Withdrawal' => arrayMergeTwoDimArray($propertyLoanWithdrawal, $landLoanWithdrawal, $hardConstructionLoanWithdrawal, $ffeLoanWithdrawal),
            ],
            'CashOutReport' => [
                'Disposable Payments' => arrayMergeTwoDimArray(
                    $this->withOpeningStockPayment($disposablePaymentForRoom, $inventoryPurchasesAndEndBalanceForRoom),
                    $this->withOpeningStockPayment($disposablePaymentForFood, $inventoryPurchasesAndEndBalanceForFood),
                    $this->withOpeningStockPayment($disposablePaymentForCasino, $inventoryPurchasesAndEndBalanceForCasino)
                ),
                'Variable Expenses As % Of Revenue' => arrayMergeTwoDimArray($monthlyRoomPercentageExpense, $monthlyFoodPercentageExpense, $monthlyCasinoPercentageExpense, $monthlyMeetingPercentageExpense, $monthlyOtherRevenuePercentageExpense, $monthlyGeneralPercentageExpense, $monthlyMarketingPercentageExpense, $monthlyPropertyPercentageExpense, $monthlyEnergyPercentageExpense),
                'Variable Expenses Per Night Sold' => arrayMergeTwoDimArray($roomsNightExpense),
                'Variable Expenses Per Guest Count' => arrayMergeTwoDimArray($guestNightExpense, $guestFoodExpense, $guestCasinoExpense, $guestMeetingExpense, $guestOtherRevenueExpense),
                'Fixed Repeating Expenses' => arrayMergeTwoDimArray($paymentForGeneralFixedExpense, $paymentForMarketingFixedExpense, $paymentForPropertyFixedExpense, $paymentForEnergyFixedExpense),
                'Manpower Salaries Payment' => arrayMergeTwoDimArray($roomManpowerExpense, $foodManpowerExpense, $gamingManpowerExpense, $otherRevenueManpowerExpense, $generalManpowerExpense, $marketingManpowerExpense),
                'Management Fees' => arrayMergeTwoDimArray($baseManagementFeesAmounts, $incentiveManagementFeesAmounts),
                'Taxes' => [],
                'Start-up Cost & PreOperating Expenses' => [
                    'Start-up Cost & PreOperating Expenses' => $startUpCostAndPerOperatingExpenses ?? [],
                ],
                'Acquisition And Development Payment' => arrayMergeTwoDimArray($propertyPayments, $landPayments, $contractPayments),
                'Loan Installments Payment' => arrayMergeTwoDimArray($propertyLoanInstallment, $landLoanInstallment, $hardConstructionLoanInstallment, $ffeLoanInstallment),
            ],

        ];
    }

    public function calculateCollectionPolicyAndReceivableEndBalanceFor(CollectionPolicyService $collectionPolicyService, array $revenuePerItems, array $totalRevenuePerItem, array $dateIndexWithDate, array $dateWithDateIndex)
    {

        $collectionPolicy = [];
        $receivableEndBalance = [];
        $receivableEndBalanceService = new ReceivableEndBalanceService;

        foreach ($revenuePerItems as $reportType => $options) {
            $revenueForItem = $options['revenuePerItem'];

            $items = $options['items'];

            if ($this->isCollectionTermPerSalesChannelFor($reportType)) {
                foreach ($items as $index => $item) {
                    if ($item->isSystemDefaultCollectionPolicy()) {
                        $collectionPolicyInterval = $item->collectionPolicyInterval(); // monthly for example
                        $totalRevenueSharePerSalesChannelsForSalesChannel = $revenueForItem[$item->getIdentifier()];
                        $collectionPolicy[$reportType][$item->getIdentifier()] = $collectionPolicyService->applyCollectionPolicy(true, 'system_default', $collectionPolicyInterval, $totalRevenueSharePerSalesChannelsForSalesChannel, $dateIndexWithDate, $dateWithDateIndex, $this);
                    } elseif ($item->isCustomizeCollectionPolicy()) {
                        $totalRevenueSharePerSalesChannelsForSalesChannel = $revenueForItem[$item->getIdentifier()];
                        $collectionPolicyValue = $item->getCollectionPolicyValue();
                        $collectionPolicy[$reportType][$item->getIdentifier()] = $collectionPolicyService->applyCollectionPolicy(true, 'customize', $collectionPolicyValue, $totalRevenueSharePerSalesChannelsForSalesChannel, $dateIndexWithDate, $dateWithDateIndex, $this);
                    }
                    $receivableEndBalance[$reportType][$item->getName()] = $receivableEndBalanceService->__calculate($revenueForItem[$item->getIdentifier()] ?? [], $collectionPolicy[$reportType][$item->getIdentifier()] ?? [], $dateIndexWithDate, $this);
                }

                $receivableEndBalance[$reportType]['total'] = sumSecondKeyInFourDimArr($receivableEndBalance[$reportType] ?? []);

                $collectionPolicy[$reportType]['total'] = getTotalOfTwoDimArr($collectionPolicy[$reportType] ?? []);
            } else {
                // general collection policy
                $isGeneralSystemDefaultCollectionPolicy = $this->isGeneralSystemDefaultCollectionPolicyForType($reportType);
                $isGeneralCustomizeCollectionPolicy = $this->isGeneralCustomizeCollectionPolicyForType($reportType);
                $totalRevenueForItem = $totalRevenuePerItem[$reportType]['revenuePerItem'] ?? [];
                if ($isGeneralSystemDefaultCollectionPolicy) {
                    $collectionPolicyInterval = $this->generalCollectionPolicyIntervalForType($reportType); // monthly for example
                    $collectionPolicy[$reportType][0] = $collectionPolicyService->applyCollectionPolicy(true, 'system_default', $collectionPolicyInterval, $totalRevenueForItem, $dateIndexWithDate, $dateWithDateIndex, $this);
                } elseif ($isGeneralCustomizeCollectionPolicy) {
                    $collectionPolicyValue = $this->getCollectionPolicyValue($reportType);
                    $collectionPolicy[$reportType][0] = $collectionPolicyService->applyCollectionPolicy(true, 'customize', $collectionPolicyValue, $totalRevenueForItem, $dateIndexWithDate, $dateWithDateIndex, $this);
                }

                $generalPolicyForType = $collectionPolicy[$reportType][0] ?? [];

                $receivableEndBalance[$reportType][0] = $receivableEndBalanceService->__calculate($totalRevenueForItem, $generalPolicyForType, $dateIndexWithDate, $this, 0);
                $receivableEndBalance[$reportType]['total'] = sumSecondKeyInFourDimArr($receivableEndBalance[$reportType]);
                $collectionPolicy[$reportType]['total'] = getTotalOfTwoDimArr($collectionPolicy[$reportType] ?? []);

            }
        }

        return [
            'collection_policy' => $collectionPolicy,
            'receivable_end_balance' => $receivableEndBalance,
        ];
    }

    public function calculateRevenuesPercentageExpenses($directExpenseForRoomRevenues, $totalRoomsRevenue, array $datesIndexWithYearIndex)
    {
        $result = [];
        foreach ($directExpenseForRoomRevenues as $directExpenseForRoomRevenue) {
            foreach ($totalRoomsRevenue as $date => $monthlyTotalRoomRevenue) {
                $year = $datesIndexWithYearIndex[$date];
                $expensePercentageAtYear = $directExpenseForRoomRevenue->getPayloadAtYear($year) / 100;
                $result[$directExpenseForRoomRevenue->id][$date] = $expensePercentageAtYear * $monthlyTotalRoomRevenue;
                $currentResultAtDate = $result[$directExpenseForRoomRevenue->id][$date] ?? 0;
                $result['total'][$date] = isset($result['total'][$date]) ? $result['total'][$date] + $currentResultAtDate : $currentResultAtDate;
            }
        }

        return $result;
    }

    public function calculateRoomsNightExpense(array $totalRoomsSoldNights, array $nightExpenseInflatedValue)
    {
        $result = [];
        foreach ($nightExpenseInflatedValue as $directExpenseIdentifier => $nightExpenseInflatedDatesAndValues) {
            foreach ($nightExpenseInflatedDatesAndValues as $date => $nightExpenseInflatedVal) {
                $result[$directExpenseIdentifier][$date] = ($totalRoomsSoldNights[$date] ?? 0) * $nightExpenseInflatedVal;
                $currentResultAtDate = $result[$directExpenseIdentifier][$date] ?? 0;
                $result['total'][$date] = isset($result['total'][$date]) ? $result['total'][$date] + $currentResultAtDate : $currentResultAtDate;
            }
        }

        return $result;
    }

    public function calculateRoomsGuestExpense(array $totalRoomsGuestCount, array $guestExpenseInflatedValue, $debug = false)
    {
        $result = [];

        foreach ($guestExpenseInflatedValue as $directExpenseIdentifier => $guestExpenseInflatedDatesAndValues) {
            foreach ($guestExpenseInflatedDatesAndValues as $date => $guestExpenseInflatedVal) {
                $result[$directExpenseIdentifier][$date] = ($totalRoomsGuestCount[$date] ?? 0) * $guestExpenseInflatedVal;
                $currentResultOfDate = $result[$directExpenseIdentifier][$date] ?? 0;
                $result['total'][$date] = isset($result['total'][$date]) ? $result['total'][$date] + $currentResultOfDate : $currentResultOfDate;
            }
        }

        return $result;
    }

    protected function calculatePurchaseCost(array $totalRevenue, array $dates, string $expenseType, array $datesIndexWithYearIndex)
    {
        // $expenseType [rooms , foods , gaming , etc]
        $purchaseCosts = [];
        $disposalExpenses = $this->getDirectExpenseForSection($expenseType, disposable_expenses_rate, -1);
        foreach ($disposalExpenses as $disposalExpense) {
            foreach ($dates as $date) {
                $year = $datesIndexWithYearIndex[$date];
                $disposablePurchaseCostAtYear = $disposalExpense->getPayloadAtYear($year);
                $totalRevenueAtDate = $totalRevenue[$date] ?? 0;
                $disposableRate = $disposablePurchaseCostAtYear / 100;
                $purchaseCosts[$disposalExpense->getIdentifier()][$date] = $totalRevenueAtDate * $disposableRate;
            }
        }

        return $purchaseCosts;
    }

    protected function calculateManpowerExpense(array $dates, string $expenseType, array $dateIndexWithMonthNumber, array $dateWithMonthNumber): array
    {
        $manpowerExpenses = [];
        $positionExpenses = $this->getDirectExpenseForSection($expenseType, rooms_manpower_expense, -1);
        $inflatedGrossSalaries = $this->getInflationFixedRate($positionExpenses, $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber, rooms_manpower_expense);
        foreach ($inflatedGrossSalaries as $identifier => $inflatedGrossSalary) {
            // the position and its head count of every month are worked out ONCE (they used to be rebuilt for every month: seconds on a long study)
            $directExpense = $positionExpenses->where(DepartmentExpense::getIdentifierName(), $identifier)->first();
            $headsByDate = $directExpense->getManpowerPayloadForDates($dates);
            foreach ($dates as $date) {
                $manpowerAtDate = $headsByDate[$date];
                $inflatedGrossSalaryAtDate = $inflatedGrossSalary[$date] ?? 0;
                $manpowerExpenses[$identifier][$date] = $inflatedGrossSalaryAtDate * $manpowerAtDate;
                $manpowerExpenses['total'][$date] = isset($manpowerExpenses['total'][$date]) ? $manpowerExpenses['total'][$date] + $manpowerExpenses[$identifier][$date] : $manpowerExpenses[$identifier][$date];
            }
        }

        return $manpowerExpenses;
    }

    protected function calculateMeetingFacilityAdjustedSeasonalityAtDate($meeting, $meetingFacility, $guestMeetingSeasonalityInterval, $rentMeetingSeasonalityInterval, $guestMeetingSeasonalityType, $rentMeetingSeasonalityType, $yearsOfOperationDuration, array $datesAsStringAndIndex, array $yearIndexWithYear, array $dateIndexWithDate, array $dateWithMonthNumber)
    {
        $seasonalityService = new SeasonalityService;

        $meetingSeasonalityInterval = null;
        if ($meetingFacility == guest_count_charges_per_guest_occupancy_rate_method) {
            $meetingSeasonalityInterval = $guestMeetingSeasonalityInterval;
        } elseif ($meetingFacility == facility_count_daily_rent_occupancy_rate_method) {
            $meetingSeasonalityInterval = $rentMeetingSeasonalityInterval;
        }

        $facilitySeasonality = [];
        if ($meetingFacility == guest_count_charges_per_guest_occupancy_rate_method) {
            $facilitySeasonality = $guestMeetingSeasonalityType == 'general-seasonality' ? $this->getGuestGeneralSeasonality() : $meeting->getGuestPerMeetingSeasonality();
        }
        if ($meetingFacility == facility_count_daily_rent_occupancy_rate_method) {
            $facilitySeasonality = $rentMeetingSeasonalityType == 'general-seasonality' ? $this->getRentGeneralSeasonality() : $meeting->getRentPerMeetingSeasonality();
        }
        $revenueItem = [
            'seasonality' => $meetingSeasonalityInterval,
            'quarters' => $facilitySeasonality,
            'distribution_months_values' => $facilitySeasonality,
        ];
        $yearsOfOperationDuration = $this->convertIndexYearsAndMonthsToString($yearsOfOperationDuration, $yearIndexWithYear, $dateIndexWithDate);
        $seasonality = $seasonalityService->salesSeasonality($revenueItem, $yearsOfOperationDuration, $dateWithMonthNumber);

        return $this->convertStringDatesFromArrayKeysToIndexes($seasonality, $datesAsStringAndIndex);
    }

    public function calcMeetingFacilityRevenue(Collection $meetings, array $meetingFacilityInflatedChargesValue, array $dates, array $yearsOfOperationDuration, array $daysCountPerYear, array $fAndBFacilityRevenue, array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate, array $dateWithMonthNumber)
    {
        // 1 - first method [ Guest Count x Charges Per Guest x Occupancy Rate Method ]
        $guestMeetingSeasonalityInterval = $this->getGuestMeetingSeasonalityInterval();
        $guestMeetingSeasonalityType = $this->getGuestMeetingSeasonalityType();
        $rentMeetingSeasonalityInterval = $this->getRentMeetingSeasonalityInterval();
        $rentMeetingSeasonalityType = $this->getRentMeetingSeasonalityType();
        $meetingFacilityRevenue = [];
        $meetingFacilityAdjustedSeasonality = [];
        foreach ($meetings as $meeting) {
            $meetingCount = $meeting->getMeetingCount();
            $guestFacilityOccupancyRates = $meeting->getGuestFacilityOccupancyRate();
            $totalGuestCapacity = $meeting->getTotalGuestCapacityCount();

            $meetingIdentifier = $meeting->getIdentifier();
            $meetingFacility = $meeting->getFAndBFacilities();
            $meetingFacilityAdjustedSeasonality[$meetingIdentifier] = $this->calculateMeetingFacilityAdjustedSeasonalityAtDate($meeting, $meetingFacility, $guestMeetingSeasonalityInterval, $rentMeetingSeasonalityInterval, $guestMeetingSeasonalityType, $rentMeetingSeasonalityType, $yearsOfOperationDuration, $datesAsStringAndIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber);

            foreach ($dates as $date) {
                $year = $datesIndexWithYearIndex[$date];
                $guestFacilityOccupancyRateAtYear = $guestFacilityOccupancyRates[$year] ?? 0;
                $daysCountPerYearAtYear = $daysCountPerYear['totalOfEachYear'][$year] ?? 0;
                $guestFacilityOccupancyRateAtYear = $guestFacilityOccupancyRateAtYear / 100;
                $meetingFacilityAdjustedSeasonalityAtDate = $meetingFacilityAdjustedSeasonality[$meetingIdentifier][$date] ?? 0;
                $meetingFacilityInflatedChargesValueAtDate = $meetingFacilityInflatedChargesValue[$meetingIdentifier][$date] ?? 0;

                if ($meetingFacility == guest_count_charges_per_guest_occupancy_rate_method) {
                    $meetingFacilityRevenue[$meetingIdentifier][$date] = $daysCountPerYearAtYear * $guestFacilityOccupancyRateAtYear * $meetingFacilityAdjustedSeasonalityAtDate * $meetingFacilityInflatedChargesValueAtDate * $totalGuestCapacity;
                } elseif ($meetingFacility == facility_count_daily_rent_occupancy_rate_method) {
                    $meetingFacilityRevenue[$meetingIdentifier][$date] = $daysCountPerYearAtYear * $guestFacilityOccupancyRateAtYear * $meetingFacilityAdjustedSeasonalityAtDate * $meetingFacilityInflatedChargesValueAtDate * $meetingCount;
                } elseif ($meetingFacility == percentage_from_f_b_revenue) {
                    $monthlyTotalOfFAndBFacilityRevenue = $fAndBFacilityRevenue['total'][$date] ?? 0;
                    $percentage = $meeting->getPercentageFromRevenue($year) / 100;
                    $meetingFacilityRevenue[$meetingIdentifier][$date] = $monthlyTotalOfFAndBFacilityRevenue * $percentage;
                }
                $currentTotal = $meetingFacilityRevenue[$meetingIdentifier][$date] ?? 0;

                $meetingFacilityRevenue['total'][$date] = isset($meetingFacilityRevenue['total'][$date]) ? $meetingFacilityRevenue['total'][$date] + $currentTotal : $currentTotal;
            }
            // $meetingFacilityRevenue[$meetingIdentifier]['totalOfEachYear'] = sumForEachYear($meetingFacilityRevenue[$meetingIdentifier] ?? [], $this);
        }

        $meetingFacilityRevenue['totalOfEachYear'] = sumForEachYear($meetingFacilityRevenue['total'] ?? [], $datesIndexWithYearIndex);

        return $meetingFacilityRevenue;
    }

    public function calculateOtherRevenuesFacility($others, array $monthlyGuestCountForRoom, array $FBFacilityCoverInflatedValue, array $totalRoomsRevenue, array $dates, array $operationDurationPerYear, array $datesIndexWithYearIndex)
    {
        $result = [];

        foreach ($others as $other) {
            $otherIdentifier = $other->getIdentifier();
            $otherRevenueMethod = $other->getFAndBFacilities();
            foreach ($dates as $date) {
                $year = $datesIndexWithYearIndex[$date];
                $operationZeroOrOne = $operationDurationPerYear[$year][$date] ?? null;
                $guestCapturePercentage = $other->getGuestCaptureCoverPercentage($year) / 100;
                $monthlyGuestNumberAtDate = $monthlyGuestCountForRoom[$date] ?? 0;
                $fAndBFacilityInflatedCoverValue = $FBFacilityCoverInflatedValue[$otherIdentifier][$date] ?? 0;
                if ($otherRevenueMethod == guest_capture_x_charges_per_guest_method) {
                    $result[$otherIdentifier][$date] = $guestCapturePercentage * $monthlyGuestNumberAtDate * $fAndBFacilityInflatedCoverValue * $operationZeroOrOne;
                } elseif ($otherRevenueMethod == percentage_from_rooms_revenue) {
                    $otherFacilityRoomsRevenuePercentage = $other->getPercentageFromRevenue($year) / 100;
                    $result[$otherIdentifier][$date] = $otherFacilityRoomsRevenuePercentage * ($totalRoomsRevenue[$date] ?? 0) * $operationZeroOrOne;
                }
                $value = $result[$otherIdentifier][$date] ?? 0;
                $result['total'][$date] = isset($result['total'][$date]) ? $result['total'][$date] + $value : $value;
            }
        }
        $result['totalOfEachYear'] = sumForEachYear($result['total'] ?? [], $datesIndexWithYearIndex);

        return $result;
    }

    public function calculateTotalOperatingDaysCountInEachYear(array $daysNumbersOfMonths, array $operationDurationPerYear)
    {
        $result = [];
        foreach ($daysNumbersOfMonths as $year => $daysNumbersOfMonth) {
            foreach ($daysNumbersOfMonth as $date => $numberOfDaysInMonth) {
                $operationDurationAtDate = $operationDurationPerYear[$year][$date] ?? 0;
                $result[$year][$date] = $numberOfDaysInMonth * $operationDurationAtDate;
                $result['totalOfEachYear'][$year] = isset($result['totalOfEachYear'][$year]) ? $result['totalOfEachYear'][$year] + $result[$year][$date] : $result[$year][$date];
            }
        }

        return $result;
    }

    public function calculateFacilityCoverValue($foods, array $dates, array $dateIndexWithMonthNumber, array $dateWithMonthNumber, string $directExpenseType = '')
    {
        $FBFacilityCoverInflatedValue = $this->getInflationFixedRate($foods, $dates, $dateIndexWithMonthNumber, $dateWithMonthNumber, $directExpenseType);

        return $FBFacilityCoverInflatedValue;
    }

    public function calculateFAndBFacilityRevenue($foods, array $monthlyGuestCountForRoom, array $FBFacilityCoverInflatedValue, array $totalRoomsRevenue, array $fAndBFacilityInflatedCoverValueArray, array $dates, array $daysNumbersOfMonths, array $operationDurationPerYear, array $datesIndexWithYearIndex)
    {
        $result = [];

        foreach ($foods as $food) {
            $foodIdentifier = $food->getIdentifier();
            $foodRevenueMethod = $food->getFAndBFacilities();
            foreach ($dates as $date) {
                $year = $datesIndexWithYearIndex[$date];
                $operationZeroOrOne = $operationDurationPerYear[$year][$date] ?? null;
                $guestCapturePercentage = $food->getGuestCaptureCoverPercentage($year) / 100;
                $monthlyGuestNumberAtDate = $monthlyGuestCountForRoom[$date] ?? 0;
                $fAndBFacilityInflatedCoverValue = $FBFacilityCoverInflatedValue[$foodIdentifier][$date] ?? 0;
                if ($foodRevenueMethod == guest_capture_x_cover_value_per_guest_method || $foodRevenueMethod == guest_capture_casino_charges_per_guest_method) {
                    $result[$foodIdentifier][$date] = $guestCapturePercentage * $monthlyGuestNumberAtDate * $fAndBFacilityInflatedCoverValue * $operationZeroOrOne;
                } elseif ($foodRevenueMethod == guest_capture_x_meals_per_guest_x_cover_value_per_meal_method) {
                    $foodFacilityMealAtYear = $food->getMealPerGuest($year);
                    $result[$foodIdentifier][$date] = $guestCapturePercentage * $monthlyGuestNumberAtDate * $fAndBFacilityInflatedCoverValue * $foodFacilityMealAtYear * $operationZeroOrOne;
                } elseif ($foodRevenueMethod == cover_count_target_per_day_x_cover_value_method || $foodRevenueMethod == guest_count_target_per_day_charges_per_guest_method) {
                    $coverCountTargetPerDay = $food->getDailyCountTarget($year);
                    $fAndBFacilityInflatedCoverValueAtDate = $fAndBFacilityInflatedCoverValueArray[$foodIdentifier][$date] ?? 0;
                    $dayNumberOfCurrentMonth = $daysNumbersOfMonths[$year][$date] ?? 0;
                    $result[$foodIdentifier][$date] = $dayNumberOfCurrentMonth * $coverCountTargetPerDay * $fAndBFacilityInflatedCoverValueAtDate * $operationZeroOrOne;
                } elseif ($foodRevenueMethod == percentage_from_rooms_revenue) {
                    $foodFacilityRoomsRevenuePercentage = $food->getPercentageFromRevenue($year) / 100;
                    $totalRoomsRevenueAtDate = $totalRoomsRevenue[$date] ?? 0;
                    $result[$foodIdentifier][$date] = $foodFacilityRoomsRevenuePercentage * $totalRoomsRevenueAtDate * $operationZeroOrOne;
                }
                $value = $result[$foodIdentifier][$date] ?? 0;
                $result['total'][$date] = isset($result['total'][$date]) ? $result['total'][$date] + $value : $value;
            }
        }
        $result['totalOfEachYear'] = sumForEachYear($result['total'] ?? [], $datesIndexWithYearIndex);

        return $result;
    }

    public function hasSelectAtLeastOneFAndBProjectMethod($foods)
    {
        $result = false;
        foreach ($foods as $food) {
            if ($food->getFAndBFacilities()) {
                $result = true;
            }
        }

        return $result;
    }

    public function hasFoodsInSection($foods): array
    {
        $result = [];
        foreach ($foods as $food) {
            $result[$food->getFAndBFacilities()][] = $food;
        }

        return $result;
    }

    public function getDirectExpenseForSection(string $modelName, string $sectionName, int $index = -1)
    {
        $directExpenses = $this->departmentExpenses->where('section_name', $sectionName)
            ->where('model_name', $modelName)
            ->values();
        if ($index == -1) {
            return $directExpenses;
        }
        if (! isset($directExpenses[$index])) {
            return null;
        }

        return $directExpenses[$index];
    }

    public function getBaseManagementFee(): ?ManagementFee
    {
        return $this->getManagementFeeForSection('management', BASE_MANAGEMENT_FEES_AS_PERCENTAGE_FROM_REVENUES, 0);
    }

    public function getIncentiveManagementFee(): ?ManagementFee
    {
        return $this->getManagementFeeForSection('management', INCENTIVE_MANAGEMENT_FEES_AS_PERCENTAGE_FROM_REVENUES, 0);
    }

    public function getManagementFeeForSection(string $modelName, string $sectionName, int $index = -1)
    {
        $managementFees = $this->managementFees->where('section_name', $sectionName)
            ->where('model_name', $modelName)
            ->values();
        if ($index == -1) {
            return $managementFees;
        }
        if (! isset($managementFees[$index])) {
            return null;
        }

        return $managementFees[$index];
    }

    public function getPropertyCostBreakdownForSection(string $modelName, string $sectionName, int $index = -1)
    {
        $propertyCostBreakDown = $this->propertyAcquisitionBreakDown->where('section_name', $sectionName)
            ->where('model_name', $modelName)
            ->values();
        if ($index == -1) {
            return $propertyCostBreakDown;
        }
        if (! isset($propertyCostBreakDown[$index])) {
            return null;
        }

        return $propertyCostBreakDown[$index];
    }

    public function getDirectExpensesForSection(string $modelName, string $sectionName)
    {
        $directExpenses = $this->departmentExpenses->where('section_name', $sectionName)
            ->where('model_name', $modelName)
            ->values();

        return $directExpenses;
    }
    // public function getDirectExpenseNameForSection(string $sectionName , int $index)
    // {
    // 	$directExpenses = $this->departmentExpenses->where('section_name',$sectionName);

    // 	if(!isset($directExpenses[$index])){
    // 		return null ;
    // 	}
    // 	return $directExpenses[$index]->getName();
    // }
    public function hasRoomsManpower()
    {
        return (bool) $this->has_rooms_manpower;
    }

    /**
     * The stock on hand when the hotel opens is paid in cash in the first operating month (it is not bought on credit, so the supplier payables do not see it).
     * Only the cash-out lines get it: the payments handed to getDisposablePayableStatement() stay as they were.
     */
    protected function withOpeningStockPayment(array $payments, array $inventory): array
    {
        foreach ($inventory as $directExpenseIdentifier => $items) {
            if (! is_numeric($directExpenseIdentifier)) {
                continue;
            }
            $directExpense = $this->departmentExpenses->where('id', $directExpenseIdentifier)->first();
            $opening = (float) $directExpense->getBeginningInventoryBalanceValue();
            $firstMonth = array_key_first($items['purchases'] ?? []);
            if ($opening > 0 && $firstMonth !== null) {
                $name = $directExpense->getName();
                $payments[$name][$firstMonth] = ($payments[$name][$firstMonth] ?? 0) + $opening;
            }
        }
        unset($payments['total']);
        $payments['total'] = getTotalOfTwoDimArr($payments);

        return $payments;
    }

    protected function applyCollectionPolicy(array $purchases, array $dateIndexWithDate, array $dateWithDateIndex)
    {
        $disposablePayment = [];
        $collectionPolicyService = new CollectionPolicyService;

        foreach ($purchases as $directExpenseIdentifier => $items) {
            if (is_numeric($directExpenseIdentifier)) {
                $purchase = $items['purchases'] ?? [];
                $directExpense = $this->departmentExpenses->where('id', $directExpenseIdentifier)->first();
                $collectionPolicyValue = ['due_in_days' => [0, $directExpense->getDueDays()], 'rate' => [$directExpense->getCashPayment(), $directExpense->getDeferredPaymentPercentage()]];
                $disposablePayment[$directExpense->getName()] = $collectionPolicyService->applyCollectionPolicy(true, 'customize', $collectionPolicyValue, $purchase, $dateIndexWithDate, $dateWithDateIndex, $this);
            }
        }
        $disposablePayment['total'] = getTotalOfTwoDimArr($disposablePayment);

        return $disposablePayment;
    }

    protected function getDisposablePayableStatement(array $purchases, array $disposablePayment, array $dateIndexWithDate, $convertIndexesToNames = false): array
    {
        $supplierPayableEndBalanceService = new SupplierPayableEndBalance;
        $supplierPayableEndBalanceForDirectExpense = [];
        foreach ($purchases as $directExpenseIdentifier => $arrayWithPurchasesKey) {
            if (is_numeric($directExpenseIdentifier)) {
                $disposablePaymentForCurrentItem = [];
                $purchase = isset($arrayWithPurchasesKey['purchases']) ? $arrayWithPurchasesKey['purchases'] : $arrayWithPurchasesKey;
                $directExpense = $this->departmentExpenses->where('id', $directExpenseIdentifier)->first();
                $directExpenseName = $directExpense->getName();
                if (isset($disposablePayment[$directExpenseName])) {
                    $disposablePaymentForCurrentItem = $disposablePayment[$directExpenseName];
                } elseif (isset($disposablePayment[$directExpenseIdentifier])) {
                    $disposablePaymentForCurrentItem = $disposablePayment[$directExpenseIdentifier];
                }
                $resultIdentifier = $convertIndexesToNames ? $directExpenseName : $directExpenseIdentifier;

                $supplierPayableEndBalanceForDirectExpense[$resultIdentifier] = $supplierPayableEndBalanceService->getDisposablePayableStatement($purchase, $disposablePaymentForCurrentItem, $dateIndexWithDate, $this);
            }
        }

        $supplierPayableEndBalanceForDirectExpense['total'] = sumSecondKeyInThreeDimArr($supplierPayableEndBalanceForDirectExpense);

        return $supplierPayableEndBalanceForDirectExpense;
    }

    protected function calculateFoodsOrCasinoOrOtherGuestCount($foods, array $monthlyGuestCountForRoom, array $dates, array $operationDurationPerYear, array $daysNumbersOfMonths, array $datesIndexWithYearIndex, $debug = false)
    {
        $result = [];

        foreach ($foods as $food) {
            $foodIdentifier = $food->getIdentifier();
            $foodRevenueMethod = $food->getFAndBFacilities();

            foreach ($dates as $date) {
                $year = $datesIndexWithYearIndex[$date];
                $operationZeroOrOne = $operationDurationPerYear[$year][$date] ?? null;
                $guestCapturePercentage = $food->getGuestCaptureCoverPercentage($year) / 100;
                $monthlyGuestNumberAtDate = $monthlyGuestCountForRoom[$date] ?? 0;

                if (
                    $foodRevenueMethod == guest_capture_x_cover_value_per_guest_method
                    || $foodRevenueMethod == guest_capture_x_meals_per_guest_x_cover_value_per_meal_method
                    || $foodRevenueMethod == guest_capture_casino_charges_per_guest_method
                    || $foodRevenueMethod == guest_capture_x_charges_per_guest_method
                ) {
                    $result[$foodIdentifier][$date] = $guestCapturePercentage * $monthlyGuestNumberAtDate * $operationZeroOrOne;
                } elseif ($foodRevenueMethod == guest_count_target_per_day_charges_per_guest_method || $foodRevenueMethod == cover_count_target_per_day_x_cover_value_method) {
                    $coverCountTargetPerDay = $food->getDailyCountTarget($year);
                    $dayNumberOfCurrentMonth = $daysNumbersOfMonths[$year][$date] ?? 0;
                    $result[$foodIdentifier][$date] = $dayNumberOfCurrentMonth * $coverCountTargetPerDay * $operationZeroOrOne;
                } elseif (get_class($food) == 'App\Models\Casino' && $foodRevenueMethod == percentage_from_rooms_revenue
                || get_class($food) == 'App\Models\Other' && $foodRevenueMethod == percentage_from_rooms_revenue
                || get_class($food) == 'App\Models\Food' && $foodRevenueMethod == percentage_from_rooms_revenue
                ) {
                    $percentageFromRevenueAtYear = $food->getPercentageFromRevenue($year) / 100;
                    $result[$foodIdentifier][$date] = $percentageFromRevenueAtYear * $monthlyGuestNumberAtDate * $operationZeroOrOne;
                }

                $value = $result[$foodIdentifier][$date] ?? 0;
                $result['total'][$date] = isset($result['total'][$date]) ? $result['total'][$date] + $value : $value;
            }
        }
        $result['totalOfEachYear'] = sumForEachYear($result['total'] ?? [], $datesIndexWithYearIndex);

        return $result;
    }

    protected function calculateMeetingGuestCount($meetings, array $dates, array $daysCountPerYear, array $operationDurationPerYearAsArray, array $monthlyFoodGuestCount, array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate, array $dateWithMonthNumber)
    {
        // $meetings, array $dates, array $daysCountPerYear, array $operationDurationPerYearAsArray, array $monthlyFoodGuestCount, array $datesAsStringAndIndex,array $datesIndexWithYearIndex,array $yearIndexWithYear,array $dateIndexWithDate
        $result = [];
        $guestMeetingSeasonalityInterval = $this->getGuestMeetingSeasonalityInterval();
        $guestMeetingSeasonalityType = $this->getGuestMeetingSeasonalityType();
        $rentMeetingSeasonalityInterval = $this->getRentMeetingSeasonalityInterval();
        $rentMeetingSeasonalityType = $this->getRentMeetingSeasonalityType();

        foreach ($meetings as $meeting) {
            $meetingIdentifier = $meeting->getIdentifier();
            $meetingRevenueMethod = $meeting->getFAndBFacilities();
            $totalGuestCapacity = $meeting->getTotalGuestCapacityCount();
            $guestFacilityOccupancyRates = $meeting->getGuestFacilityOccupancyRate();
            $meetingFacility = $meeting->getFAndBFacilities();
            $meetingFacilityAdjustedSeasonality[$meetingIdentifier] = $this->calculateMeetingFacilityAdjustedSeasonalityAtDate($meeting, $meetingFacility, $guestMeetingSeasonalityInterval, $rentMeetingSeasonalityInterval, $guestMeetingSeasonalityType, $rentMeetingSeasonalityType, $operationDurationPerYearAsArray, $datesAsStringAndIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber);

            foreach ($dates as $date) {
                $year = $datesIndexWithYearIndex[$date];
                if ($meetingRevenueMethod == guest_count_charges_per_guest_occupancy_rate_method
                    || $meetingRevenueMethod == facility_count_daily_rent_occupancy_rate_method
                ) {
                    $daysCountPerYearAtYear = $daysCountPerYear['totalOfEachYear'][$year] ?? 0;
                    $guestFacilityOccupancyRateAtYear = $guestFacilityOccupancyRates[$year] ?? 0;
                    $daysCountPerYearAtYear = $daysCountPerYear['totalOfEachYear'][$year] ?? 0;
                    $guestFacilityOccupancyRateAtYear = $guestFacilityOccupancyRateAtYear / 100;
                    $meetingFacilityAdjustedSeasonalityAtDate = $meetingFacilityAdjustedSeasonality[$meetingIdentifier][$date] ?? 0;
                    $result[$meetingIdentifier][$date] = $daysCountPerYearAtYear * $guestFacilityOccupancyRateAtYear * $meetingFacilityAdjustedSeasonalityAtDate * $totalGuestCapacity;
                } elseif ($meetingRevenueMethod == percentage_from_f_b_revenue) {
                    $monthlyFoodGuestCountAtDate = $monthlyFoodGuestCount['total'][$date] ?? 0;
                    $meetingFacilityAdjustedSeasonalityAtDate = $meetingFacilityAdjustedSeasonality[$meetingIdentifier][$date] ?? 0;
                    $percentageFromRevenueAtFAndB = $meeting->getPercentageFromRevenue($year) / 100;
                    $result[$meetingIdentifier][$date] = $monthlyFoodGuestCountAtDate * $percentageFromRevenueAtFAndB;
                }
                $value = $result[$meetingIdentifier][$date] ?? 0;
                $result['total'][$date] = isset($result['total'][$date]) ? $result['total'][$date] + $value : $value;
            }
        }
        $result['totalOfEachYear'] = sumForEachYear($result['total'] ?? [], $datesIndexWithYearIndex);

        return $result;
    }

    public function getOperationDates(): array
    {
        return $this->operation_dates ?: [];
    }

    public function getStudyDates(): array
    {
        return $this->study_dates ?: [];
    }

    public function getFullDateStringFromDateIndex(int $dateIndex): string
    {
        return App('dateIndexWithDate')[$dateIndex];
    }

    public function getYearIndexFromDateIndex(int $dateIndex): int
    {
        return App('datesIndexWithYearIndex')[$dateIndex];
    }

    public function getYearFromYearIndex(int $yearIndex): int
    {
        return App('yearIndexWithYear')[$yearIndex];
    }

    public function getDateIndexFromDate(string $date)
    {
        return App('dateWithDateIndex')[$date];
    }

    public function getDatesAsStringAndIndex()
    {
        return array_flip($this->getStudyDates());
    }

    public function getDaysNumbersOfMonth(array $datesAsStringAndIndex, array $years, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate)
    {
        $result = [];
        foreach ($years as $yearIndex) {
            $currentYear = $yearIndexWithYear[$yearIndex];
            $dateAsIndex = $this->getFirstDateIndexInYearIndex($datesAsStringAndIndex, $yearIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate);
            $dateAsString = $dateIndexWithDate[$dateAsIndex];
            $firstMonthInCurrentYear = explode('-', $dateAsString)[1] ?? 1;
            for ($monthNumber = $firstMonthInCurrentYear; $monthNumber <= 12; $monthNumber++) {
                $monthNumber = sprintf('%02d', $monthNumber);
                $date = '01-'.$monthNumber.'-'.$currentYear;
                // 01-01-2023

                $dateIndex = $datesAsStringAndIndex[$date];
                $currentDate = LegacyCarbon::make($date);
                $result[$yearIndex][$dateIndex] = $currentDate->month((int) $monthNumber)->daysInMonth;
            }
        }

        return $result;
    }

    public function getOnlyDatesOfActiveOperation(array $operationDurationPerYear, array $dateIndexWithDate, $removeZeros = true)
    {
        $result = [];
        foreach ($operationDurationPerYear as $currentYear => $datesAndZerosOrOnes) {
            foreach ($datesAndZerosOrOnes as $dateIndex => $zeroOrOneAtDate) {
                if ($zeroOrOneAtDate || ! $removeZeros) {
                    if (is_numeric($dateIndex)) {
                        $dateFormatted = $dateIndexWithDate[$dateIndex];
                    } else {
                        $dateFormatted = $dateIndex;
                    }
                    $result[$dateFormatted] = $dateIndex;
                }
            }
        }

        return $result;
    }

    public function getOnlyDatesOfActiveStudy(array $studyDurationPerYear, array $dateIndexWithDate)
    {
        $result = [];
        foreach ($studyDurationPerYear as $currentYear => $datesAndZerosOrOnes) {
            foreach ($datesAndZerosOrOnes as $dateIndex => $zeroOrOneAtDate) {
                if (is_numeric($dateIndex)) {
                    $dateFormatted = $dateIndexWithDate[$dateIndex];
                } else {
                    $dateFormatted = $dateIndex;
                }
                $result[$dateFormatted] = $dateIndex;
            }
        }

        return $result;
    }

    public function getAcquisition()
    {
        $acquisition = $this->acquisition;

        return $acquisition ? $acquisition->load('loans') : null;
    }

    public function getPropertyAcquisition()
    {
        $propertyAcquisition = $this->propertyAcquisition;

        return $propertyAcquisition ? $propertyAcquisition->load('loans') : null;
    }

    public function calculatePaymentForEnergyFixedExpenses(CollectionPolicyService $collectionPolicyService, array $inflatedValueForEnergyFixedExpenses, array $dateIndexWithDate, array $dateWithDateIndex)
    {
        $payment = [];
        foreach ($inflatedValueForEnergyFixedExpenses as $identifier => $dateValues) {
            if (is_numeric($identifier)) {
                $departmentExpense = $this->departmentExpenses->where('id', $identifier)->first();
                $paymentTerm = $departmentExpense->getOpexPaymentTerm();
                $payment[$identifier] = $collectionPolicyService->applyCollectionPolicy(true, 'system_default', $paymentTerm, $dateValues, $dateIndexWithDate, $dateWithDateIndex, $this);
            }
        }

        return $payment;
    }

    public function getGeneralCollectionPolicyTypeForType(string $type)
    {
        $type = Str::plural($type);
        if ($type == 'gamings') {
            $type = 'casinos';
        }
        if ($type == 'food') {
            $type = 'foods';
        }

        return $this[$type.'_general_collection_policy_type'];
    }

    public function generalCollectionPolicyIntervalForType(string $type)
    {
        // $type like [room , casino .. etc]
        $type = Str::plural($type);
        if ($type == 'gamings') {
            $type = 'casinos';
        }

        return $this[$type.'_general_collection_policy_interval'];
    }

    public function isGeneralSystemDefaultCollectionPolicyForType(string $type)
    {
        // $type like [room , casino .. etc]
        $type = Str::plural($type);
        if ($type == 'gamings') {
            $type = 'casinos';
        }
        if ($type == 'food') {
            $type = 'foods';
        }

        return $this->getGeneralCollectionPolicyTypeForType($type) == 'system_default';
    }

    public function isGeneralCustomizeCollectionPolicyForType(string $type)
    {
        // $type like [room , casino .. etc]
        $type = Str::plural($type);
        if ($type == 'gamings') {
            $type = 'casinos';
        }

        return $this->getGeneralCollectionPolicyTypeForType($type) == 'customize';
    }

    public function getGeneralCollectionPolicyRateAndDueInDays(int $index, $type, string $modelType)
    {
        // type [rate or due_in_days]
        // modelType like [room , casino .. etc]
        $modelType = Str::plural($modelType);
        if ($modelType == 'food') {
            $modelType = 'foods';
        }
        if ($modelType == 'gaming' || $modelType == 'gamings') {
            $modelType = 'casinos';
        }

        if (! $this->isGeneralCustomizeCollectionPolicyForType($modelType)) {
            return [
                'rate' => 0,
                'due_in_days' => 0,
            ][$type];
        }

        return [
            'rate' => ((array) json_decode($this[$modelType.'_general_collection_policy_value']))['rate'][$index] ?? 0,
            'due_in_days' => ((array) json_decode($this[$modelType.'_general_collection_policy_value']))['due_in_days'][$index] ?? 0,
        ][$type];
    }

    public function getCollectionPolicyValue(string $type)
    {
        // type like [room , casino .. etc]
        $type = Str::plural($type);
        if ($type == 'gamings') {
            $type = 'casinos';
        }
        $collectionPolicyValue = convertJsonToArray($this[$type.'_general_collection_policy_value']);

        return $collectionPolicyValue;
    }

    public function convertStringDatesFromArrayKeysToIndexes(array $items, array $datesAsStringAndIndex)
    {
        $newItems = [];
        foreach ($items as $dateAsString => $value) {
            if (is_numeric($dateAsString)) {
                throw new \Exception('Custom Exception - Data As String Must Be Date String Format 01-12-2025');
            }
            $newItems[$datesAsStringAndIndex[$dateAsString]] = $value;
        }

        return $newItems;
    }

    /**
     * By Given Array As Date And Value [25=>2500,26=>2600], Convert It To Date String Array [10-10-2025=>2500]
     */
    public function replaceIndexWithItsStringDate(array $dates, array $dateIndexWithDate): array
    {
        $stringFormattedDates = [];
        foreach ($dates as $dateIndex => $value) {
            if (is_numeric($dateIndex)) {
                // is index date like 25
                $stringFormattedDates[$dateIndexWithDate[$dateIndex]] = $value;
            } else {
                // is already date string like 10-10-2025
                $stringFormattedDates[$dateIndex] = $value;
            }
        }

        return $stringFormattedDates;
    }

    /**
     * Determine If User Has visit Section Like Casino
     *
     * @param  string  $modelName  [food , room , casino , meeting , other]
     */
    public function hasVisitSection(string $modelName): bool
    {
        $modelName = Str::singular($modelName);

        return $this['has_visit_'.$modelName.'_section'];
    }

    public function getHardConstructionStartDateAsIndex(array $dateWithDateIndex): int
    {
        $developmentStartDate = $this->getDevelopmentStartDateFormatted();

        return $dateWithDateIndex[$developmentStartDate];
    }

    public function getSoftConstructionStartDateAsIndex(array $dateWithDateIndex): int
    {
        $developmentStartDate = $this->getDevelopmentStartDateFormatted();

        return $dateWithDateIndex[$developmentStartDate];
    }

    public function getFFEStartDateAsIndex(array $dateWithDateIndex): int
    {
        $developmentStartDate = $this->getDevelopmentStartDateFormatted();

        return $dateWithDateIndex[$developmentStartDate];
    }

    public function getHardConstructionStartDateAsString(): string
    {
        return $this->getDevelopmentStartDateFormatted();
    }

    public function getSoftConstructionStartDateAsString(): string
    {
        return $this->getDevelopmentStartDateFormatted();
    }

    public function getFFEStartDateAsString(): string
    {
        return $this->getDevelopmentStartDateFormatted();
    }

    public function removeDatesBeforeDate(array $items, string $limitDate)
    {
        $newItems = [];
        $limitDate = LegacyCarbon::make($limitDate);
        foreach ($items as $year => $dateAndValues) {
            foreach ($dateAndValues as $date => $value) {
                $currentDate = LegacyCarbon::make($date);
                if ($limitDate->lessThanOrEqualTo($currentDate)) {
                    $newItems[$year][$date] = $value;
                }
            }
        }

        return $newItems;
    }

    public function removeDatesBeforeDateOneDim(array $dateAndValues, int $limitDate)
    {
        $newItems = [];
        // $limitDate = Carbon::make($limitDate);
        foreach ($dateAndValues as $dateAsIndex => $value) {
            // $currentDate = Carbon::make($date);
            if ($limitDate <= $dateAsIndex) {
                $newItems[$dateAsIndex] = $value;
            }
            // if ($limitDate->lessThanOrEqualTo($currentDate)) {
            // }
        }

        return $newItems;
    }

    public function removeDatesAfterDate(array $dateAndValues, int $limitDateAsIndex)
    {
        $newItems = [];
        // $limitDate = Carbon::make($limitDate);
        foreach ($dateAndValues as $dateAsIndex => $value) {
            // $currentDate = Carbon::make($date);
            if ($dateAsIndex <= $limitDateAsIndex) {
                $newItems[$dateAsIndex] = $value;

            }
            // if ($currentDate->lessThanOrEqualTo($limitDate)) {
            // }
        }

        return $newItems;
    }

    protected function convertIndexYearsAndMonthsToString(array $items, array $yearIndexWithYear, array $dateIndexWithDate)
    {
        $result = [];
        foreach ($items as $yearAsIndex => $dateAsIndexAndValue) {
            foreach ($dateAsIndexAndValue as $dateAsIndex => $value) {
                if (is_numeric($yearAsIndex) && is_numeric($dateAsIndex)) {
                    $yearAsString = $yearIndexWithYear[$yearAsIndex];
                    $dateAsString = $dateIndexWithDate[$dateAsIndex];
                    $result[$yearAsString][$dateAsString] = $value;
                } else {
                    throw new \Exception('Custom Exception .. Year Passed [ '.$yearAsIndex.' ] And Full Date [ '.$dateAsIndex.' ] And Both Must Be Numeric Values');
                }
            }
        }

        return $result;
    }

    public function convertArrayOfStringDatesToStringDatesAndDateIndex(array $items, array $dateIndexWithDate, array $dateWithDateIndex)
    {
        $newItems = [];

        foreach ($items as $date => $sumValue) {
            if (is_numeric($date)) {
                $newItems[$dateIndexWithDate[$date]] = $date;
            } else {
                $newItems[$date] = $dateWithDateIndex[$date];
            }
        }

        return $newItems;
    }

    public function convertArrayOfStringKeysToDateString(array $items, array $datesAsStringAndIndex)
    {
        $newItems = [];

        foreach ($items as $date => $sumValue) {
            if (! is_numeric($date)) {
                $newItems[$datesAsStringAndIndex[$date]] = $sumValue;
            } else {
                $newItems[$date] = $sumValue;
            }
        }

        return $newItems;
    }

    public function convertArrayOfIndexKeysToDateStringAsIndexWithItsOriginalValue(array $items, array $dateIndexWithDate)
    {
        $newItems = [];

        foreach ($items as $dateAsIndex => $value) {
            if (is_numeric($dateAsIndex)) {
                $newItems[$dateIndexWithDate[$dateAsIndex]] = $value;
            } else {
                $newItems[$dateAsIndex] = $value;
            }
        }

        return $newItems;
    }

    public function convertArrayOfIndexKeysToIndexAsDateStringWithItsOriginalValue(array $items, array $datesAsStringAndIndex)
    {
        $newItems = [];

        foreach ($items as $dateAsIndex => $value) {
            if (is_numeric($dateAsIndex)) {
                $newItems[$dateAsIndex] = $value;
            } else {
                $newItems[$datesAsStringAndIndex[$dateAsIndex]] = $value;
            }
        }

        return $newItems;
    }

    public function calculateBaseManagementFeesAmounts(array $totalHotelRevenues, array $datesIndexWithYearIndex): array
    {
        $baseManagementFees = $this->getBaseManagementFee();
        if (! $baseManagementFees) {
            return [];
        }
        $baseManagementFeesAmounts = [];
        $baseManagementFeesPayload = $baseManagementFees->getPayload();
        foreach ($totalHotelRevenues as $dateAsIndex => $totalHotelRevenueAtDate) {
            $yearIndexOfHotelRevenue = $datesIndexWithYearIndex[$dateAsIndex];
            $baseManagementFeesPayloadAtDate = $baseManagementFeesPayload[$yearIndexOfHotelRevenue] ?? 0;
            $baseManagementFeesAmounts[$dateAsIndex] = $baseManagementFeesPayloadAtDate / 100 * $totalHotelRevenueAtDate;
        }

        return $baseManagementFeesAmounts;
    }

    public function calculateIncentiveManagementFeesAmounts(array $monthlyEbitda, string $intervalName, array $datesIndexWithYearIndex, array $dateIndexWithDate, array $dateWithDateIndex)
    {
        if ($intervalName != 'annually') {
            return [];
        }

        $annuallyEbitda = sumIntervalsIndexes($monthlyEbitda, 'annually', $this->financialYearStartMonth(), $dateIndexWithDate);

        $incentiveManagementFees = $this->getIncentiveManagementFee();
        if (! $incentiveManagementFees) {
            return [];
        }
        $incentiveManagementFeesAmounts = [];
        $incentiveManagementFeesPayload = $incentiveManagementFees->getPayload();
        foreach ($annuallyEbitda as $dateAsIndex => $annuallyEbitdaAtDate) {

            $yearAsIndex = $datesIndexWithYearIndex[$dateAsIndex];
            $incentiveManagementFeesPayloadAtDate = $incentiveManagementFeesPayload[$yearAsIndex] ?? 0;
            $incentiveManagementFeesAmounts[$dateAsIndex] = $annuallyEbitdaAtDate < 0 ? 0 : $incentiveManagementFeesPayloadAtDate / 100 * $annuallyEbitdaAtDate;
        }

        return $incentiveManagementFeesAmounts;
    }

    public function calculateCorporateTaxes(array $monthlyEbt, string $intervalName, array $dateIndexWithDate, array $dateWithDateIndex)
    {
        if ($intervalName != 'annually') {
            return [];
        }
        $annuallyEbt = sumIntervalsIndexes($monthlyEbt, 'annually', $this->financialYearStartMonth(), $dateIndexWithDate);

        $incentiveManagementFees = $this->getIncentiveManagementFee();

        if (! $incentiveManagementFees) {
            return [];
        }
        $corporateTaxesAmounts = [];
        $corporateTaxesRate = $this->getCorporateTaxesRate();
        foreach ($annuallyEbt as $dateAsIndex => $annuallyEbtAtDate) {
            $corporateTaxesAmounts[$dateAsIndex] = $annuallyEbtAtDate < 0 ? 0 : $corporateTaxesRate / 100 * $annuallyEbtAtDate;
        }

        return $corporateTaxesAmounts;
    }

    public function calculateCorporateTaxesStatement(array $datesAsIndexes, array $corporateTaxes, string $intervalName, array $dateIndexWithDate, array $dateWithDateIndex): array
    {
        $corporateTaxesStatements = [];
        $corporateTaxesPayments = [];
        foreach ($datesAsIndexes as $dateAsIndex) {
            $corporateTaxesPayments[$dateAsIndex + 4] = $corporateTaxes[$dateAsIndex] ?? 0;
        }

        $corporateTaxesForIntervals = [
            'monthly' => $corporateTaxes,
            'quarterly' => sumIntervalsIndexes($corporateTaxes, 'quarterly', $this->financialYearStartMonth(), $dateIndexWithDate),
            'semi-annually' => sumIntervalsIndexes($corporateTaxes, 'semi-annually', $this->financialYearStartMonth(), $dateIndexWithDate),
            'annually' => sumIntervalsIndexes($corporateTaxes, 'annually', $this->financialYearStartMonth(), $dateIndexWithDate),
        ];

        $corporateTaxesPaymentsForIntervals = [
            'monthly' => $corporateTaxesPayments,
            'quarterly' => sumIntervalsIndexes($corporateTaxesPayments, 'quarterly', $this->financialYearStartMonth(), $dateIndexWithDate),
            'semi-annually' => sumIntervalsIndexes($corporateTaxesPayments, 'semi-annually', $this->financialYearStartMonth(), $dateIndexWithDate),
            'annually' => sumIntervalsIndexes($corporateTaxesPayments, 'annually', $this->financialYearStartMonth(), $dateIndexWithDate),
        ];

        $intervalDates = [
            'monthly' => $datesAsIndexes,
            'quarterly' => sumIntervalsIndexes($datesAsIndexes, 'quarterly', $this->financialYearStartMonth(), $dateIndexWithDate),
            'semi-annually' => sumIntervalsIndexes($datesAsIndexes, 'semi-annually', $this->financialYearStartMonth(), $dateIndexWithDate),
            'annually' => sumIntervalsIndexes($datesAsIndexes, 'annually', $this->financialYearStartMonth(), $dateIndexWithDate),
        ];
        foreach (getIntervalFormatted() as $intervalName => $intervalNameFormatted) {
            $beginningBalance = 0;
            foreach ($intervalDates[$intervalName] as $dateAsIndex => $accumulatedValue) {
                $date = $dateAsIndex;
                if (! is_numeric($date)) {
                    $date = $dateWithDateIndex[$dateAsIndex];
                    $dateAsIndex = $date;
                }
                $corporateTaxesStatements[$intervalName]['beginning_balance'][$date] = $beginningBalance;
                $corporateTaxesValue = $corporateTaxesForIntervals[$intervalName][$dateAsIndex] ?? 0;
                $totalDue[$intervalName][$date] = $corporateTaxesValue + $beginningBalance;
                $currentPayment = $corporateTaxesPaymentsForIntervals[$intervalName][$dateAsIndex] ?? 0;
                $endBalance[$intervalName][$date] = $totalDue[$intervalName][$date] - ($currentPayment);
                $beginningBalance = $endBalance[$intervalName][$date];
                $corporateTaxesStatements[$intervalName]['corporate taxes'][$date] = $corporateTaxesValue;
                $corporateTaxesStatements[$intervalName]['total_due'][$date] = $totalDue[$intervalName][$date];
                $corporateTaxesStatements[$intervalName]['payments'][$date] = $currentPayment;

                $corporateTaxesStatements[$intervalName]['end_balance'][$date] = $endBalance[$intervalName][$date];
            }
        }

        return $corporateTaxesStatements ?? [];
    }

    public function calculateManagementFeesStatement(array $datesAsIndexes, array $managementFeesAmounts, string $intervalName, array $dateIndexWithDate, array $dateWithDateIndex): array
    {
        $managementFeesStatements = [];
        $managementFeesPayments = [];
        foreach ($datesAsIndexes as $dateAsIndex) {
            $managementFeesAmountAtDate = $managementFeesAmounts[$dateAsIndex] ?? 0;
            $managementFeesAmountAtDate = $managementFeesAmountAtDate / 12;
            if (isset($managementFeesAmounts[$dateAsIndex])) {
                for ($i = 1; $i <= 12; $i++) {
                    $managementFeesPayments[$dateAsIndex + $i] = $managementFeesAmountAtDate;
                }
            }
        }
        $managementFeesForIntervals = [
            'monthly' => $managementFeesAmounts,
            'quarterly' => sumIntervalsIndexes($managementFeesAmounts, 'quarterly', $this->financialYearStartMonth(), $dateIndexWithDate),
            'semi-annually' => sumIntervalsIndexes($managementFeesAmounts, 'semi-annually', $this->financialYearStartMonth(), $dateIndexWithDate),
            'annually' => sumIntervalsIndexes($managementFeesAmounts, 'annually', $this->financialYearStartMonth(), $dateIndexWithDate),
        ];
        $paymentsForIntervals = [
            'monthly' => $managementFeesPayments,
            'quarterly' => sumIntervalsIndexes($managementFeesPayments, 'quarterly', $this->financialYearStartMonth(), $dateIndexWithDate),
            'semi-annually' => sumIntervalsIndexes($managementFeesPayments, 'semi-annually', $this->financialYearStartMonth(), $dateIndexWithDate),
            'annually' => sumIntervalsIndexes($managementFeesPayments, 'annually', $this->financialYearStartMonth(), $dateIndexWithDate),
        ];
        $intervalDates = [
            'monthly' => $datesAsIndexes,
            'quarterly' => sumIntervalsIndexes($datesAsIndexes, 'quarterly', $this->financialYearStartMonth(), $dateIndexWithDate),
            'semi-annually' => sumIntervalsIndexes($datesAsIndexes, 'semi-annually', $this->financialYearStartMonth(), $dateIndexWithDate),
            'annually' => sumIntervalsIndexes($datesAsIndexes, 'annually', $this->financialYearStartMonth(), $dateIndexWithDate),
        ];
        foreach (getIntervalFormatted() as $intervalName => $intervalNameFormatted) {
            $beginningBalance = 0;
            foreach ($intervalDates[$intervalName] as $dateAsIndex => $accumulatedValue) {
                // $dateAsIndex = $dateWithDateIndex[$dateAsString];

                $date = $dateAsIndex;
                if (! is_numeric($date)) {
                    $date = $dateWithDateIndex[$dateAsIndex];
                    $dateAsIndex = $date;
                }
                $managementFeesStatements[$intervalName]['beginning_balance'][$date] = $beginningBalance;
                $managementFeesAmount = $managementFeesForIntervals[$intervalName][$dateAsIndex] ?? 0;
                $totalDue[$intervalName][$date] = $managementFeesAmount + $beginningBalance;
                $currentPayment = $paymentsForIntervals[$intervalName][$dateAsIndex] ?? 0;
                $endBalance[$intervalName][$date] = $totalDue[$intervalName][$date] - ($currentPayment);
                $beginningBalance = $endBalance[$intervalName][$date];
                $managementFeesStatements[$intervalName]['management fees'][$date] = $managementFeesAmount;
                $managementFeesStatements[$intervalName]['total_due'][$date] = $totalDue[$intervalName][$date];
                $managementFeesStatements[$intervalName]['payments'][$date] = $currentPayment;
                $managementFeesStatements[$intervalName]['end_balance'][$date] = $endBalance[$intervalName][$date];
            }
        }

        return $managementFeesStatements ?? [];
    }

    public function getFFEItemForSection(string $modelName, string $sectionName, int $index = -1)
    {
        $ffeItems = $this->ffeItems->where('section_name', $sectionName)
            ->where('model_name', $modelName)
            ->values();
        if ($index == -1) {
            return $ffeItems;
        }
        if (! isset($ffeItems[$index])) {
            return null;
        }

        return $ffeItems[$index];
    }

    public function getFFE()
    {
        return $this->ffe;
    }

    public function formatFixedAssetsSuppliersForView(array $studyDatesAsStringForValue, array $fixedAssetsDatesAndValues, array $payments, array $dateIndexWithDate, array $dateWithDateIndex)
    {
        $datesAsStrings = $this->convertArrayOfStringDatesToStringDatesAndDateIndex($studyDatesAsStringForValue, $dateIndexWithDate, $dateWithDateIndex);
        $datesAsStrings = convertValuesToZero($datesAsStrings);
        $fixedAssetsDatesAndValues = array_merge($datesAsStrings, $fixedAssetsDatesAndValues);

        $fixedAssetsForIntervals = [
            'monthly' => $fixedAssetsDatesAndValues,
            'quarterly' => sumIntervalsIndexes($fixedAssetsDatesAndValues, 'quarterly', $this->financialYearStartMonth(), $dateIndexWithDate),
            'semi-annually' => sumIntervalsIndexes($fixedAssetsDatesAndValues, 'semi-annually', $this->financialYearStartMonth(), $dateIndexWithDate),
            'annually' => sumIntervalsIndexes($fixedAssetsDatesAndValues, 'annually', $this->financialYearStartMonth(), $dateIndexWithDate),
        ];
        $paymentsForInterval = [
            'monthly' => $payments,
            'quarterly' => sumIntervalsIndexes($payments, 'quarterly', $this->financialYearStartMonth(), $dateIndexWithDate),
            'semi-annually' => sumIntervalsIndexes($payments, 'semi-annually', $this->financialYearStartMonth(), $dateIndexWithDate),
            'annually' => sumIntervalsIndexes($payments, 'annually', $this->financialYearStartMonth(), $dateIndexWithDate),
        ];

        $result = [];
        foreach (getIntervalFormatted() as $intervalName => $intervalNameFormatted) {
            $beginningBalance = 0;
            foreach ($fixedAssetsForIntervals[$intervalName] as $dateAsIndex => $fixedAssetAtValue) {
                if (! is_numeric($dateAsIndex)) {
                    $dateAsIndex = $dateWithDateIndex[$dateAsIndex];
                }
                $dateAsString = $dateIndexWithDate[$dateAsIndex];
                $value = $fixedAssetAtValue;
                $result[$intervalName]['beginning_balance'][$dateAsIndex] = $beginningBalance;
                $totalDue[$dateAsIndex] = $value + $beginningBalance;
                $collectionAtDate = getValueFromArrayStringAndIndex($paymentsForInterval[$intervalName], $dateAsString, $dateAsIndex, 0);

                $endBalance[$dateAsIndex] = $totalDue[$dateAsIndex] - $collectionAtDate;
                $beginningBalance = $endBalance[$dateAsIndex];
                $result[$intervalName]['purchases'][$dateAsIndex] = $value;
                $result[$intervalName]['total_due'][$dateAsIndex] = $totalDue[$dateAsIndex];
                $result[$intervalName]['payments'][$dateAsIndex] = $collectionAtDate;
                $result[$intervalName]['end_balance'][$dateAsIndex] = $endBalance[$dateAsIndex];
            }
        }

        return $result;
    }

    // public function mergeDates(array $first, array $second,array $datesAsStringAndIndex)
    // {
    // 	$dates = [];
    // 	if (!is_numeric(array_key_first($first))) {
    // 		$first = $this->convertArrayOfStringKeysToDateString($first,$datesAsStringAndIndex);
    // 	}
    // 	if (!is_numeric(array_key_first($second))) {
    // 		$second = $this->convertArrayOfStringKeysToDateString($second,$datesAsStringAndIndex);
    // 	}

    // 	return array_values(array_unique(array_merge(array_keys($first), array_keys($second))));
    // }

    public function sumTwoArrayUntilIndex(array $first, array $second, int $limitDateAsIndex): array
    {

        // if (is_numeric(array_key_first($first))) {
        // 	$first = $this->convertArrayOfIndexKeysToDateStringAsIndexWithItsOriginalValue($first,$dateIndexWithDate);
        // }
        // if (is_numeric(array_key_first($second))) {
        // 	$second = $this->convertArrayOfIndexKeysToDateStringAsIndexWithItsOriginalValue($second,$dateIndexWithDate);
        // }
        $dates = array_values(array_unique(array_merge(array_keys($first), array_keys($second))));

        $result = [];
        // $limitDate = Carbon::make($limitDateAsString);
        foreach ($dates as $dateAsIndex) {
            // if (Carbon::make($date)->lessThanOrEqualTo($limitDate)){}
            if ($dateAsIndex <= $limitDateAsIndex) {
                $secondVal = $second[$dateAsIndex] ?? 0;
                $value = $first[$dateAsIndex] ?? 0;
                $result[$dateAsIndex] = $value + $secondVal;
            } else {
                $result[$dateAsIndex] = 0;
            }
        }

        return $result;
    }

    public function getStudyDateFormatted(array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate)
    {
        $dateWithMonthNumber = App('dateWithMonthNumber');
        $studyDurationPerYear = $this->getStudyDurationPerYear($datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber, true, true, false);

        return $this->getOnlyDatesOfActiveStudy($studyDurationPerYear, $dateIndexWithDate);
    }

    public function calculateWorkingCapitalInjection(array $accumulatedNetCash, array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate, $debug = false)
    {

        $workingCapital = [];

        $accumulatedNetCashPerYear = $this->arrayPerYear($accumulatedNetCash, $datesIndexWithYearIndex, $dateIndexWithDate);
        $accumulatedNetCashPerYear = getMinAtEveryIndex($accumulatedNetCashPerYear);

        $accumulatedNetCashPerYear = eachIndexMinusPreviousIfNegative($accumulatedNetCashPerYear, $debug);
        foreach ($accumulatedNetCashPerYear as $yearIndex => $newValue) {

            $workingCapitalDate = $this->getFirstDateIndexInYearIndex($datesAsStringAndIndex, $yearIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate);
            if (! is_null($workingCapitalDate)) {
                $workingCapital[$workingCapitalDate] = $newValue;
            }
        }

        return $workingCapital;
    }

    public function getFirstDateIndexInYearIndex(array $datesAsStringAndIndex, int $yearIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate)
    {

        $studyDates = $this->getStudyDateFormatted($datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate);
        foreach ($studyDates as $studyDateAsString => $studyDateAsIndex) {
            $currentYearIndex = $datesIndexWithYearIndex[$studyDateAsIndex];
            if ($currentYearIndex == $yearIndex) {
                return $studyDateAsIndex;
            }
        }

        return null;
    }

    public function arrayPerYear(array $keyAndValue, array $datesIndexWithYearIndex, array $dateIndexWithDate)
    {
        $result = [];
        foreach ($keyAndValue as $dateAsIndex => $value) {
            $fullDate = $dateIndexWithDate[$dateAsIndex];
            $yearAsIndex = $datesIndexWithYearIndex[$dateAsIndex];
            $result[$yearAsIndex][$fullDate] = $value;
        }

        return $result;
    }

    protected function getMonthIndexFromMonthNumberAndYearIndex($monthNumber, $yearIndex)
    {
        $datesAsStringAndIndex = $this->getDatesAsStringAndIndex();

        $datesIndexWithYearIndex = App('datesIndexWithYearIndex');
        $yearIndexWithYear = App('yearIndexWithYear');
        $dateIndexWithDate = App('dateIndexWithDate');
        $dateWithMonthNumber = App('dateWithMonthNumber');
        $yearsWithItsMonths = $this->getOperationDurationPerYear($datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber, true, false)[$yearIndex];
        $currentLoop = 1;
        $monthNumber = (int) $monthNumber;
        foreach ($yearsWithItsMonths as $currentMonthIndex => $trueOrFalse) {
            if ($currentLoop == $monthNumber) {
                return $currentMonthIndex;
            }
            $currentLoop++;
        }

    }

    public function calculatePropertyTaxes(array $propertyAssetsForBuilding): array
    {
        $initialTotalGross = $propertyAssetsForBuilding['initial_total_gross'] ?? [];

        if (! count($initialTotalGross)) {
            return [];
        }

        $operationStartDateAsIndex = $this->getOperationStartDateAsIndex();
        $dateIndexWithMonthNumber = app('dateIndexWithMonthNumber');
        $dateIndexWithYearIndex = app('datesIndexWithYearIndex');
        $operationStartDateMonth = $dateIndexWithMonthNumber[$operationStartDateAsIndex];
        $operationStartDateYearAsIndex = $dateIndexWithYearIndex[$operationStartDateAsIndex];
        $propertyTaxesDatesAndValue = [];

        foreach ($initialTotalGross as $dateAsIndex => $initialTotalGrossValue) {
            $currentMonth = $dateIndexWithMonthNumber[$dateAsIndex];
            $currentYearAsIndex = $dateIndexWithYearIndex[$dateAsIndex];
            if ($currentMonth == $operationStartDateMonth && $currentYearAsIndex >= $operationStartDateYearAsIndex) {
                $propertyTaxesDatesAndValue[$dateAsIndex] = $initialTotalGrossValue;
            }
        }

        $propertyTaxesExpense = $this->departmentExpenses->where('section_name', other_property_fixed_expenses)
            ->where('model_name', 'property')->whereIn('name', ['Property Taxes', __('Property Taxes')])->first();
        if (! $propertyTaxesExpense) {
            return [];
        }
        $propertyTaxesRate = $propertyTaxesExpense->getPercentageFromFixedAssets() / 100;
        $escalationRate = $propertyTaxesExpense->getGuestAnnualEscalationRate() / 100;

        $isFirstLoop = true;
        $propertyTaxesExpenses = [];
        foreach ($propertyTaxesDatesAndValue as $dateAsIndex => $initialTotalGrossValue) {
            if ($isFirstLoop) {
                $propertyTaxesExpenses[$dateAsIndex] = $initialTotalGrossValue * $propertyTaxesRate;
                $isFirstLoop = false;
            } else {
                $propertyTaxesExpenses[$dateAsIndex] = $initialTotalGrossValue * $propertyTaxesRate * (1 + $escalationRate);
            }
        }

        $monthlyPropertyTaxesExpenses = [];
        $propertyTaxesPayments = [];
        // $lastStudyYear = explode('-', $this->getStudyEndDateAsIndex())[2];
        $studyEndDateAsIndex = $this->getStudyEndDateAsIndex();
        $lastStudyYearAsIndex = $dateIndexWithYearIndex[$studyEndDateAsIndex];
        foreach ($propertyTaxesExpenses as $dateAsIndex => $propertyTaxesExpenseValue) {
            $paymentMonth = $propertyTaxesExpense->getPaymentMonth();
            $paymentYearAsIndex = $dateIndexWithYearIndex[$dateAsIndex] + 1;
            $paymentMonthAsIndex = $this->getMonthIndexFromMonthNumberAndYearIndex($paymentMonth, $paymentYearAsIndex);
            if ($paymentYearAsIndex <= $lastStudyYearAsIndex) {
                $propertyTaxesPayments[$paymentMonthAsIndex] = $propertyTaxesExpenseValue;
            }

            for ($i = 0; $i < 12; $i++) {
                $currentDateAsIndex = $dateAsIndex + $i;
                if ($currentDateAsIndex <= $studyEndDateAsIndex) {
                    $monthlyPropertyTaxesExpenses[$currentDateAsIndex] = $propertyTaxesExpenseValue / 12;
                }
            }
        }

        return [
            'propertyTaxesExpenses' => $propertyTaxesExpenses,
            'monthlyPropertyTaxesExpenses' => $monthlyPropertyTaxesExpenses,
            'payments' => $propertyTaxesPayments,
        ];
    }

    public function calculatePropertyInsurance(array $studyDates, array $propertyAssetsForBuilding, array $propertyAssetsForFFE, array $totalOfFFEItemForFFE): array
    {

        $propertyInsuranceDatesAndValue = [];
        $operationStartDateAsIndex = $this->getOperationStartDateAsIndex();
        $dateIndexWithMonthNumber = app('dateIndexWithMonthNumber');
        $dateIndexWithYearIndex = app('datesIndexWithYearIndex');
        $operationStartDateMonth = $dateIndexWithMonthNumber[$operationStartDateAsIndex];
        $operationStartDateYearAsIndex = $dateIndexWithYearIndex[$operationStartDateAsIndex];
        foreach ($studyDates as $dateAsIndex) {
            $currentMonth = $dateIndexWithMonthNumber[$dateAsIndex];
            $currentYearAsIndex = $dateIndexWithYearIndex[$dateAsIndex];

            if ($currentMonth == $operationStartDateMonth && $currentYearAsIndex >= $operationStartDateYearAsIndex) {
                $initialTotalGrossForPropertyAssets = $propertyAssetsForBuilding['initial_total_gross'][$dateAsIndex] ?? 0;
                $initialTotalGrossForFFE = $propertyAssetsForFFE['initial_total_gross'][$dateAsIndex] ?? 0;
                $initialTotalGrossForFFEItems = $totalOfFFEItemForFFE['initial_total_gross'][$dateAsIndex] ?? 0;
                $propertyInsuranceDatesAndValue[$dateAsIndex] = $initialTotalGrossForPropertyAssets + $initialTotalGrossForFFE + $initialTotalGrossForFFEItems;
            }
        }

        $propertyInsuranceExpense = $this->departmentExpenses->where('section_name', other_property_fixed_expenses)
            ->where('model_name', 'property')
            ->whereIn('name', ['Property Insurance', __('Property Insurance')])
            ->first();
        if (! $propertyInsuranceExpense) {
            return [];
        }
        $propertyInsuranceRate = $propertyInsuranceExpense->getPercentageFromFixedAssets() / 100;
        $escalationRate = $propertyInsuranceExpense->getGuestAnnualEscalationRate() / 100;
        $isFirstLoop = true;
        $propertyInsuranceExpenses = [];
        foreach ($propertyInsuranceDatesAndValue as $dateAsIndex => $initialTotalGrossValue) {
            if ($isFirstLoop) {
                $propertyInsuranceExpenses[$dateAsIndex] = $initialTotalGrossValue * $propertyInsuranceRate;
                $isFirstLoop = false;
            } else {
                $propertyInsuranceExpenses[$dateAsIndex] = $initialTotalGrossValue * $propertyInsuranceRate * (1 + $escalationRate);
            }
        }
        $monthlyPropertyInsuranceExpenses = [];
        $propertyInsurancePayments = [];
        $studyEndDateAsIndex = $this->getStudyEndDateAsIndex();
        $lastStudyYearAsIndex = $dateIndexWithYearIndex[$studyEndDateAsIndex];
        foreach ($propertyInsuranceExpenses as $dateAsIndex => $propertyInsuranceExpenseValue) {
            // $paymentYear = explode('-', $dateAsString)[2];
            // $paymentMonth = explode('-', $dateAsString)[1];
            // $paymentDay = explode('-', $dateAsString)[0];
            $paymentYearAsIndex = $dateIndexWithYearIndex[$dateAsIndex] + 1;
            // $paymentMonthAsIndex= $this->getMonthIndexFromMonthNumberAndYearIndex($dateAsIndex,$paymentYearAsIndex);

            if ($paymentYearAsIndex <= $lastStudyYearAsIndex) {
                $propertyInsurancePayments[$dateAsIndex] = $propertyInsuranceExpenseValue;
            }

            for ($i = 0; $i < 12; $i++) {
                $currentDateAsIndex = $dateAsIndex + $i;
                if ($currentDateAsIndex <= $studyEndDateAsIndex) {
                    $monthlyPropertyInsuranceExpenses[$currentDateAsIndex] = $propertyInsuranceExpenseValue / 12;
                }
            }
        }

        return [
            'propertyInsuranceExpenses' => $propertyInsuranceExpenses,
            'monthlyPropertyInsuranceExpenses' => $monthlyPropertyInsuranceExpenses,
            'payments' => $propertyInsurancePayments,
        ];
    }

    public function calculateFreeCashFlowForEquity(array $reportItems)
    {
        // $reportItems must be after working capital calculations;
        $totalCashInReportExceptEquityInjectionAndLoanWithdrawal = $reportItems['cashInReport']['Total Cash In']['subItems'] ?? [];
        unset($totalCashInReportExceptEquityInjectionAndLoanWithdrawal['Equity Injection'], $totalCashInReportExceptEquityInjectionAndLoanWithdrawal['Loan Withdrawal']);

        $totalCashInReportExceptEquityInjectionAndLoanWithdrawal = getTotalOfArraysOf2Depth($totalCashInReportExceptEquityInjectionAndLoanWithdrawal);
        $loanWithdrawals = $reportItems['cashInReport']['Total Cash In']['subItems']['Loan Withdrawal'];
        if (isset($loanWithdrawals['subItems'])) {
            unset($loanWithdrawals['subItems']);
        }
        $acquisitionAndDevelopment = $reportItems['cashOutReport']['Total Cash Out Report']['subItems']['Acquisition And Development Payment'] ?? [];
        unset($acquisitionAndDevelopment['subItems']);

        $reportItems['cashOutReport']['Total Cash Out Report']['subItems']['Acquisition And Development Payment'] = HArr::subtractAtDates([$acquisitionAndDevelopment, $loanWithdrawals], array_keys($acquisitionAndDevelopment));
        $totalCashOutReport = getTotalOfArraysOf2Depth($reportItems['cashOutReport']['Total Cash Out Report']['subItems'] ?? []);

        // $totalCashOutReport = removeKeyFromArray($reportItems['cashOutReport']['Total Cash Out Report']??[], 'subItems');
        return subtractTwoArray($totalCashInReportExceptEquityInjectionAndLoanWithdrawal, $totalCashOutReport);
    }

    public function calculateFreeCashFlowForFirm(array $reportItems)
    {
        // $reportItems must be after working capital calculations;
        $totalCashInReportExceptEquityInjectionAndLoanWithdrawal = $reportItems['cashInReport']['Total Cash In']['subItems'] ?? [];
        unset($totalCashInReportExceptEquityInjectionAndLoanWithdrawal['Equity Injection'], $totalCashInReportExceptEquityInjectionAndLoanWithdrawal['Loan Withdrawal']);

        $totalCashInReportExceptEquityInjectionAndLoanWithdrawal = getTotalOfArraysOf2Depth($totalCashInReportExceptEquityInjectionAndLoanWithdrawal);

        $totalCashOutReportExceptEquityInjectionAndLoanWithdrawal = $reportItems['cashOutReport']['Total Cash Out Report']['subItems'] ?? [];
        unset($totalCashOutReportExceptEquityInjectionAndLoanWithdrawal['Loan Installments Payment']);

        $totalCashOutReportExceptEquityInjectionAndLoanWithdrawal = getTotalOfArraysOf2Depth($totalCashOutReportExceptEquityInjectionAndLoanWithdrawal);

        return subtractTwoArray($totalCashInReportExceptEquityInjectionAndLoanWithdrawal, $totalCashOutReportExceptEquityInjectionAndLoanWithdrawal);
    }

    public function getExchangeRates()
    {
        $exchangeRates = convertJsonToArray($this->exchange_rates);

        return arrayToValueIndexes($exchangeRates);
    }

    public function getExchangeRateAtYear(int $year)
    {
        $exchangeRates = convertJsonToArray($this->exchange_rates);
        $exchangeRateAtYears = arrayToValueIndexes($exchangeRates);

        return $exchangeRateAtYears && isset($exchangeRateAtYears[$year]) ? $exchangeRateAtYears[$year] : $this->getExchangeRate();
    }

    public function calculatePricesAfterExchangeRate(array $inflatedPrices, array $exchangeRates, string $relationName, array $datesIndexWithYearIndex)
    {
        $result = [];

        $firstModel = $this->{$relationName}->first();
        $firstModelCurrency = $firstModel ? $firstModel->getChosenCurrency() : $this->getMainFunctionalCurrency();

        foreach ($inflatedPrices as $identifier => $inflatedPriceArray) {
            $identifierColumn = ($firstModel::class)::getIdentifierColumnName();
            $model = $this->rooms->where($identifierColumn, $identifier)->first();
            foreach ($inflatedPriceArray as $dateAsIndex => $value) {
                $currentModelCurrencyId = $model ? $model->getChosenCurrency() : $firstModelCurrency;
                if ($currentModelCurrencyId != $this->getAdditionalCurrency()) {
                    $result[$identifier][$dateAsIndex] = $value;

                    continue;
                }
                $yearIndex = $datesIndexWithYearIndex[$dateAsIndex];
                $exchangeRateAtYear = $exchangeRates[$yearIndex] ?? 1;
                $result[$identifier][$dateAsIndex] = $value * $exchangeRateAtYear;
            }
        }

        return $result;
    }

    public function calculateRetainedEarning(array $netProfit, $interval)
    {
        $retainedEarning = [];
        $minusNumber = [
            'monthly' => 1,
            'quarterly' => 3,
            'semi-annually' => 6,
            'annually' => 12,
        ][$interval];
        foreach ($netProfit as $dateAsIndex => $value) {
            $previousDate = $dateAsIndex - $minusNumber;
            $previousProfit = $netProfit[$previousDate] ?? 0;
            $previousRetainedEarning = $retainedEarning[$previousDate] ?? 0;

            if ($interval == 'annually') {
            }

            if ($previousDate < 0) {
                $retainedEarning[$dateAsIndex] = 0;
            } else {
                $retainedEarning[$dateAsIndex] = $previousRetainedEarning + $previousProfit;
            }
        }

        return $retainedEarning;
    }

    public function calculateLandFixedAssets(array $studyDates, array $propertyLandCapitalizedInterest, array $landLoanCapitalizedInterest): array
    {
        $propertyLandCapitalizedInterestAsIndexDates = [];
        $landLoanCapitalizedInterestAsIndexDates = [];
        $landFixedAssets = [];
        $acquisition = $this->getAcquisition();
        $propertyAcquisition = $this->getPropertyAcquisition();
        $acquisitionLandPurchaseDateAndAmount = [];
        $propertyPurchaseDateFormatted = null;
        $propertyLandAmount = 0;
        $propertyPurchaseDateAsIndex = null;
        $propertyLandPurchaseDateAndAmount = [];
        if ($acquisition) {
            $acquisitionPurchaseDateFormatted = $acquisition->getLandPurchaseDateFormatted();
            $acquisitionLandAmount = $acquisition->getTotalPurchaseCost();
            $acquisitionPurchaseDateAsIndex = $studyDates[$acquisitionPurchaseDateFormatted];
            $acquisitionLandPurchaseDateAndAmount = [
                $acquisitionPurchaseDateAsIndex => $acquisitionLandAmount,
            ];
        }
        if ($propertyAcquisition) {
            $propertyPurchaseDateAsIndex = $propertyAcquisition->getPropertyPurchaseDateAsIndex();
            $propertyLand = $propertyAcquisition->getLandProperty();
            $propertyLandAmount = $propertyLand->getItemAmount();
            $propertyLandPurchaseDateAndAmount = [
                $propertyPurchaseDateAsIndex => $propertyLandAmount,
            ];

        }
        foreach ($studyDates as $dateAsString => $dateAsIndex) {
            $propertyLandCapitalizedInterestAtDate = $propertyLandCapitalizedInterest[$dateAsIndex] ?? 0;
            $landLoanCapitalizedInterestAtDate = $landLoanCapitalizedInterest[$dateAsIndex] ?? 0;
            $propertyLandCapitalizedInterestAsIndexDates[$dateAsIndex] = $propertyLandCapitalizedInterestAtDate;
            $landLoanCapitalizedInterestAsIndexDates[$dateAsIndex] = $landLoanCapitalizedInterestAtDate;

            if (isset($propertyLandPurchaseDateAndAmount[$dateAsIndex], $acquisitionLandPurchaseDateAndAmount[$dateAsIndex])) {
                $landFixedAssets[$dateAsIndex] = $propertyLandPurchaseDateAndAmount[$dateAsIndex] + $acquisitionLandPurchaseDateAndAmount[$dateAsIndex];
            } elseif (isset($acquisitionLandPurchaseDateAndAmount[$dateAsIndex])) {
                $landFixedAssets[$dateAsIndex] = $acquisitionLandPurchaseDateAndAmount[$dateAsIndex];
            } elseif (isset($propertyLandPurchaseDateAndAmount[$dateAsIndex])) {
                $landFixedAssets[$dateAsIndex] = $propertyLandPurchaseDateAndAmount[$dateAsIndex];
            } else {
                $landFixedAssets[$dateAsIndex] = 0;
            }
        }
        $accumulatedLandFixed = HArr::accumulateArray(HArr::sumAtDates([$landFixedAssets, $propertyLandCapitalizedInterestAsIndexDates, $landLoanCapitalizedInterestAsIndexDates], $studyDates));
        if ($propertyPurchaseDateFormatted) {
            $landFixedAssets[$propertyPurchaseDateAsIndex] = $landFixedAssets[$propertyPurchaseDateAsIndex] - $propertyLandAmount;
        }

        return [
            'landFixed' => $landFixedAssets,
            'landFixedWithoutProperty' => $acquisitionLandPurchaseDateAndAmount,
            'AccumulatedLandFixed' => $accumulatedLandFixed,
        ];
    }

    public function getPropertyAcquisitionDatesAndAmounts(array $studyDates)
    {
        $datesAndAmounts = [];
        $propertyAcquisition = $this->getPropertyAcquisition();
        if (! $propertyAcquisition) {
            return [];
        }

        $purchaseDateFormatted = $propertyAcquisition->getPropertyPurchaseDateFormatted();
        $totalPurchaseCost = $propertyAcquisition->getTotalPurchaseCost();

        foreach ($studyDates as $dateAsString => $dateAsIndex) {
            if ($dateAsString == $purchaseDateFormatted) {
                $datesAndAmounts[$dateAsIndex] = $totalPurchaseCost;
            } else {
                $datesAndAmounts[$dateAsIndex] = 0;
            }
        }

        return $datesAndAmounts;
    }

    public function replaceDepartmentExpenseIdWithName(array $items)
    {

        $result = [];
        foreach ($items as $id => $items) {
            if (is_numeric($id)) {
                $result[$this->departmentExpenses->where('id', $id)->first()->getName()] = $items;
            } else {
                $result[$id] = $items;
            }
        }

        return $result;
    }

    public function getDatesIndexesHelper()
    {
        $studyDates = $this->getStudyDates();
        $studyStartDate = Arr::first($studyDates);
        $studyEndDate = Arr::last($studyDates);
        $studyStartDate = $studyStartDate ? LegacyCarbon::make($studyStartDate)->format('m/d/Y') : null;
        $studyEndDate = $studyEndDate ? LegacyCarbon::make($studyEndDate)->format('m/d/Y') : null;

        return $this->datesAndIndexesHelpers($studyDates);
    }

    public function datesAndIndexesHelpers(array $studyDates)
    {
        $firstLoop = true;
        $baseYear = null;
        $datesIndexWithYearIndex = [];
        $yearIndexWithYear = [];
        $dateIndexWithDate = [];
        $dateIndexWithMonthNumber = [];
        $dateWithMonthNumber = [];
        $dateWithDateIndex = [];

        foreach ($studyDates as $dateIndex => $dateAsString) {
            $year = explode('-',$dateAsString)[2];
            $montNumber = explode('-',$dateAsString)[1];
            if ($firstLoop) {
                $baseYear = $year;
                $firstLoop = false;
            }
            $yearIndex = $year - $baseYear;
            $datesIndexWithYearIndex[$dateIndex] = $yearIndex;
            $yearIndexWithYear[$yearIndex] = $year;
            $dateIndexWithDate[$dateIndex] = $dateAsString;
            $dateIndexWithMonthNumber[$dateIndex] = $montNumber;
            $dateWithMonthNumber[$dateAsString] = $montNumber;
            $dateWithDateIndex[$dateAsString] = $dateIndex;

        }

        return [
            'datesIndexWithYearIndex' => $datesIndexWithYearIndex,
            'yearIndexWithYear' => $yearIndexWithYear,
            'dateIndexWithDate' => $dateIndexWithDate,
            'dateIndexWithMonthNumber' => $dateIndexWithMonthNumber,
            'dateWithMonthNumber' => $dateWithMonthNumber,
            'dateWithDateIndex' => $dateWithDateIndex,
        ];

        return $datesIndexWithYearIndex;
    }

    public function studyStartDateToOperationStartDates(): array
    {
        $result = [];
        $studyStartDate = LegacyCarbon::make($this->getStudyStartDateFormatted());
        $dateBeforeOperationStartDate = $this->getOperationStartMonth() - 1;
        $endDate = $studyStartDate->addMonths($dateBeforeOperationStartDate);
        $dates = ((new Date)->generateDatesBetweenTwoDates(LegacyCarbon::make($this->getStudyStartDateFormatted()),$endDate));
        foreach ($dates as $dateAsString) {
            $result[$dateAsString] = $this->getDateIndexFromDate($dateAsString);
        }

        return $result;
    }

    public function convertDateStringToDateIndex(string $dateAsString): int
    {
        return app('dateWithDateIndex')[$dateAsString];
    }
}
