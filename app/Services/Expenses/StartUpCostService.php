<?php

namespace App\Services\Expenses;

use App\Models\Currency;
use App\Models\DepartmentExpense;
use App\Models\HospitalitySector;
use App\Services\Revenue\RevenueParts;
use App\Support\CollectionTerms;
use App\Support\ExpensePages;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StartUpCostService (the start-up costs and the pre-operating expenses of a study)
//  Location: app/Services/Expenses/StartUpCostService.php
//
//  Kept in department_expenses (model_name "start") like every expense of the old application:
//    start_up_cost        up to three lines: currency, cost amount, the month it is paid, cash payment % (the rest is deferred) and the due days
//    pre_operating_expense up to three lines: currency and an amount for every month from the study start to the month before the opening
//                         (the amounts are kept by date index in manpower_payload, as the old application did)
//  A line with a name is kept, a line whose name is emptied is deleted, and a line keeps its id when it is saved again.
//  The calculation reads these rows as they are saved (App\Calculations\StartUpCostService).
// ══════════════════════════════════════════════════════════════════

class StartUpCostService
{
    public const MODEL = 'start';
    public const LINES = 3;

    public function present(HospitalitySector $study): array
    {
        $main = (int) $study->main_functional_currency;
        $months = $this->openingMonths($study);
        $dates = $this->preOperatingDates($study);
        $startUp = $this->rows($study, ExpensePages::START_UP)->values();
        $pre = $this->rows($study, ExpensePages::PRE_OPERATING)->values();
        $firstMonth = $months[0]['index'] ?? null;

        $startRows = [];
        $preRows = [];
        for ($i = 0; $i < self::LINES; $i++) {
            /** @var DepartmentExpense|null $e */
            $e = $startUp->get($i);
            $startRows[] = [
                'id' => $e?->id, 'name' => (string) $e?->getName(),
                'currency' => (int) ($e?->getChosenCurrency() ?: $main),
                'cost' => CollectionTerms::number($e?->getStartUpCost() ?: 0),
                'date' => (int) ($e && $e->getDateAsIndex() !== null ? $e->getDateAsIndex() : $firstMonth),
                'cash' => CollectionTerms::number($e ? $e->getCashPayment() : 100),
                'dueDays' => (int) ($e?->getDueDays() ?: 0),
            ];

            $p = $pre->get($i);
            $amounts = [];
            foreach ($dates as $date) {
                $amounts[$date['index']] = $p ? CollectionTerms::number($p->getManpowerPayloadAtDate((string) $date['index'])) : 0;
            }
            $preRows[] = [
                'id' => $p?->id, 'name' => (string) $p?->getName(),
                'currency' => (int) ($p?->getChosenCurrency() ?: $main),
                'amounts' => RevenueParts::map($amounts),
            ];
        }

        return [
            'currencies' => $this->currencies($study),
            'months' => $months,
            'dates' => $dates,
            'days' => ExpensePages::DAYS,
            'form' => ['sections' => [ExpensePages::START_UP => $startRows, ExpensePages::PRE_OPERATING => $preRows]],
        ];
    }

    /** The months a start-up cost can be paid in: from the first month of the study to the opening month. */
    public function openingMonths(HospitalitySector $study): array
    {
        $first = (int) $study->getStudyStartDateAsIndex();
        $last = $study->getOperationStartDateAsIndex();
        $out = [];
        foreach ($study->getStudyDates() as $index => $date) {
            if ((int) $index >= $first && ($last === null || (int) $index <= $last)) {
                $out[] = ['index' => (int) $index, 'label' => $this->label((string) $date)];
            }
        }

        return $out;
    }

    /** The months before the opening, one column of the pre-operating table each. */
    public function preOperatingDates(HospitalitySector $study): array
    {
        $out = [];
        foreach ($study->studyStartDateToOperationStartDates() as $date => $index) {
            $out[] = ['index' => (int) $index, 'label' => $this->label((string) $date)];
        }

        return $out;
    }

    public function save(HospitalitySector $study, array $data): void
    {
        DB::transaction(function () use ($study, $data) {
            $dateKeys = array_column($this->preOperatingDates($study), 'index');
            $this->saveSection($study, ExpensePages::START_UP, (array) ($data['sections'][ExpensePages::START_UP] ?? []), function (array $row) {
                return [
                    'chosen_currency' => (string) $row['currency'],
                    'start_up_cost' => RevenueParts::amount($row['cost'] ?? 0),
                    'date' => (int) $row['date'],
                    'cash_payment_percentage' => RevenueParts::amount($row['cash'] ?? 0),
                    'deferred_payment_percentage' => RevenueParts::amount(100 - (float) ($row['cash'] ?? 0)),
                    'due_days' => (string) (int) ($row['dueDays'] ?? 0),
                ];
            });
            $this->saveSection($study, ExpensePages::PRE_OPERATING, (array) ($data['sections'][ExpensePages::PRE_OPERATING] ?? []), function (array $row) use ($dateKeys) {
                $amounts = [];
                foreach ($dateKeys as $key) {
                    $amounts[$key] = (string) CollectionTerms::number(((array) ($row['amounts'] ?? []))[$key] ?? 0);
                }

                return ['chosen_currency' => (string) $row['currency'], 'manpower_payload' => $amounts];
            });
        });
    }

    private function saveSection(HospitalitySector $study, string $section, array $rows, callable $columns): void
    {
        $existing = $this->rows($study, $section)->keyBy('id');
        $kept = [];

        foreach (array_values($rows) as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $values = ['model_name' => self::MODEL, 'section_name' => $section, 'name' => $name, 'hospitality_sector_id' => $study->id, 'company_id' => $study->company_id] + $columns($row);
            $current = isset($row['id']) ? $existing->get((int) $row['id']) : null;
            if ($current) {
                $current->update($values);
                $kept[] = $current->id;
            } else {
                $kept[] = DepartmentExpense::query()->create($values)->id;
            }
        }

        $existing->keys()->diff($kept)->each(fn ($id) => $existing->get($id)->delete());
    }

    private function rows(HospitalitySector $study, string $section): Collection
    {
        return DepartmentExpense::query()->where('hospitality_sector_id', $study->id)->where('model_name', self::MODEL)->where('section_name', $section)->orderBy('id')->get();
    }

    private function currencies(HospitalitySector $study): array
    {
        $ids = array_filter([(int) $study->main_functional_currency, (int) $study->additional_currency]);

        return Currency::query()->whereIn('id', $ids)->orderBy('id')->get(['id', 'name'])
            ->map(fn ($currency) => ['id' => (int) $currency->id, 'name' => (string) $currency->name])->values()->all();
    }

    private function label(string $date): string
    {
        $parts = explode('-', $date);
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        return count($parts) === 3 ? ($months[((int) $parts[1]) - 1] ?? $parts[1]).' '.$parts[2] : $date;
    }
}
