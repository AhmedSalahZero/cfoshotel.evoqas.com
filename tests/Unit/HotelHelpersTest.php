<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

// The small helper functions of the hotel module (dates, arrays, totals) must give the very same answers as in the
// previous CFO Hotels. tests/Fixtures/hotel/helper_cases.json holds 76 inputs with the answers the OLD code gave.
// Run:  php artisan test --filter=HotelHelpersTest
// (It starts the application, because some helpers use the translator, e.g. getIntervalFormatted.)
class HotelHelpersTest extends TestCase
{
    private function decode(mixed $value): mixed
    {
        if (is_array($value)) {
            if (count($value) === 1 && isset($value['@carbon'])) {
                return Carbon::parse($value['@carbon']);
            }

            return array_map(fn ($item) => $this->decode($item), $value);
        }

        return $value;
    }

    public function test_every_helper_gives_the_same_answer_as_the_previous_application(): void
    {
        $fixture = json_decode((string) file_get_contents(dirname(__DIR__).'/Fixtures/hotel/helper_cases.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertGreaterThan(70, count($fixture['cases']));

        $failures = [];
        foreach ($fixture['cases'] as $i => $case) {
            $actual = json_encode(($case['function'])(...$this->decode($case['args'])), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

            if ($actual !== $case['expected']) {
                $failures[] = "#{$i} {$case['function']}: expected {$case['expected']}, got {$actual}";
            }
        }

        $this->assertSame([], $failures);
    }

    public function test_the_helpers_that_have_cases_exist(): void
    {
        $fixture = json_decode((string) file_get_contents(dirname(__DIR__).'/Fixtures/hotel/helper_cases.json'), true, 512, JSON_THROW_ON_ERROR);
        $covered = array_unique(array_column($fixture['cases'], 'function'));

        foreach ($covered as $function) {
            $this->assertTrue(function_exists($function), "{$function}() is missing");
        }

        // the two that need real rows are checked below, formatOptionsForSelect and getIdFromRowsWithTotal
        $this->assertTrue(function_exists('formatOptionsForSelect'));
        $this->assertTrue(function_exists('getIdFromRowsWithTotal'));
        $this->assertGreaterThan(42, count($covered));
    }

    public function test_the_constants_of_the_hotel_module_are_defined(): void
    {
        $this->assertSame(20, MAX_YEARS_COUNT);
        $this->assertSame('land', LAND_LOAN);
        $this->assertSame('ffe_cost', FFE_COST);
        $this->assertSame('hard_cost_construction', HARD_COST_CONSTRUCTION);
        $this->assertSame('property_loan', PROPERTY_LOAN);
        $this->assertSame('property_acquisition', PROPERTY_ACQUISITION);
        $this->assertSame('base_management_fees_as_percentage_from_revenues', BASE_MANAGEMENT_FEES_AS_PERCENTAGE_FROM_REVENUES);
        $this->assertSame('incentive_management_fees_as_percentage_from_revenues', INCENTIVE_MANAGEMENT_FEES_AS_PERCENTAGE_FROM_REVENUES);
    }

    public function test_options_for_a_select_are_made_from_getId_and_getName(): void
    {
        $items = new Collection([
            new class { public function getId() { return 7; } public function getName() { return 'Standard'; } },
            new class { public function getId() { return 9; } public function getName() { return 'Suite'; } },
        ]);

        $this->assertSame(
            json_encode([['value' => 7, 'title' => 'Standard'], ['value' => 9, 'title' => 'Suite']]),
            json_encode(formatOptionsForSelect($items)),
        );
    }

    public function test_the_total_row_is_found_by_its_title_or_else_by_its_position(): void
    {
        $english = new Collection([(object) ['id' => 4, 'title' => 'Total Rooms'], (object) ['id' => 5, 'title' => 'Suite']]);
        $arabic = new Collection([(object) ['id' => 8, 'title' => 'إجمالي الغرف'], (object) ['id' => 9, 'title' => 'جناح']]);

        $this->assertSame(4, getIdFromRowsWithTotal($english));
        $this->assertSame(8, getIdFromRowsWithTotal($arabic));
        $this->assertSame(0, getIdFromRowsWithTotal(new Collection));
    }
}
