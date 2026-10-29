<?php

namespace App\Models\Concerns;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — The revenue part of the hotel calculation, on its own
//  Location: app/Models/Concerns/HospitalitySectorRevenueOnly.php
//
//  calculateRoomRevenueAndGuestCount() works out the whole hotel (revenue, expenses, start-up cost, loans, taxes, statements); on a
//  15-year study that takes about five seconds. The input pages only need the REVENUE figures (the reading aids under the yearly boxes,
//  the revenue row of the expense pages), and the revenue is worked out in the first part of that calculation.
//  This method is that first part, copied line for line, returning exactly the same figures under the same names.
//  KEEP IT IDENTICAL to the revenue part of calculateRoomRevenueAndGuestCount(): a test (RevenueResultsTest) compares the two.
//  A fault in the later parts (a loan, for example) no longer stops the revenue pages.
// ══════════════════════════════════════════════════════════════════

trait HospitalitySectorRevenueOnly
{
    public function calculateRevenueOnly(): array
    {
        $datesAndIndexesHelpers = $this->getDatesIndexesHelper();
        $datesIndexWithYearIndex = $datesAndIndexesHelpers['datesIndexWithYearIndex'];
        $yearIndexWithYear = $datesAndIndexesHelpers['yearIndexWithYear'];
        $dateIndexWithDate = $datesAndIndexesHelpers['dateIndexWithDate'];
        $dateIndexWithMonthNumber = $datesAndIndexesHelpers['dateIndexWithMonthNumber'];
        $dateWithMonthNumber = $datesAndIndexesHelpers['dateWithMonthNumber'];
        $dateWithDateIndex = $datesAndIndexesHelpers['dateWithDateIndex'];

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

        return [
            'totalRoomRevenueOfEachYear' => $totalRoomRevenueOfEachYear,
            'totalRoomsSoldNightsPerYear' => $totalRoomsSoldNightsPerYear,
            'totalMaxAvailableNightsPerYear' => $totalMaxAvailableNightsPerYear,
            'totalMaxPracticalAvailableNightsPerYear' => $totalMaxPracticalAvailableNightsPerYear,
            'annualGuestCountPerRoom' => $annualGuestCountPerRoom,
            'fAndBFacilityRevenue' => $fAndBFacilityRevenue,
            'casinoFacilityRevenue' => $casinoFacilityRevenue,
            'meetingFacilityRevenue' => $meetingFacilityRevenue,
            'otherRevenueFacilityRevenue' => $otherFacilityRevenue,
            'daysCountPerYear' => $daysCountPerYear,
            'annuallyTotalHotelRevenue' => $annuallyTotalHotelRevenue,
        ];
    }
}
