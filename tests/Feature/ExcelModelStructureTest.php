<?php

namespace Tests\Feature;

use App\Excel\Labels;
use App\Excel\ModelBuilder;
use App\Excel\ModelDataBuilder;
use App\Models\HospitalitySector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\HotelStudies;
use Tests\TestCase;

// The workbook is sound whatever the study: names, protection, language, formulas. Run:  php artisan test --filter=ExcelModelStructureTest
class ExcelModelStructureTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string,string> the parts of the .xlsx (name => contents) */
    private function parts(string $bytes): array
    {
        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($file, $bytes);
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($file) === true, 'the workbook is a valid zip');
        $parts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $parts[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }
        $zip->close();
        unlink($file);

        return $parts;
    }

    public function test_every_label_has_english_and_arabic(): void
    {
        $words = (new Labels('en'))->all();
        $this->assertNotEmpty($words);
        foreach ($words as $key => [$english, $arabic]) {
            $this->assertNotSame('', trim($english), "$key has no English");
            $this->assertNotSame('', trim($arabic), "$key has no Arabic");
            $this->assertSame(substr_count($english, '%s'), substr_count($arabic, '%s'), "$key: the placeholders differ");
        }
    }

    public function test_a_missing_label_stops_the_build(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Labels('en'))->get('no.such.label');
    }

    public static function cases(): array
    {
        $cases = [];
        foreach ([305, 306, 307, 313, 314] as $id) {
            foreach (['en', 'ar'] as $lang) {
                $cases["study $id $lang"] = [$id, $lang];
            }
        }

        return $cases;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cases')]
    public function test_the_workbook_builds_and_is_sound(int $id, string $lang): void
    {
        HotelStudies::load();
        $study = HospitalitySector::query()->withoutGlobalScopes()->findOrFail($id);
        $study->bindDateTables();
        $x = ModelBuilder::build(ModelDataBuilder::fromStudy($study), $lang);
        $parts = $this->parts($x->wb->toString());
        $book = $parts['xl/workbook.xml'];

        // sheet names: at most 31 characters and no two the same
        preg_match_all('/<sheet name="([^"]*)"/u', $book, $m);
        $names = array_map(fn ($s) => html_entity_decode($s, ENT_QUOTES | ENT_XML1), $m[1]);
        $this->assertGreaterThan(5, count($names));
        $this->assertSame(count($names), count(array_unique(array_map('mb_strtolower', $names))), 'sheet names are unique');
        foreach ($names as $name) {
            $this->assertLessThanOrEqual(31, mb_strlen($name));
        }
        $this->assertStringContainsString('fullCalcOnLoad="1"', $book);

        // protected without a password, the input cells open; Arabic sheets run right to left
        $sheets = array_filter(array_keys($parts), fn ($k) => str_starts_with($k, 'xl/worksheets/sheet'));
        $this->assertCount(count($names), $sheets);
        $all = '';
        foreach ($sheets as $k) {
            $all .= $parts[$k];
        }
        $this->assertStringContainsString('<sheetProtection', $all);
        $this->assertStringNotContainsString('password=', strtolower($all));
        $this->assertStringNotContainsString('hashValue', $all);
        $this->assertSame($lang === 'ar', str_contains($all, 'rightToLeft="1"'));
        $this->assertStringContainsString('<protection locked="0"/>', $parts['xl/styles.xml'], 'input cells are open');

        // the tabs: the Cover first, then the dashboard, the calendar last; every sheet a formula points at exists
        $this->assertSame($lang === 'ar' ? 'الغلاف' : 'Cover', $names[0]);
        $this->assertSame($lang === 'ar' ? 'لوحة المؤشرات' : 'Dashboard', $names[1]);
        $this->assertSame($lang === 'ar' ? 'التقويم' : 'Calendar', end($names));
        preg_match_all("/'((?:[^']|'')+)'!/u", html_entity_decode($all, ENT_QUOTES | ENT_XML1), $refs);
        foreach (array_unique($refs[1]) as $ref) {
            $this->assertContains(str_replace("''", "'", $ref), $names, "a formula points at the sheet '$ref', which does not exist");
        }

        // no PHP left inside a formula, and every defined name used is defined
        preg_match_all('/<f>(.*?)<\/f>/su', $all, $f);
        $this->assertGreaterThan(500, count($f[1]));
        $defined = [];
        preg_match_all('/<definedName name="([^"]+)"/', $book, $d);
        foreach ($d[1] as $name) {
            $defined[$name] = true;
        }
        foreach ($f[1] as $formula) {
            $formula = html_entity_decode($formula, ENT_QUOTES | ENT_XML1);
            $this->assertDoesNotMatchRegularExpression('/\{\$|\$[a-z]\w*\(|\$[a-z]+\s*[-+*\/]/', $formula, $formula);
            $this->assertStringNotContainsString('Array', $formula);
            preg_match_all('/\bZ_[A-Za-z0-9_]+/', $formula, $z);
            foreach ($z[0] as $name) {
                $this->assertArrayHasKey($name, $defined, "$name is used but not defined");
            }
        }
    }
}
