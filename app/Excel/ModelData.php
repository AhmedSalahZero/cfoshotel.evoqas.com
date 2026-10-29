<?php

namespace App\Excel;

// What the workbook is built from: the saved rows of a study (see ModelDataBuilder) and the calendar facts every sheet needs.
// The calendar is the one of the application: months from the study start to December of the year the study ends (the revenue is worked
// out for all of them), a month number 0.. and a year number counted from the first calendar year of the study.
final class ModelData
{
    public readonly array $p;
    /** @var list<string> "Y-m-d" of the first day of every month of the grid (index 0 = the study's first month) */
    public readonly array $dates;
    public readonly int $D;            // months in the grid
    public readonly int $Y;            // year columns
    public readonly int $baseYear;
    public readonly int $opIdx;        // month number the hotel opens
    public readonly int $studyEndIdx;  // month number of the last month of the study
    public readonly array $rooms;
    public readonly array $channels;
    public readonly array $foods;
    public readonly array $casinos;
    public readonly array $meetings;
    public readonly array $others;
    public readonly array $expenses;
    public readonly array $managementFees;
    public readonly ?array $acquisition;
    public readonly ?array $property;
    public readonly array $breakdown;
    public readonly ?array $ffe;
    public readonly array $ffeItems;
    public readonly array $loans;
    /** @var array{property: ?array, land: ?array, hard: ?array, ffe: ?array} */
    public readonly array $loanFor;
    /** @var array<string,array<int,string>> */
    public readonly array $names;

    /** @param array<string,mixed> $dump */
    public function __construct(array $dump)
    {
        $p = $dump['study'];
        $this->p = $p;

        $all = array_map(fn ($d) => self::ymd($d), array_values(self::arr($p['study_dates'] ?? [])));
        $endYmd = self::ymd((string) $p['study_end_date']);
        $endYear = (int) substr($endYmd, 0, 4);
        // through December of the year the study ends
        $dates = array_values(array_filter($all, fn ($d) => (int) substr($d, 0, 4) <= $endYear));
        $this->dates = $dates;
        $this->D = count($dates);
        $this->baseYear = (int) substr($dates[0], 0, 4);
        $this->Y = $endYear - $this->baseYear + 1;
        $this->opIdx = (int) array_search(self::ymd((string) $p['operation_start_date']), $dates, true);
        $this->studyEndIdx = (int) array_search($endYmd, $dates, true);

        $this->rooms = $dump['rooms'] ?? [];
        $this->channels = $dump['channels'] ?? [];
        $this->foods = $dump['foods'] ?? [];
        $this->casinos = $dump['casinos'] ?? [];
        $this->meetings = $dump['meetings'] ?? [];
        $this->others = $dump['others'] ?? [];
        $this->expenses = $dump['expenses'] ?? [];
        $this->managementFees = $dump['management_fees'] ?? [];
        $this->acquisition = $dump['acquisition'] ?? null;
        $this->property = $dump['property'] ?? null;
        $this->breakdown = $dump['breakdown'] ?? [];
        $this->ffe = $dump['ffe'] ?? null;
        $this->ffeItems = $dump['ffe_items'] ?? [];
        $this->loans = $dump['loans'] ?? [];
        $this->loanFor = $dump['loan_for'] ?? ['property' => null, 'land' => null, 'hard' => null, 'ffe' => null];
        $this->names = $dump['names'] ?? [];
    }

    /** The name of a type (kind: room, food, casino, meeting, other). */
    public function name(string $kind, mixed $id): string
    {
        return (string) ($this->names[$kind][$id] ?? ('#'.$id));
    }

    /** "d-m-Y", "m/d/Y" or "Y-m-d" (as the application stores dates) to "Y-m-d". */
    public static function ymd(string $date): string
    {
        $date = trim($date);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $date, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
        }

        return $date;
    }

    /** Excel serial number of the first of a grid month. */
    public function serial(int $idx): int
    {
        return self::serialOf($this->dates[$idx] ?? $this->dates[array_key_last($this->dates)]);
    }

    public static function serialOf(string $ymd): int
    {
        // A calendar day is the same day everywhere, so it is read as midnight UTC. 25569 = the Excel number of 1970-01-01.
        return intdiv((new \DateTimeImmutable(substr($ymd, 0, 10), new \DateTimeZone('UTC')))->getTimestamp(), 86400) + 25569;
    }

    /** A value saved per year (year number => value) for every year column of the workbook. @return list<float> */
    public function yearly(mixed $values): array
    {
        $values = self::arr($values);
        $out = [];
        for ($y = 0; $y < $this->Y; $y++) {
            $out[] = (float) ($values[$y] ?? 0);
        }

        return $out;
    }

    /** @return array<int|string,mixed> */
    public static function arr(mixed $v): array
    {
        if (is_array($v)) {
            return $v;
        }
        if (is_string($v) && $v !== '') {
            $d = json_decode($v, true);

            return is_array($d) ? $d : [];
        }

        return [];
    }
}
