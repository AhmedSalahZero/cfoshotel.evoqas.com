<?php

namespace App\Services\Investment;

use App\Models\Currency;
use App\Models\FFE;
use App\Models\FFEItem;
use App\Models\HospitalitySector;
use App\Services\Revenue\RevenueParts;
use App\Support\CollectionTerms;
use App\Support\Investment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — FfeCostService (furniture, fixtures and equipment)
//  Location: app/Services/Investment/FfeCostService.php
//
//  As in the old application (viewFFECost / storeFFECost):
//    · up to ten items in `ffe_items` (model "ffe", section "ffe_cost"): name, currency, item cost, contingency %, total cost (cost + contingency),
//      depreciation duration (3 - 25 years) and the replacement cost rate (% of cost) with its interval (1 - 5 years)
//    · one record in `ffe` per study: start, duration, end, execution method, payment terms (down payment, two balances, due days) and equity
//      funding, and its loan (section "ffe_cost")
//  An item with a name is kept, an item whose name is emptied is deleted, and an item keeps its id when it is saved again.
//  The end is the start plus the duration and must not be after the opening month. The second balance is what is left of 100 %.
// ══════════════════════════════════════════════════════════════════

class FfeCostService
{
    public function __construct(private readonly LoanFormService $loans, private readonly InvestmentDates $dates) {}

    public function record(HospitalitySector $study): ?FFE
    {
        return FFE::query()->where('hospitality_sector_id', $study->id)->orderBy('id')->first();
    }

    public function present(HospitalitySector $study): array
    {
        $record = $this->record($study);
        $record?->unsetRelation('loans');
        $main = (int) $study->main_functional_currency;
        $items = $this->items($study)->values();
        $rows = [];
        for ($i = 0; $i < Investment::FFE_ROWS; $i++) {
            /** @var FFEItem|null $item */
            $item = $items->get($i);
            $rows[] = [
                'id' => $item?->id,
                'name' => (string) $item?->getName(),
                'currency' => (int) ($item?->getCurrencyName() ?: $main),
                'cost' => CollectionTerms::number($item?->getItemCost() ?: 0),
                'contingency' => CollectionTerms::number($item?->getContingencyRate() ?: 0),
                'depreciation' => (int) ($item?->getDepreciationDuration() ?: 3),
                'replacementRate' => CollectionTerms::number($item?->getReplacementCostRate() ?: 0),
                'replacementInterval' => (int) ($item?->getReplacementInterval() ?: 1),
            ];
        }

        $start = $record?->start_date;
        $duration = $record?->duration;
        $method = $record?->execution_method;
        $equity = $record?->ffe_equity_funding;

        return [
            'currencies' => $this->currencies($study),
            'months' => $this->dates->months($study),
            'opening' => $this->dates->opening($study),
            'endLabels' => $this->endLabels($study),
            'lists' => [
                'dueDays' => Investment::DUE_DAYS, 'execution' => Investment::FFE_EXECUTION, 'depreciation' => Investment::depreciationYears(), 'replacement' => Investment::replacementYears(),
            ],
            'form' => [
                'rows' => $rows,
                'dates' => [
                    'start' => $start === null || $start === '' ? $this->dates->developmentStart($study) : (int) $start,
                    'duration' => $duration === null || $duration === '' ? $this->dates->developmentDuration($study) : (int) $duration,
                    'method' => in_array($method, Investment::FFE_EXECUTION, true) ? $method : Investment::FFE_EXECUTION[0],
                ],
                'terms' => [
                    'down' => CollectionTerms::number($record?->down_payment ?: 0),
                    'one' => CollectionTerms::number($record?->balance_rate_one ?: 0),
                    'dueOne' => (int) ($record?->due_one ?: 0),
                    'dueTwo' => (int) ($record?->due_two ?: 0),
                ],
                'equity' => $equity === null || $equity === '' ? 100 : CollectionTerms::number($equity),
                'loans' => [FFE_COST => $this->loans->present($record?->getLoanForSection(FFE_COST))],
            ],
        ];
    }

    public function save(HospitalitySector $study, array $data): void
    {
        DB::transaction(function () use ($study, $data) {
            $down = (float) $data['terms']['down'];
            $one = (float) $data['terms']['one'];
            $columns = [
                'hospitality_sector_id' => $study->id, 'company_id' => $study->company_id,
                'start_date' => (int) $data['dates']['start'],
                'duration' => (string) (int) $data['dates']['duration'],
                'end_date' => $this->dates->end((int) $data['dates']['start'], (int) $data['dates']['duration']),
                'execution_method' => (string) $data['dates']['method'],
                'down_payment' => RevenueParts::amount($down),
                'balance_rate_one' => RevenueParts::amount($one),
                'balance_rate_two' => RevenueParts::amount(100 - $down - $one),
                'due_one' => (string) (int) $data['terms']['dueOne'],
                'due_two' => (string) (int) $data['terms']['dueTwo'],
                'ffe_equity_funding' => RevenueParts::amount($data['equity']),
            ];

            $record = $this->record($study);
            if ($record) {
                $record->update($columns);
            } else {
                $record = FFE::query()->create($columns);
            }

            $this->loans->save($record, FFE_COST, $data['loans'][FFE_COST], $study);
            $this->saveItems($study, $record, (array) $data['rows']);
        });
    }

    private function saveItems(HospitalitySector $study, FFE $record, array $rows): void
    {
        $existing = $this->items($study)->keyBy('id');
        $kept = [];

        foreach (array_values($rows) as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $values = [
                'section_name' => FFE_COST, 'model_name' => Investment::FFE_MODEL, 'name' => $name, 'ffe_id' => $record->id,
                'hospitality_sector_id' => $study->id, 'company_id' => $study->company_id,
                'currency_name' => (string) (int) $row['currency'],
                'item_cost' => RevenueParts::amount($row['cost'] ?? 0),
                'contingency_rate' => RevenueParts::amount($row['contingency'] ?? 0),
                'depreciation_duration' => (string) (int) $row['depreciation'],
                'replacement_cost_rate' => RevenueParts::amount($row['replacementRate'] ?? 0),
                'replacement_interval' => (string) (int) $row['replacementInterval'],
            ];
            $current = isset($row['id']) ? $existing->get((int) $row['id']) : null;
            if ($current) {
                $current->update($values);
                $kept[] = $current->id;
            } else {
                $kept[] = FFEItem::query()->create($values)->id;
            }
        }

        $existing->keys()->diff($kept)->each(fn ($id) => $existing->get($id)->delete());
    }

    private function items(HospitalitySector $study): Collection
    {
        return FFEItem::query()->where('hospitality_sector_id', $study->id)->where('section_name', FFE_COST)->where('model_name', Investment::FFE_MODEL)->orderBy('id')->get();
    }

    private function currencies(HospitalitySector $study): array
    {
        $ids = array_filter([(int) $study->main_functional_currency, (int) $study->additional_currency]);

        return Currency::query()->whereIn('id', $ids)->orderBy('id')->get(['id', 'name'])
            ->map(fn ($currency) => ['id' => (int) $currency->id, 'name' => (string) $currency->name])->values()->all();
    }

    private function endLabels(HospitalitySector $study): array
    {
        $out = [];
        foreach (array_keys($study->getStudyDates()) as $index) {
            $out[(int) $index] = $this->dates->label($study, (int) $index);
        }

        return $out;
    }
}
