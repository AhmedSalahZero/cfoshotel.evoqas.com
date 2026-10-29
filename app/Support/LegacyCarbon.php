<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Carbon 2 date differences, kept on Carbon 3 (Laravel 11+). Carbon 3 returns signed
 * floats from diffIn*() where Carbon 2 returned whole, absolute integers; the app's
 * figures are built on the Carbon 2 results. The contract is tests/Fixtures/carbon
 * (generated with Carbon 2, checked by tests/Unit/LegacyCarbonTest).
 */
final class LegacyCarbon
{
    /**
     * Carbon 2's Carbon::make(). Carbon 3 behaves the same except for the string '0': Carbon 2 created
     * every empty() time as "now", Carbon 3 throws InvalidFormatException for '0'.
     */
    public static function make(mixed $var): ?Carbon
    {
        if (is_string($var) && trim($var) === '0') {
            return Carbon::now();
        }

        return Carbon::make($var);
    }

    /**
     * Carbon 2's Carbon::parse(). Carbon 3 behaves the same except for the empty() times that are not
     * null or '': '0' and false were "now" in Carbon 2 ('0' throws in Carbon 3, false becomes 1970),
     * and for true.
     */
    public static function parse(mixed $time = null, mixed $timezone = null): Carbon
    {
        if ($time === '0' || $time === false) {
            return Carbon::now($timezone);
        }
        if ($time === true) {
            $time = '1'; // Carbon 2 parsed true as the time string "1" (an InvalidFormatException); Carbon 3 reads it as timestamp 1
        }

        return Carbon::parse($time, $timezone);
    }

    /** Carbon 2's $from->diffInDays($to, $absolute): whole days as an integer, absolute by default. */
    public static function diffInDays(DateTimeInterface $from, DateTimeInterface|string|null $to = null, bool $absolute = true): int
    {
        $timezone = $from->getTimezone();
        // Carbon 2 resolveCarbon(): empty → now, string → parsed in $from's timezone.
        $other = match (true) {
            ! $to => Carbon::now($timezone),
            is_string($to) => Carbon::parse($to, $timezone),
            default => $to,
        };
        // Carbon 2 moved the other date to $from's timezone, then used DateTime::diff().
        $interval = DateTimeImmutable::createFromInterface($from)
            ->diff(DateTimeImmutable::createFromInterface($other)->setTimezone($timezone), $absolute);

        return ($interval->invert ? -1 : 1) * (int) $interval->days;
    }
}
