<?php

namespace App\Services\Expenses;

use App\Models\Currency;
use App\Models\DepartmentExpense;
use App\Models\HospitalitySector;
use App\Services\Revenue\FoodsRevenueService;
use App\Services\Revenue\RevenueFiguresService;
use App\Services\Revenue\RevenueParts;
use App\Support\CollectionTerms;
use App\Support\ExpensePages;
use App\Support\StudyYears;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — ExpensesService (what the nine expense screens show and save)
//  Location: app/Services/Expenses/ExpensesService.php
//
//  Every line the person writes is a row of department_expenses, found by its model (the screen) and its section (the table of the screen).
//  A line with a name is kept; a line whose name is emptied is deleted; the rows are never rebuilt, so a row keeps its id (the calculation
//  names its results by that id).
//  The figures "at the operation date" are worked out here, as the old screen worked them out in the browser, because the calculation reads
//  the saved figure, not the typed one:  amount x (1 + escalation %) ^ years from the study start to the opening.
//  Layout of the screen is in App\Support\ExpensePages.
// ══════════════════════════════════════════════════════════════════

class ExpensesService
{
    /** The most months of manpower the screen asks for; the last one is kept for the months after (the calculation repeats it). */
    public const MAX_MONTHS = 48;

    public function __construct(private readonly RevenueFiguresService $figures) {}

    // ── what the screen shows ─────────────────────────────────────

    public function present(HospitalitySector $study, array $page): array
    {
        $years = StudyYears::of($study);
        $main = (int) $study->main_functional_currency;
        $dates = $this->operatingDates($study);

        // positions saved by the old application count as "yes" even when its flag was never set
        $flag = $page['flag'] === null ? null : ((bool) $study->{$page['flag']} || $this->rows($study, $page['model'], ExpensePages::MANPOWER)->isNotEmpty());

        $sections = [];
        foreach ($page['sections'] as $section) {
            $saved = $this->rows($study, $page['model'], $section['name']);
            $sections[] = ['kind' => $section['kind'], 'name' => $section['name'], 'style' => $section['style'] ?? null, 'rows' => $this->shape($section, $saved, $years, $main, $dates)];
        }

        return [
            'page' => $page['key'],
            'years' => $years,
            'dates' => $dates,
            'daysDifference' => (float) $study->getDiffBetweenOperationStartDateAndStudyStartDate(),
            'estimationDate' => (string) $study->getStudyStartDateFormattedForView(),
            'currencies' => $this->currencies($study),
            'days' => ExpensePages::DAYS,
            'terms' => ExpensePages::TERMS,
            'flag' => $flag,
            'form' => ['flag' => (bool) $flag, 'sections' => collect($sections)->mapWithKeys(fn ($s) => [$s['name'] => $s['rows']])->all()],
            'layout' => collect($sections)->map(fn ($s) => ['kind' => $s['kind'], 'name' => $s['name'], 'style' => $s['style']])->all(),
        ];
    }

    /**
     * The revenue the percentages of a page are taken from, for every operating year. It needs the whole calculation of the hotel (several seconds),
     * so the page is shown first and this arrives a moment later (a deferred prop of the controller).
     */
    public function revenue(HospitalitySector $study, array $page): array
    {
        $figures = $this->figures->figures($study);

        return collect(StudyYears::of($study))->mapWithKeys(fn (array $y) => [$y['index'] => (float) ($figures['revenue'][$page['revenue']][$y['index']] ?? 0)])->all();
    }

    /** The months of operation, as the date index the calculation keeps them under, with the label the screen shows. */
    public function operatingDates(HospitalitySector $study): array
    {
        $first = $study->getOperationStartDateAsIndex();
        $last = $study->getStudyEndDateAsIndex();
        if ($first === null || $last === null) {
            return [];
        }

        $out = [];
        foreach ($study->getStudyDates() as $index => $date) {
            if ((int) $index >= $first && (int) $index <= $last) {
                $out[] = ['index' => (int) $index, 'label' => $this->label((string) $date)];
            }
            if (count($out) >= self::MAX_MONTHS) {
                break;
            }
        }

        return $out;
    }

    private function label(string $date): string
    {
        $parts = explode('-', $date);
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        return count($parts) === 3 ? ($months[((int) $parts[1]) - 1] ?? $parts[1]).' '.$parts[2] : $date;
    }

    private function rows(HospitalitySector $study, string $model, string $section): Collection
    {
        return DepartmentExpense::query()->where('hospitality_sector_id', $study->id)->where('model_name', $model)->where('section_name', $section)->orderBy('id')->get();
    }

    private function shape(array $section, Collection $saved, array $years, int $main, array $dates): array
    {
        $rows = [];
        $count = $section['rows'];
        for ($i = 0; $i < $count; $i++) {
            /** @var DepartmentExpense|null $e */
            $e = $saved->values()->get($i);
            $rows[] = match ($section['kind']) {
                'percent' => $this->percentRow($e, $years),
                'cost' => $this->costRow($e, $section['style'], $main),
                'disposable' => $this->disposableRow($e, $years),
                'manpower' => $this->manpowerRow($e, $dates, $main),
                'property' => $this->propertyRow($e, $i, $main),
            };
        }

        return $rows;
    }

    private function years(?DepartmentExpense $e, array $years): object
    {
        return RevenueParts::yearMap($e && $e->payload ? json_encode($e->payload) : null, $years);
    }

    private function percentRow(?DepartmentExpense $e, array $years): array
    {
        return ['id' => $e?->id, 'name' => (string) $e?->getName(), 'values' => $this->years($e, $years)];
    }

    private function costRow(?DepartmentExpense $e, string $style, int $main): array
    {
        $c = ExpensePages::COST_COLUMNS[$style];
        $row = [
            'id' => $e?->id, 'name' => (string) $e?->getName(),
            'currency' => (int) ($e?->{$c['currency']} ?: $main),
            'amount' => CollectionTerms::number($e?->{$c['amount']} ?: 0),
            'escalation' => CollectionTerms::number($e?->{$c['escalation']} ?: 0),
            'atOperation' => CollectionTerms::number($e?->{$c['atOperation']} ?: 0),
            'annual' => CollectionTerms::number($e?->{$c['annual']} ?: 0),
        ];
        if ($style === 'fixed') {
            $row['terms'] = in_array($e?->opex_payment_terms, ExpensePages::TERMS, true) ? $e->opex_payment_terms : 'monthly';
        }

        return $row;
    }

    private function disposableRow(?DepartmentExpense $e, array $years): array
    {
        return [
            'id' => $e?->id, 'name' => (string) $e?->getName(),
            'beginning' => CollectionTerms::number($e?->getBeginningInventoryBalanceValue() ?: 0),
            'coverage' => (int) ($e?->getInventoryCoverageDays() ?: 0),
            'cash' => CollectionTerms::number($e ? $e->getCashPayment() : 100),
            'dueDays' => (int) ($e?->getDueDays() ?: 0),
            'values' => $this->years($e, $years),
        ];
    }

    private function manpowerRow(?DepartmentExpense $e, array $dates, int $main): array
    {
        $heads = [];
        foreach ($dates as $date) {
            $heads[$date['index']] = $e ? CollectionTerms::number($e->getManpowerPayloadAtDate((string) $date['index'])) : 0;
        }

        return [
            'id' => $e?->id, 'name' => (string) $e?->getName(),
            'salary' => CollectionTerms::number($e?->getCurrentNetSalary() ?: 0),
            'currency' => (int) ($e?->getChosenCurrency() ?: $main),
            'escalation' => CollectionTerms::number($e?->getEscalationRate() ?: 0),
            'atOperation' => CollectionTerms::number($e?->getNetSalaryAtOperationDate() ?: 0),
            'annual' => CollectionTerms::number($e?->getAnnualEscalationRate() ?: 0),
            'taxes' => CollectionTerms::number($e?->getSalaryTaxes() ?: 0),
            'insurance' => CollectionTerms::number($e?->getSocialInsurance() ?: 0),
            'heads' => RevenueParts::map($heads),
        ];
    }

    private function propertyRow(?DepartmentExpense $e, int $i, int $main): array
    {
        return [
            'id' => $e?->id, 'name' => ExpensePages::PROPERTY_NAMES[$i],
            'currency' => (int) ($e?->getNightExpenseChosenCurrency() ?: $main),
            'percentage' => CollectionTerms::number($e?->getPercentageFromFixedAssets() ?: 0),
            'annual' => CollectionTerms::number($e?->getGuestAnnualEscalationRate() ?: 0),
            'month' => $i === 0 ? (string) ($e?->getPaymentMonth() ?: '01') : null,
        ];
    }

    private function currencies(HospitalitySector $study): array
    {
        $ids = array_filter([(int) $study->main_functional_currency, (int) $study->additional_currency]);

        return Currency::query()->whereIn('id', $ids)->orderBy('id')->get(['id', 'name'])
            ->map(fn ($currency) => ['id' => (int) $currency->id, 'name' => (string) $currency->name])->values()->all();
    }

    // ── what is saved ─────────────────────────────────────────────

    public function save(HospitalitySector $study, array $page, array $data): void
    {
        DB::transaction(function () use ($study, $page, $data) {
            $yearKeys = array_column(StudyYears::of($study), 'index');
            $dateKeys = array_column($this->operatingDates($study), 'index');
            $power = (float) $study->getDiffBetweenOperationStartDateAndStudyStartDate();

            if ($page['flag'] !== null) {
                $study->update([$page['flag'] => ! empty($data['flag']) ? '1' : '0']);
            }

            foreach ($page['sections'] as $section) {
                $rows = (array) ($data['sections'][$section['name']] ?? []);
                // answering "no" to the manpower question removes the positions, so they are not counted in the results
                if ($section['kind'] === 'manpower' && empty($data['flag'])) {
                    $rows = [];
                }
                $this->saveSection($study, $page['model'], $section, $rows, $yearKeys, $dateKeys, $power);
            }
        });
    }

    private function saveSection(HospitalitySector $study, string $model, array $section, array $rows, array $yearKeys, array $dateKeys, float $power): void
    {
        $existing = $this->rows($study, $model, $section['name'])->keyBy('id');
        $kept = [];

        foreach (array_values($rows) as $i => $row) {
            $name = $section['kind'] === 'property' ? ExpensePages::PROPERTY_NAMES[$i] : trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $columns = ['model_name' => $model, 'section_name' => $section['name'], 'name' => $name, 'hospitality_sector_id' => $study->id, 'company_id' => $study->company_id]
                + $this->columns($section, $row, $i, $yearKeys, $dateKeys, $power);

            $current = isset($row['id']) ? $existing->get((int) $row['id']) : null;
            if ($current) {
                $current->update($columns);
                $kept[] = $current->id;
            } else {
                $kept[] = DepartmentExpense::query()->create($columns)->id;
            }
        }

        $existing->keys()->diff($kept)->each(fn ($id) => $existing->get($id)->delete());
    }

    private function years_(array $values, array $yearKeys): array
    {
        $out = [];
        foreach ($yearKeys as $key) {
            $out[$key] = (string) CollectionTerms::number($values[$key] ?? 0);
        }

        return $out;
    }

    private function columns(array $section, array $row, int $i, array $yearKeys, array $dateKeys, float $power): array
    {
        $amount = fn ($v) => RevenueParts::amount($v ?? 0);

        switch ($section['kind']) {
            case 'percent':
                return ['payload' => $this->years_((array) ($row['values'] ?? []), $yearKeys)];

            case 'cost':
                $c = ExpensePages::COST_COLUMNS[$section['style']];
                $columns = [
                    $c['currency'] => (string) $row['currency'],
                    $c['amount'] => $amount($row['amount']),
                    $c['escalation'] => $amount($row['escalation']),
                    $c['atOperation'] => $amount(FoodsRevenueService::coverValueAtOperation((float) $row['amount'], (float) $row['escalation'], $power)),
                    $c['annual'] => $amount($row['annual']),
                ];
                if ($section['style'] === 'fixed') {
                    $columns['opex_payment_terms'] = (string) $row['terms'];
                }

                return $columns;

            case 'disposable':
                return [
                    'beginning_inventory_balance_value' => $amount($row['beginning']),
                    'inventory_coverage_days' => (string) (int) $row['coverage'],
                    'cash_payment_percentage' => $amount($row['cash']),
                    'deferred_payment_percentage' => $amount(100 - (float) $row['cash']),
                    'due_days' => (string) (int) $row['dueDays'],
                    'payload' => $this->years_((array) ($row['values'] ?? []), $yearKeys),
                ];

            case 'manpower':
                $heads = [];
                foreach ($dateKeys as $key) {
                    $heads[$key] = (string) CollectionTerms::number(((array) ($row['heads'] ?? []))[$key] ?? 0);
                }

                return [
                    'current_net_salary' => $amount($row['salary']),
                    'chosen_currency' => (string) $row['currency'],
                    'escalation_rate' => $amount($row['escalation']),
                    'net_salary_at_operation_date' => $amount(FoodsRevenueService::coverValueAtOperation((float) $row['salary'], (float) $row['escalation'], $power)),
                    'annual_escalation_rate' => $amount($row['annual']),
                    'salary_taxes' => $amount($row['taxes']),
                    'social_insurance' => $amount($row['insurance']),
                    'manpower_payload' => $heads,
                ];

            case 'property':
                return [
                    'chosen_night_expense_currency' => (string) $row['currency'],
                    'percentage_from_fixed_assets' => $amount($row['percentage']),
                    'guest_annual_escalation_rate' => $amount($row['annual']),
                    'payment_month' => $i === 0 ? (string) $row['month'] : null,
                ];
        }

        return [];
    }
}
