<?php

namespace Tests\Feature;

use App\Support\StudyCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

// A kept result is used until study data is written, and never wrongly. Run:  php artisan test --filter=StudyCacheTest
class StudyCacheTest extends TestCase
{
    private const STUDY = 987654;

    protected function setUp(): void
    {
        parent::setUp();
        StudyCache::enable(true);
        StudyCache::bump();
    }

    protected function tearDown(): void
    {
        foreach (['a', 'b', 'objects', 'skipped'] as $name) {
            Cache::store('file')->forget('study-'.self::STUDY.'-'.$name);
        }
        StudyCache::enable(null);
        parent::tearDown();
    }

    /** @return array{0: \Closure, 1: \Closure}  the way to ask for the result, and the way to ask how many times it was really calculated */
    private function counted(string $name, ?\Closure $keep = null, mixed $value = ['x' => 1.5]): array
    {
        $counter = new \ArrayObject(['n' => 0]);   // an object, so every closure shares the same count
        $get = fn () => StudyCache::remember(self::STUDY, $name, function () use ($value, $counter) {
            $counter['n'] = $counter['n'] + 1;

            return $value;
        }, $keep);

        return [$get, fn () => $counter['n']];
    }

    public function test_the_second_call_uses_the_kept_result(): void
    {
        [$get, $calls] = $this->counted('a');
        $this->assertSame(['x' => 1.5], $get());
        $this->assertSame(['x' => 1.5], $get());
        $this->assertSame(1, $calls());
    }

    public function test_a_write_to_a_study_table_makes_it_calculate_again(): void
    {
        [$get, $calls] = $this->counted('a');
        $get();
        StudyCache::noteQuery('update `rooms` set `average_daily_rate` = ? where `id` = ?');
        $get();
        StudyCache::noteQuery('insert into `department_expenses` (`name`) values (?)');
        $get();
        StudyCache::noteQuery('delete from `loans` where `id` = ?');
        $get();
        $this->assertSame(4, $calls());
    }

    public function test_reading_or_writing_something_else_keeps_it(): void
    {
        [$get, $calls] = $this->counted('a');
        $get();
        StudyCache::noteQuery('select * from `rooms`');
        StudyCache::noteQuery('update `users` set `last_login_at` = ?');
        StudyCache::noteQuery('insert into `sessions` (`id`) values (?)');
        $get();
        $this->assertSame(1, $calls());
    }

    public function test_a_result_with_an_object_is_not_kept(): void
    {
        [$get, $calls] = $this->counted('objects', null, ['model' => new \stdClass]);
        $get();
        $get();
        $this->assertSame(2, $calls());
    }

    public function test_a_result_the_caller_does_not_want_kept_is_not_kept(): void
    {
        [$get, $calls] = $this->counted('skipped', fn ($data) => false);
        $get();
        $get();
        $this->assertSame(2, $calls());
    }

    public function test_numbers_come_back_exactly(): void
    {
        $value = ['a' => [1 => 0.1 + 0.2, 2 => 1234567.891234567, 3 => -0.0000123]];
        [$get] = $this->counted('b', null, $value);
        $first = $get();
        $second = $get();
        $this->assertSame($first, $second);
        $this->assertSame($value, $second);
    }

    public function test_it_is_off_for_the_other_tests_unless_switched_on(): void
    {
        StudyCache::enable(null);   // the normal rule: off while the tests run
        [$get, $calls] = $this->counted('a');
        $get();
        $get();
        $this->assertSame(2, $calls());
    }
}
