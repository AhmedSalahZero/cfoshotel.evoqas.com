<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StudyCache (keep a calculated result until something is saved)
//  Location: app/Support/StudyCache.php
//
//  The whole-hotel calculation takes about five seconds and the statements about as much again, and every screen of the results asked for it
//  again. This keeps the finished result (plain arrays only) in files under storage/framework/cache and gives it back until the data changes.
//
//  How old figures are kept out:
//    · One "version" is shared by all studies. EVERY write (insert, update, delete) to a table that holds study data or the type lists
//      changes it - whoever wrote: a screen, a seeder or a command - because the application watches its own database queries (AppServiceProvider).
//    · A kept result is used only when the version it was made under is still the current one; otherwise it is calculated again and replaces it.
//    · A result is kept only when it holds plain data (no objects) and is not huge; otherwise nothing is kept and the answer is calculated as before.
//    · Any trouble with the files (full disk, no rights) falls back to calculating: the cache can slow nothing down and change nothing.
//  Off while the automated tests run (unless a test switches it on), so the other tests see no change.
// ══════════════════════════════════════════════════════════════════

final class StudyCache
{
    /** Tables whose change can change a figure or a name in the results. */
    public const TABLES = [
        'hospitality_sectors', 'rooms', 'saleschannels', 'foods', 'casinos', 'meetings', 'others', 'department_expenses', 'management_fees',
        'acquisitions', 'property_acquisitions', 'property_acquisition_breakdowns', 'ffe', 'ffe_items', 'loans',
        'room_names', 'food_names', 'casino_names', 'meeting_names', 'other_names', 'companies', 'currencies',
    ];

    private const MAX_BYTES = 30 * 1048576;

    private static ?bool $forced = null;

    /** Tests: true = on, false = off, null = the normal rule (on, except while the tests run). */
    public static function enable(?bool $on = true): void
    {
        self::$forced = $on;
    }

    private static function enabled(): bool
    {
        return self::$forced ?? ! app()->runningUnitTests();
    }

    private static function store()
    {
        return Cache::store('file');
    }

    public static function version(): string
    {
        try {
            $version = self::store()->get('studies-version');

            return is_string($version) && $version !== '' ? $version : self::bump();
        } catch (Throwable) {
            return uniqid('broken-', true);   // never equal to a kept one: nothing is reused
        }
    }

    public static function bump(): string
    {
        $version = uniqid('', true);

        try {
            self::store()->forever('studies-version', $version);
        } catch (Throwable) {
            // the files cannot be written: results are then calculated each time
        }

        return $version;
    }

    /** Called with the text of every database query: a write to a study table changes the version. */
    public static function noteQuery(string $sql): void
    {
        if (preg_match('/^\s*(?:insert\s+(?:ignore\s+)?into|replace\s+into|update|delete\s+from)\s+[`"\[]?(\w+)/i', $sql, $m)
            && in_array(strtolower($m[1]), self::TABLES, true)) {
            self::bump();
        }
    }

    /**
     * The kept result of $name for a study, or the result of $compute (kept for next time when it is plain data).
     *
     * @param  Closure|null  $keep  asked with the result; when it says false the result is not kept (for example "could not be calculated")
     */
    public static function remember(int $studyId, string $name, Closure $compute, ?Closure $keep = null): mixed
    {
        if (! self::enabled()) {
            return $compute();
        }

        $key = "study-{$studyId}-{$name}";
        $version = self::version();

        try {
            $kept = self::store()->get($key);
            if (is_array($kept) && ($kept['version'] ?? null) === $version && array_key_exists('data', $kept)) {
                return $kept['data'];
            }
        } catch (Throwable) {
            // unreadable: calculate
        }

        $started = microtime(true);
        $data = $compute();
        self::note($studyId, $name, microtime(true) - $started);

        if ($data !== null && ($keep === null || $keep($data))) {
            try {
                $blob = serialize($data);
                if (strlen($blob) <= self::MAX_BYTES && ! preg_match('/(?:^|[;{}])O:\d+:"/', $blob)) {
                    self::store()->forever($key, ['version' => $version, 'data' => $data]);
                }
            } catch (Throwable) {
                // not kept
            }
        }

        return $data;
    }

    /** One line in storage/logs/laravel.log each time something had to be calculated again: what and how many seconds. */
    private static function note(int $studyId, string $name, float $seconds): void
    {
        try {
            Log::info(sprintf('CACHE MISS study %d %s: %.2fs', $studyId, $name, $seconds));
        } catch (Throwable) {
            // logging must never stop a screen
        }
    }
}
