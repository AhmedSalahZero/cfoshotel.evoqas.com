<?php

namespace App\Services\Investment;

use App\Models\Acquisition;
use App\Models\HospitalitySector;
use App\Services\Revenue\RevenueParts;
use App\Support\CollectionTerms;
use App\Support\Investment;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — LandAcquisitionService (the land and the construction of a study)
//  Location: app/Services/Investment/LandAcquisitionService.php
//
//  One record in `acquisitions` per study, as in the old application (HospitalitySectorController@viewLandAcquisitionCosts / storeLandAcquisitionCosts):
//    · the land: purchase date, cost, contingency, payment method and terms, equity funding, and its loan (section "land")
//    · the hard construction cost: cost, contingency, start, duration, end, execution method, payment terms (down payment, two balances, due days),
//      equity funding, and its loan (section "hard_cost_construction")
//    · the soft construction cost: the same without funding and loan (it is funded by equity)
//  Dates are month indexes of the study. The end of a construction is its start plus its duration and must not be after the opening month.
//  The second balance rate is what is left of 100 % after the down payment and the first balance.
//  The calculation reads this record as it is saved (HospitalitySector::getAcquisition()).
// ══════════════════════════════════════════════════════════════════

class LandAcquisitionService
{
    public const KINDS = ['hard', 'soft'];

    public function __construct(private readonly LoanFormService $loans, private readonly InvestmentDates $dates) {}

    public function record(HospitalitySector $study): ?Acquisition
    {
        return Acquisition::query()->where('hospitality_sector_id', $study->id)->orderBy('id')->first();
    }

    public function present(HospitalitySector $study): array
    {
        $record = $this->record($study);
        $record?->unsetRelation('loans');
        $start = $this->dates->developmentStart($study);
        $duration = $this->dates->developmentDuration($study);

        return [
            'months' => $this->dates->months($study),
            'opening' => $this->dates->opening($study),
            'endLabels' => $this->endLabels($study),
            'lists' => [
                'methods' => Investment::PAYMENT_METHODS, 'intervals' => Investment::INTERVALS, 'collectionDue' => Investment::COLLECTION_DUE, 'dueDays' => Investment::DUE_DAYS,
                'hardExecution' => Investment::HARD_EXECUTION, 'softExecution' => Investment::SOFT_EXECUTION,
            ],
            'form' => [
                'land' => PurchaseBlock::present($record, 'land', $this->dates->studyStart($study)),
                'hard' => $this->constructionPresent($record, 'hard', $start, $duration) + ['equity' => $this->equity($record?->hard_equity_funding)],
                'soft' => $this->constructionPresent($record, 'soft', $start, $duration),
                'loans' => [
                    LAND_LOAN => $this->loans->present($record?->getLoanForSection(LAND_LOAN)),
                    HARD_COST_CONSTRUCTION => $this->loans->present($record?->getLoanForSection(HARD_COST_CONSTRUCTION)),
                ],
            ],
        ];
    }

    public function save(HospitalitySector $study, array $data): void
    {
        DB::transaction(function () use ($study, $data) {
            $columns = ['hospitality_sector_id' => $study->id, 'company_id' => $study->company_id, 'has_land_section' => 1]
                + PurchaseBlock::columns($data['land'], 'land')
                + $this->constructionColumns($data['hard'], 'hard') + ['hard_equity_funding' => RevenueParts::amount($data['hard']['equity'])]
                + $this->constructionColumns($data['soft'], 'soft');

            $record = $this->record($study);
            if ($record) {
                $record->update($columns);
            } else {
                $record = Acquisition::query()->create($columns);
            }

            $this->loans->save($record, LAND_LOAN, $data['loans'][LAND_LOAN], $study);
            $this->loans->save($record, HARD_COST_CONSTRUCTION, $data['loans'][HARD_COST_CONSTRUCTION], $study);
        });
    }

    /** The label of every month a construction can end in (the months of the study, so also the ones after the opening). */
    private function endLabels(HospitalitySector $study): array
    {
        $out = [];
        foreach (array_keys($study->getStudyDates()) as $index) {
            $out[(int) $index] = $this->dates->label($study, (int) $index);
        }

        return $out;
    }

    private function constructionPresent(?Acquisition $record, string $kind, int $defaultStart, int $defaultDuration): array
    {
        $number = fn (string $column) => CollectionTerms::number($record?->{$column} ?: 0);
        $start = $record?->{"{$kind}_construction_start_date"};
        $duration = $record?->{"{$kind}_construction_duration"};
        $method = $record?->{"{$kind}_execution_method"};
        $methods = $kind === 'hard' ? Investment::HARD_EXECUTION : Investment::SOFT_EXECUTION;

        return [
            'cost' => $number("{$kind}_construction_cost"),
            'contingency' => $number("{$kind}_construction_contingency_rate"),
            'start' => $start === null || $start === '' ? $defaultStart : (int) $start,
            'duration' => $duration === null || $duration === '' ? $defaultDuration : (int) $duration,
            'method' => in_array($method, $methods, true) ? $method : $methods[0],
            'down' => $number("{$kind}_down_payment"),
            'one' => $number("{$kind}_balance_rate_one"),
            'dueOne' => (int) ($record?->{"{$kind}_due_one"} ?: 0),
            'dueTwo' => (int) ($record?->{"{$kind}_due_two"} ?: 0),
        ];
    }

    private function constructionColumns(array $block, string $kind): array
    {
        $down = (float) ($block['down'] ?? 0);
        $one = (float) ($block['one'] ?? 0);

        return [
            "has_{$kind}_construction_cost_section" => 1,
            "{$kind}_construction_cost" => RevenueParts::amount($block['cost'] ?? 0),
            "{$kind}_construction_contingency_rate" => RevenueParts::amount($block['contingency'] ?? 0),
            "{$kind}_construction_start_date" => (int) $block['start'],
            "{$kind}_construction_duration" => (string) (int) $block['duration'],
            "{$kind}_construction_end_date" => $this->dates->end((int) $block['start'], (int) $block['duration']),
            "{$kind}_execution_method" => (string) $block['method'],
            "{$kind}_down_payment" => RevenueParts::amount($down),
            "{$kind}_balance_rate_one" => RevenueParts::amount($one),
            "{$kind}_balance_rate_two" => RevenueParts::amount(100 - $down - $one),
            "{$kind}_due_one" => (string) (int) $block['dueOne'],
            "{$kind}_due_two" => (string) (int) $block['dueTwo'],
        ];
    }

    private function equity(mixed $value): int|float
    {
        return $value === null || $value === '' ? 100 : CollectionTerms::number($value);
    }
}
