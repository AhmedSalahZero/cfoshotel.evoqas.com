<?php

namespace App\Services\Investment;

use App\Models\HospitalitySector;
use App\Models\PropertyAcquisition;
use App\Models\PropertyAcquisitionBreakDown;
use App\Services\Revenue\RevenueParts;
use App\Support\CollectionTerms;
use App\Support\Investment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — PropertyAcquisitionService (an existing property bought as a whole)
//  Location: app/Services/Investment/PropertyAcquisitionService.php
//
//  One record in `property_acquisitions` per study, as in the old application (viewPropertyAcquisitionCosts / storePropertyAcquisitionCosts):
//    · the purchase (date, cost, contingency, payment method and terms, equity funding) and its loan (section "property_loan")
//    · the breakdown of the purchase cost over land, building and furniture & equipment, in `property_acquisition_breakdowns`
//      (model "property", section "property_acquisition"): each line's share of the cost (%), its amount, and the years it is depreciated over
//      (not for the land). The shares must add up to 100 %. The amounts are worked out from the shares and the total purchase cost.
//    · the replacement (refurbishment) of the building and of the furniture & equipment: a rate of cost and an interval in years.
//  The old application deleted the breakdown and wrote it again on every save; so does this one.
// ══════════════════════════════════════════════════════════════════

class PropertyAcquisitionService
{
    public const SECTION = 'property_acquisition';

    public function __construct(private readonly LoanFormService $loans, private readonly InvestmentDates $dates) {}

    public function record(HospitalitySector $study): ?PropertyAcquisition
    {
        return PropertyAcquisition::query()->where('hospitality_sector_id', $study->id)->orderBy('id')->first();
    }

    public function present(HospitalitySector $study): array
    {
        $record = $this->record($study);
        $record?->unsetRelation('loans');
        $rows = $this->breakdown($study)->values();
        $lines = [];
        foreach (Investment::BREAKDOWN as $i => $line) {
            /** @var PropertyAcquisitionBreakDown|null $row */
            $row = $rows->get($i);
            $lines[] = [
                'name' => $line['name'],
                'percentage' => CollectionTerms::number($row?->getPropertyCostPercentage() ?: 0),
                'depreciation' => $line['depreciated'] ? (int) ($row?->depreciation_duration ?: 3) : 0,
            ];
        }

        return [
            'months' => $this->dates->months($study),
            'lists' => [
                'methods' => Investment::PAYMENT_METHODS, 'intervals' => Investment::INTERVALS, 'collectionDue' => Investment::COLLECTION_DUE,
                'depreciation' => Investment::depreciationYears(), 'buildingDepreciation' => Investment::depreciationYears(true), 'replacement' => Investment::replacementYears(),
            ],
            'form' => [
                'property' => PurchaseBlock::present($record, 'property', $this->dates->studyStart($study)),
                'breakdown' => $lines,
                'replacement' => [
                    'rate' => CollectionTerms::number($record?->replacement_cost_rate ?: 0),
                    'interval' => (int) ($record?->replacement_interval ?: 1),
                    'ffeRate' => CollectionTerms::number($record?->ffe_replacement_cost_rate ?: 0),
                    'ffeInterval' => (int) ($record?->ffe_replacement_interval ?: 1),
                ],
                'loans' => [PROPERTY_LOAN => $this->loans->present($record?->getLoanForSection(PROPERTY_LOAN))],
            ],
        ];
    }

    public function save(HospitalitySector $study, array $data): void
    {
        DB::transaction(function () use ($study, $data) {
            $columns = ['hospitality_sector_id' => $study->id, 'company_id' => $study->company_id, 'has_land_section' => 1]
                + PurchaseBlock::columns($data['property'], 'property')
                + [
                    'replacement_cost_name' => Investment::REPLACEMENT_BUILDING_NAME,
                    'replacement_cost_rate' => RevenueParts::amount($data['replacement']['rate']),
                    'replacement_interval' => (string) (int) $data['replacement']['interval'],
                    'ffe_replacement_cost_name' => Investment::REPLACEMENT_FFE_NAME,
                    'ffe_replacement_cost_rate' => RevenueParts::amount($data['replacement']['ffeRate']),
                    'ffe_replacement_interval' => (string) (int) $data['replacement']['ffeInterval'],
                ];

            $record = $this->record($study);
            if ($record) {
                $record->update($columns);
            } else {
                $record = PropertyAcquisition::query()->create($columns);
            }

            $this->loans->save($record, PROPERTY_LOAN, $data['loans'][PROPERTY_LOAN], $study);
            $this->saveBreakdown($study, $data['breakdown'], $this->total($data['property']));
        });
    }

    /** The total purchase cost: the cost plus its contingency. */
    public function total(array $property): float
    {
        return (float) $property['cost'] * (1 + (float) $property['contingency'] / 100);
    }

    private function saveBreakdown(HospitalitySector $study, array $lines, float $total): void
    {
        $this->breakdown($study)->each->delete();

        foreach (Investment::BREAKDOWN as $i => $line) {
            $share = (float) ($lines[$i]['percentage'] ?? 0);
            PropertyAcquisitionBreakDown::query()->create([
                'name' => $line['name'],
                'section_name' => self::SECTION,
                'model_name' => Investment::BREAKDOWN_MODEL,
                'property_cost_percentage' => RevenueParts::amount($share),
                'item_amount' => RevenueParts::amount($total * $share / 100),
                'depreciation_duration' => $line['depreciated'] ? (string) (int) ($lines[$i]['depreciation'] ?? 3) : null,
                'hospitality_sector_id' => $study->id,
                'company_id' => $study->company_id,
            ]);
        }
    }

    private function breakdown(HospitalitySector $study): Collection
    {
        return PropertyAcquisitionBreakDown::query()->where('hospitality_sector_id', $study->id)->where('section_name', self::SECTION)
            ->where('model_name', Investment::BREAKDOWN_MODEL)->orderBy('id')->get();
    }
}
