<?php

namespace Tests\Unit;

use App\Support\LegacyCarbon;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * App\Support\LegacyCarbon must give exactly Carbon 2's diffInDays() results, on whatever
 * Carbon version is installed. Contract: tests/Fixtures/carbon (generated with Carbon 2).
 */
class LegacyCarbonTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_make_and_parse_match_carbon_2_on_every_input(): void
    {
        $dir = dirname(__DIR__).'/Fixtures/carbon';
        $expected = json_decode((string) file_get_contents($dir.'/expected-parse-carbon2.json'), true)['results'];
        $previous = date_default_timezone_get();
        date_default_timezone_set('Africa/Cairo');
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00'));
        $describe = static function (callable $make): string {
            try {
                $date = $make();

                return $date === null ? 'null' : $date->format('Y-m-d H:i:s.u e');
            } catch (\Throwable $e) {
                return 'throws '.get_class($e);
            }
        };

        try {
            $differences = [];
            foreach (require $dir.'/parse-inputs.php' as $i => $input) {
                $actual = ['make' => $describe(fn () => LegacyCarbon::make($input)), 'parse' => $describe(fn () => LegacyCarbon::parse($input))];
                if ($actual !== $expected[$i]) {
                    $differences[] = var_export($input, true).': expected '.json_encode($expected[$i]).', got '.json_encode($actual);
                }
            }
        } finally {
            date_default_timezone_set($previous);
        }

        $this->assertCount(47, $expected);
        $this->assertSame([], $differences);
    }

    public function test_diff_in_days_matches_carbon_2_on_every_case(): void
    {
        $dir = dirname(__DIR__).'/Fixtures/carbon';
        $expected = json_decode((string) file_get_contents($dir.'/expected-carbon2.json'), true)['results'];
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Africa/Cairo'));

        $differences = [];
        foreach (require $dir.'/cases.php' as $i => [$from, $fromTz, $kind, $to, $toTz]) {
            $a = Carbon::parse($from, $fromTz);
            $b = match ($kind) {
                'carbon' => Carbon::parse($to, $toTz), 'string' => $to, 'null' => null
            };
            $actual = [LegacyCarbon::diffInDays($a, $b), LegacyCarbon::diffInDays($a, $b, false)];
            if ($actual !== $expected[$i]) {
                $differences[] = "#$i $from $fromTz -> $kind $to $toTz: expected ".json_encode($expected[$i]).', got '.json_encode($actual);
            }
        }

        $this->assertCount(6000, $expected);
        $this->assertSame([], array_slice($differences, 0, 10), count($differences).' differing cases');
    }
}
