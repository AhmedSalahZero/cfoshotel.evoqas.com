<?php

namespace App\Excel;

use App\Models\HospitalitySector;
use Illuminate\Support\Facades\DB;

// The saved rows of a study, as plain arrays (the workbook is built from these and from nothing else).
final class ModelDataBuilder
{
    /** @return array<string,mixed> */
    public static function fromStudy(HospitalitySector $study): array
    {
        $id = (int) $study->id;
        $rows = fn (string $table, string $order = 'id') => DB::table($table)->where('hospitality_sector_id', $id)->orderBy($order)->get()->map(fn ($r) => (array) $r)->all();
        $sector = (array) DB::table('hospitality_sectors')->where('id', $id)->first();

        return [
            'study' => $sector,
            'rooms' => $rows('rooms'),
            'channels' => $rows('saleschannels'),
            'foods' => $rows('foods'),
            'casinos' => $rows('casinos'),
            'meetings' => $rows('meetings'),
            'others' => $rows('others'),
            'expenses' => $rows('department_expenses'),
            'management_fees' => $rows('management_fees'),
            'acquisition' => $rows('acquisitions')[0] ?? null,
            'property' => $rows('property_acquisitions')[0] ?? null,
            'breakdown' => $rows('property_acquisition_breakdowns'),
            'ffe' => $rows('ffe')[0] ?? null,
            'ffe_items' => $rows('ffe_items'),
            'loans' => $rows('loans'),
            // the loan of each purchase, found as the application finds it (a loan belongs to the acquisition / property / FF&E row it points at)
            'loan_for' => self::loansFor($id),
            // the names of the types the rows point at (room_type_id, food_type_id, ...)
            'names' => [
                'room' => DB::table('room_names')->pluck('title', 'id')->all(),
                'food' => DB::table('food_names')->pluck('title', 'id')->all(),
                'casino' => DB::table('casino_names')->pluck('title', 'id')->all(),
                'meeting' => DB::table('meeting_names')->pluck('title', 'id')->all(),
                'other' => DB::table('other_names')->pluck('title', 'id')->all(),
            ],
        ];
    }

    /** @return array{property: ?array, land: ?array, hard: ?array, ffe: ?array} */
    private static function loansFor(int $studyId): array
    {
        $first = fn (string $table) => ($row = DB::table($table)->where('hospitality_sector_id', $studyId)->orderBy('id')->first()) ? (int) $row->id : null;
        $loan = fn (string $column, ?int $owner, string $section) => $owner === null ? null
            : (($row = DB::table('loans')->where($column, $owner)->where('section_name', $section)->orderBy('id')->first()) ? (array) $row : null);

        $acquisition = $first('acquisitions');
        $property = $first('property_acquisitions');
        $ffe = $first('ffe');
        // the FF&E loan: the one with the id of the FF&E, else the newest of its loans
        $ffeLoans = $ffe === null ? collect() : DB::table('loans')->where('ffe_id', $ffe)->where('section_name', 'ffe_cost')->get();
        $ffeLoan = $ffeLoans->firstWhere('id', $ffe) ?? $ffeLoans->sortByDesc('id')->first();

        return [
            'property' => $loan('acquisition_id', $property, 'property_loan'),
            'land' => $loan('acquisition_id', $acquisition, 'land'),
            'hard' => $loan('acquisition_id', $acquisition, 'hard_cost_construction'),
            'ffe' => $ffeLoan ? (array) $ffeLoan : null,
        ];
    }
}
