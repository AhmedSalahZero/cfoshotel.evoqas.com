<?php

namespace App\Services\Expenses;

use App\Models\HospitalitySector;
use App\Models\ManagementFee;
use App\Services\Revenue\RevenueFiguresService;
use App\Support\CollectionTerms;
use App\Support\StudyYears;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — ManagementFeesService (the base and the incentive management fee of a study)
//  Location: app/Services/Expenses/ManagementFeesService.php
//
//  Two lines, each a percentage for every operating year, kept in management_fees (model_name "management") as the old application kept them:
//    base_management_fees_as_percentage_from_revenues       name "Base Management Fees"       (a percentage of the total revenue of the hotel)
//    incentive_management_fees_as_percentage_from_revenues  name "Incentive Management Fees"  (a percentage of EBITDA; the revenue row is not shown)
//  The saved percentage of the last year is repeated for the years after it, which is what the model does on saving.
// ══════════════════════════════════════════════════════════════════

class ManagementFeesService
{
    public const MODEL = 'management';

    /** section name => the name the old application stores, and whether the revenue row is hidden. */
    public const SECTIONS = [
        'base_management_fees_as_percentage_from_revenues' => ['name' => 'Base Management Fees', 'hidesRevenue' => false],
        'incentive_management_fees_as_percentage_from_revenues' => ['name' => 'Incentive Management Fees', 'hidesRevenue' => true],
    ];

    public function __construct(private readonly RevenueFiguresService $figures) {}

    /** The total revenues of every operating year (needs the whole calculation, so the page asks for it after it is shown). */
    public function revenue(HospitalitySector $study): array
    {
        $figures = $this->figures->figures($study);

        return collect(StudyYears::of($study))->mapWithKeys(fn (array $y) => [$y['index'] => (float) ($figures['revenue']['total'][$y['index']] ?? 0)])->all();
    }

    public function present(HospitalitySector $study): array
    {
        $years = StudyYears::of($study);
        $sections = [];
        $form = [];

        foreach (self::SECTIONS as $key => $section) {
            $row = $this->row($study, $key);
            $values = [];
            foreach ($years as $year) {
                $values[$year['index']] = $row ? CollectionTerms::number($row->getPayloadAtYear($year['index'])) : 0;
            }
            $sections[] = ['name' => $key, 'label' => $section['name'], 'hidesRevenue' => $section['hidesRevenue']];
            $form[$key] = [['id' => $row?->id, 'name' => $section['name'], 'values' => (object) $values]];
        }

        return [
            'years' => $years,
            'layout' => $sections,
            'form' => ['sections' => $form],
        ];
    }

    public function save(HospitalitySector $study, array $data): void
    {
        $yearKeys = array_column(StudyYears::of($study), 'index');

        DB::transaction(function () use ($study, $data, $yearKeys) {
            foreach (self::SECTIONS as $key => $section) {
                $values = [];
                foreach ($yearKeys as $index) {
                    $values[$index] = (string) CollectionTerms::number($data['sections'][$key][0]['values'][$index] ?? 0);
                }
                $columns = ['payload' => $values, 'name' => $section['name'], 'model_name' => self::MODEL, 'section_name' => $key,
                    'hospitality_sector_id' => $study->id, 'company_id' => $study->company_id];

                $row = $this->row($study, $key);
                $row ? $row->update($columns) : ManagementFee::query()->create($columns);
            }
        });
    }

    private function row(HospitalitySector $study, string $section): ?ManagementFee
    {
        return ManagementFee::query()->where('hospitality_sector_id', $study->id)->where('model_name', self::MODEL)->where('section_name', $section)->orderBy('id')->first();
    }
}
