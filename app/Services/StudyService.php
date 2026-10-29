<?php

namespace App\Services;

use App\Models\Company;
use App\Models\HospitalitySector;
use App\Models\User;
use App\Support\StudyCalendar;
use App\Support\StudyStreams;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StudyService (create, change, copy, delete and share a study)
//  Location: app/Services/StudyService.php
//
//  Ports the previous CFO Hotels' HospitalitySectorMutator and the copy / delete of its controller, with the
//  weak spots mended:
//    · everything is done inside one database transaction (a failure leaves nothing half-saved);
//    · the dates are worked out here from the first month and the length (App\Support\StudyCalendar), not sent by the browser;
//    · copying also carries the loans of the land, the property and the furniture along, pointing at the COPIED parents
//      (the old copy left them pointing at the original), and never copies who the study was shared with.
//  The data arrives already checked by App\Http\Requests\Studies\StudyRequest.
// ══════════════════════════════════════════════════════════════════

class StudyService
{
    /** Tables whose rows hang on a study only (copied row by row). */
    public const PLAIN_TABLES = ['rooms', 'saleschannels', 'foods', 'casinos', 'meetings', 'others', 'department_expenses', 'management_fees', 'property_acquisition_breakdowns'];

    /** Tables copied by hand because other rows point at them (or they point at others). */
    public const LINKED_TABLES = ['ffe', 'ffe_items', 'acquisitions', 'property_acquisitions', 'loans'];

    // ── create / change ───────────────────────────────────────────

    public function create(User $user, array $data): HospitalitySector
    {
        return DB::transaction(function () use ($user, $data) {
            $study = new HospitalitySector;
            $study->fill($this->mainColumns($data) + ['company_id' => $user->company_id, 'creator_id' => $user->id]);
            $study->save();

            $this->syncStreams($study, $data, $user->company);
            $this->syncChannels($study, $data);

            return $study;
        });
    }

    public function update(HospitalitySector $study, array $data): HospitalitySector
    {
        return DB::transaction(function () use ($study, $data) {
            $study->fill($this->mainColumns($data))->save();

            $this->syncStreams($study, $data, Company::query()->findOrFail($study->company_id));
            $this->syncChannels($study, $data);

            return $study->refresh();
        });
    }

    /** The columns of the study itself, worked out from the validated form. */
    public function mainColumns(array $data): array
    {
        $start = $data['study_start'];
        $years = (int) $data['duration_in_years'];
        $development = (int) $data['development_start_month'];
        $operation = (int) $data['operation_start_month'];
        $months = StudyCalendar::monthList($start);

        $columns = [
            'study_name' => $data['study_name'],
            'property_name' => $data['property_name'] ?? null,
            'region' => $data['region'] ?? null,
            'property_status' => $data['property_status'],
            'star_rating' => $data['star_rating'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'state_id' => $data['state_id'] ?? null,

            'study_start_date' => StudyCalendar::toStored($start),
            'duration_in_years' => $years,
            'study_end_date' => StudyCalendar::toStored(StudyCalendar::endMonth($start, $years)),
            'development_start_month' => $development,
            'development_start_date' => (string) $development,   // the date number of the development start
            'development_duration' => (int) $data['development_duration'],
            'operation_start_month' => $operation,
            'operation_start_date' => StudyCalendar::toStored(StudyCalendar::addMonths($start, $operation)),
            'study_dates' => $months,
            'operation_dates' => StudyCalendar::operationMonths($months, $operation),

            'financial_year_start_month' => $data['financial_year_start_month'],
            'main_functional_currency' => (string) $data['main_functional_currency'],
            'additional_currency' => isset($data['additional_currency']) ? (string) $data['additional_currency'] : '',
            'exchange_rate' => $data['exchange_rate'],
            'corporate_taxes_rate' => $data['corporate_taxes_rate'],
            'investment_return_rate' => $data['investment_return_rate'],
            'perpetual_growth_rate' => $data['perpetual_growth_rate'],

            'has_sales_channels' => ! empty($data['channels']['on']) ? 1 : 0,
        ];

        foreach (StudyStreams::ALL as $name => $config) {
            $stream = $data['streams'][$name];
            $total = ($stream['mode'] ?? 'types') === 'total' ? 1 : 0;
            $columns[$config['isTotal']] = $name === 'rooms' ? (string) $total : $total;
            if ($config['on'] !== null) {
                $columns[$config['on']] = ! empty($stream['on']) ? 1 : 0;
            }
        }

        return $columns;
    }

    /**
     * Brings the rows of every stream in line with the form: rows for types that are no longer there are removed,
     * rows that stay get the new numbers (their other figures are left alone), new types get new rows.
     * A switched-off stream has no rows at all.
     */
    private function syncStreams(HospitalitySector $study, array $data, Company $company): void
    {
        foreach (StudyStreams::ALL as $name => $config) {
            $stream = $data['streams'][$name];
            $relation = $config['relation'];

            if (empty($stream['on'])) {
                $study->{$relation}()->delete();

                continue;
            }

            $totalId = (int) $company->{$config['totalId']}();
            $wanted = [];
            foreach ($stream['rows'] as $row) {
                $typeId = ($stream['mode'] ?? 'types') === 'total' ? $totalId : (int) $row['type_id'];
                $wanted[$typeId] = $row;
            }

            $existing = $study->{$relation}()->get()->keyBy(fn ($model) => (int) ($model->{$config['type']} ?: $totalId));

            foreach ($existing as $typeId => $model) {
                if (! isset($wanted[$typeId])) {
                    $model->delete();
                }
            }

            foreach ($wanted as $typeId => $row) {
                $numbers = [$config['count'] => (int) $row['count']];
                if ($config['value'] !== null) {
                    $numbers[$config['value']] = $name === 'rooms' ? (string) (float) $row['value'] : (int) $row['value'];
                }

                if ($model = $existing->get($typeId)) {
                    $model->update($numbers);
                } else {
                    $study->{$relation}()->create([$config['type'] => $typeId] + $numbers + $config['extra']);
                }
            }
        }
    }

    private function syncChannels(HospitalitySector $study, array $data): void
    {
        $channels = $data['channels'] ?? [];

        if (empty($channels['on'])) {
            $study->salesChannels()->delete();

            return;
        }

        $names = array_values(array_unique($channels['names'] ?? []));
        $study->salesChannels()->whereNotIn('name', $names)->delete();
        $have = $study->salesChannels()->pluck('name')->all();

        foreach (array_diff($names, $have) as $name) {
            $study->salesChannels()->create(['name' => $name]);
        }
    }

    // ── copy ──────────────────────────────────────────────────────

    /** A full copy of a study (every figure entered so far) under a new name, owned by $by. */
    public function copy(HospitalitySector $source, User $by, string $name): HospitalitySector
    {
        return DB::transaction(function () use ($source, $by, $name) {
            $copy = $source->replicate(['id']);
            $copy->study_name = $name;
            $copy->creator_id = $by->id;
            $copy->save();

            $old = (int) $source->id;
            $new = (int) $copy->id;

            foreach (self::PLAIN_TABLES as $table) {
                $this->copyRows($table, $old, $new);
            }

            $ffe = $this->copyRows('ffe', $old, $new);
            $this->copyRows('ffe_items', $old, $new, ['ffe_id' => $ffe]);
            $acquisitions = $this->copyRows('acquisitions', $old, $new);
            $properties = $this->copyRows('property_acquisitions', $old, $new);
            $this->copyLoans($old, $new, $ffe, $acquisitions, $properties);

            return $copy;
        });
    }

    /** Copies the rows of one table that belong to the study; returns [old row id => new row id]. */
    private function copyRows(string $table, int $oldStudy, int $newStudy, array $remap = []): array
    {
        $map = [];

        foreach (DB::table($table)->where('hospitality_sector_id', $oldStudy)->orderBy('id')->get() as $row) {
            $data = (array) $row;
            $oldId = $data['id'];
            unset($data['id']);
            $data['hospitality_sector_id'] = $newStudy;

            foreach ($remap as $column => $ids) {
                if (isset($data[$column], $ids[$data[$column]])) {
                    $data[$column] = $ids[$data[$column]];
                }
            }
            $map[$oldId] = DB::table($table)->insertGetId($data);
        }

        return $map;
    }

    private function loansOf(int $study, array $ffe, array $acquisitions, array $properties)
    {
        return DB::table('loans')->where(function ($q) use ($study, $ffe, $acquisitions, $properties) {
            $q->where('hospitality_sector_id', $study)
                ->orWhereIn('ffe_id', array_keys($ffe))
                ->orWhereIn('acquisition_id', array_merge(array_keys($acquisitions), array_keys($properties)));
        });
    }

    private function copyLoans(int $oldStudy, int $newStudy, array $ffe, array $acquisitions, array $properties): void
    {
        foreach ($this->loansOf($oldStudy, $ffe, $acquisitions, $properties)->orderBy('id')->get() as $row) {
            $data = (array) $row;
            unset($data['id']);

            if ((int) $data['hospitality_sector_id'] === $oldStudy) {
                $data['hospitality_sector_id'] = $newStudy;
            }
            if ($data['ffe_id'] !== null && isset($ffe[$data['ffe_id']])) {
                $data['ffe_id'] = $ffe[$data['ffe_id']];
            }
            if ($data['acquisition_id'] !== null) {
                $id = $data['acquisition_id'];
                // Land / building loans and property loans share one column. The section name says which parent it is.
                $isProperty = str_starts_with((string) ($data['section_name'] ?? ''), 'property');
                if (isset($properties[$id]) && ($isProperty || ! isset($acquisitions[$id]))) {
                    $data['acquisition_id'] = $properties[$id];
                } elseif (isset($acquisitions[$id])) {
                    $data['acquisition_id'] = $acquisitions[$id];
                }
            }
            DB::table('loans')->insert($data);
        }
    }

    // ── delete / share ────────────────────────────────────────────

    /** Deletes the study and everything that hangs on it (loans included). */
    public function delete(HospitalitySector $study): void
    {
        DB::transaction(function () use ($study) {
            $id = (int) $study->id;
            $ffe = DB::table('ffe')->where('hospitality_sector_id', $id)->pluck('id', 'id')->all();
            $acquisitions = DB::table('acquisitions')->where('hospitality_sector_id', $id)->pluck('id', 'id')->all();
            $properties = DB::table('property_acquisitions')->where('hospitality_sector_id', $id)->pluck('id', 'id')->all();

            $this->loansOf($id, $ffe, $acquisitions, $properties)->delete();
            $study->delete();   // the rest follows by the database's own cascade
        });
    }

    /** Replaces who the study is shared with. $access: [user id => 'view' | 'edit']; anything else means "not shared". */
    public function share(HospitalitySector $study, array $access): void
    {
        $sync = [];
        foreach ($access as $userId => $level) {
            if (in_array($level, ['view', 'edit'], true)) {
                $sync[(int) $userId] = ['can_edit' => $level === 'edit'];
            }
        }
        $study->sharedWith()->sync($sync);
    }
}
