<?php

// Deterministic date-difference cases for App\Support\LegacyCarbon (no mt_rand: its ranges differ across PHP versions).
// Returns [[from, fromTz, toKind, to, toTz], ...]; toKind: carbon | string | null.
return (static function (): array {
    $seed = 20261019;
    $next = static function (int $max) use (&$seed): int {
        $seed = ($seed * 1103515245 + 12345) % 2147483648;

        return $seed % ($max + 1);
    };
    $zones = ['Africa/Cairo', 'UTC', 'Asia/Riyadh', 'America/New_York', 'Asia/Dubai'];
    $times = ['00:00:00', '00:00:01', '01:30:00', '12:00:00', '23:59:59', '02:30:00'];
    $special = ['2024-02-28', '2024-02-29', '2024-03-01', '2023-04-27', '2023-04-28', '2023-10-26', '2023-10-27', '2024-12-31', '2025-01-01', '2026-10-19', '2000-02-29', '1999-12-31'];
    $date = static function () use ($next, $special): string {
        if ($next(3) === 0) {
            return $special[$next(count($special) - 1)];
        }

        return sprintf('%04d-%02d-%02d', 1995 + $next(45), 1 + $next(11), 1 + $next(27));
    };
    $cases = [];
    for ($i = 0; $i < 6000; $i++) {
        $fromTz = $zones[$next(count($zones) - 1)];
        $toTz = $next(2) === 0 ? $zones[$next(count($zones) - 1)] : $fromTz;
        $kind = ['carbon', 'carbon', 'carbon', 'string', 'null'][$next(4)];
        $cases[] = [$date().' '.$times[$next(count($times) - 1)], $fromTz, $kind, $date().' '.$times[$next(count($times) - 1)], $toTz];
    }

    return $cases;
})();
