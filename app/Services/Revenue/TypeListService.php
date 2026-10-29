<?php

namespace App\Services\Revenue;

use App\Models\Company;
use App\Support\StudyStreams;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — TypeListService (the company's own lists of room types, food outlets, gaming areas, halls and other revenue)
//  Location: app/Services/Revenue/TypeListService.php
//
//  Every company starts with the standard lists (App\Models\Concerns\HasHotelTypeLists). The company administrator can rename
//  a type, add new ones and remove the ones no study uses. The first row of each list, the "Total ..." row, belongs to the
//  system: it is not shown here and cannot be changed or removed. A type that a study uses cannot be removed
//  (the previous application said "Some items can not be deleted where they are used in some studies").
// ══════════════════════════════════════════════════════════════════

class TypeListService
{
    /** @return array<string, array<int, array{id: int, title: string, inUse: bool}>> */
    public function lists(Company $company): array
    {
        $lists = [];
        foreach (array_keys(StudyStreams::ALL) as $stream) {
            $lists[$stream] = $this->rows($company, $stream);
        }

        return $lists;
    }

    /** The types of one stream without the "Total ..." row, in the company's order. */
    public function rows(Company $company, string $stream): array
    {
        $config = StudyStreams::ALL[$stream];
        $used = DB::table($config['relation'])->whereIn($config['type'], $company->{$config['names']}()->pluck('id'))->distinct()->pluck($config['type'])->map(fn ($id) => (int) $id)->all();

        return StudyStreams::types($company, $stream)->where('total', false)
            ->map(fn ($type) => ['id' => $type['id'], 'title' => $type['title'], 'inUse' => in_array($type['id'], $used, true)])->values()->all();
    }

    /**
     * Saves the list the person sent: [['id' => 12|null, 'title' => '...'], ...].
     * Rows that are missing from it are removed (when no study uses them); rows without an id are new.
     */
    public function save(Company $company, string $stream, array $rows): void
    {
        DB::transaction(function () use ($company, $stream, $rows) {
            $config = StudyStreams::ALL[$stream];
            $current = collect($this->rows($company, $stream))->keyBy('id');
            $keepIds = collect($rows)->pluck('id')->filter()->map(fn ($id) => (int) $id);

            $blocked = $current->filter(fn ($row, $id) => ! $keepIds->contains($id) && $row['inUse'])->pluck('title')->all();
            if ($blocked) {
                throw ValidationException::withMessages(['rows' => __('revenue.errors.type_in_use', ['names' => implode(', ', $blocked)])]);
            }

            $company->{$config['names']}()->whereIn('id', $current->keys()->diff($keepIds))->delete();

            foreach ($rows as $row) {
                $title = trim((string) $row['title']);
                if (! empty($row['id'])) {
                    $company->{$config['names']}()->whereKey((int) $row['id'])->update(['title' => $title]);
                } else {
                    $company->{$config['names']}()->create(['title' => $title, 'is_active' => 1]);
                }
            }
        });
    }
}
