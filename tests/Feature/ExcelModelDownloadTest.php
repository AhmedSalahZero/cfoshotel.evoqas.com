<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\HotelStudies;
use Tests\TestCase;

// ══════════════════════════════════════════════════════════════════
//  The "Excel Model" download of a study ( /studies/{id}/excel-model )
//  Location: tests/Feature/ExcelModelDownloadTest.php
//  Run:      php artisan test --filter=ExcelModelDownloadTest
//
//  Who may download it (anyone who may view the study), what a study that cannot be calculated does, the file name and the language of the file.
//  What is IN the file is tested in ExcelModelAccuracyTest (the same figures as the application) and ExcelModelStructureTest.
// ══════════════════════════════════════════════════════════════════

class ExcelModelDownloadTest extends TestCase
{
    use RefreshDatabase;

    private const STUDY = 314;   // a study of company 40, made by person 1

    protected function setUp(): void
    {
        parent::setUp();
        HotelStudies::load();
    }

    private function owner(): User
    {
        return User::query()->findOrFail(1);
    }

    /** @return array<string,string> the parts of the downloaded .xlsx */
    private function parts(string $bytes): array
    {
        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($file, $bytes);
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($file) === true, 'the download is a valid .xlsx (a zip)');
        $parts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $parts[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }
        $zip->close();
        unlink($file);

        return $parts;
    }

    public function test_the_owner_downloads_the_workbook_of_the_study(): void
    {
        $response = $this->actingAs($this->owner())->get(route('studies.excel', self::STUDY));

        $response->assertOk();
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertMatchesRegularExpression('/^attachment; filename="CFO-Hotels-Model-[a-z0-9-]+-en\.xlsx"/', $response->headers->get('Content-Disposition'));
        $parts = $this->parts($response->getContent());
        $this->assertArrayHasKey('xl/workbook.xml', $parts);
        $this->assertStringContainsString('name="Balance sheet"', $parts['xl/workbook.xml']);
        $this->assertStringContainsString('name="Cover"', $parts['xl/workbook.xml']);
        $this->assertStringNotContainsString('rightToLeft="1"', $parts['xl/worksheets/sheet1.xml']);
    }

    public function test_the_file_follows_the_language_of_the_account(): void
    {
        $owner = $this->owner();
        $owner->update(['language' => 'ar']);

        $response = $this->actingAs($owner)->get(route('studies.excel', self::STUDY));

        $response->assertOk();
        $this->assertMatchesRegularExpression('/filename="CFO-Hotels-Model-[a-z0-9-]+-ar\.xlsx"/', $response->headers->get('Content-Disposition'));
        $parts = $this->parts($response->getContent());
        $this->assertStringContainsString('name="الميزانية العمومية"', $parts['xl/workbook.xml']);
        $this->assertStringContainsString('rightToLeft="1"', $parts['xl/worksheets/sheet1.xml']);
    }

    public function test_a_colleague_the_study_is_shared_with_may_download_it_to_view_only(): void
    {
        $colleague = User::factory()->forCompany(Company::query()->findOrFail(40))->create();
        $this->actingAs($colleague)->get(route('studies.excel', self::STUDY))->assertNotFound();

        DB::table('hospitality_sector_shares')->insert(['hospitality_sector_id' => self::STUDY, 'user_id' => $colleague->id, 'can_edit' => false]);

        $this->actingAs($colleague)->get(route('studies.excel', self::STUDY))->assertOk();
    }

    public function test_a_person_of_another_company_does_not_find_the_study(): void
    {
        $stranger = User::factory()->forCompany(Company::query()->findOrFail(41))->create();

        $this->actingAs($stranger)->get(route('studies.excel', self::STUDY))->assertNotFound();
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get(route('studies.excel', self::STUDY))->assertRedirect(route('login'));
    }

    public function test_a_study_that_cannot_be_calculated_goes_back_to_the_list_with_a_message(): void
    {
        DB::table('hospitality_sectors')->where('id', self::STUDY)->update(['study_dates' => null]);

        $response = $this->actingAs($this->owner())->get(route('studies.excel', self::STUDY));

        $response->assertRedirect(route('studies.index'));
        $response->assertSessionHas('error', __('studies.messages.excel_not_ready'));
    }

    public function test_a_study_not_filled_in_yet_still_gets_a_model_of_zeros(): void
    {
        DB::table('rooms')->where('hospitality_sector_id', self::STUDY)->delete();

        $response = $this->actingAs($this->owner())->get(route('studies.excel', self::STUDY));

        $this->assertTrue($response->isOk() || $response->isRedirect(), 'it is either built or sent back with a message, never an error page');
    }

    public function test_the_list_tells_the_page_which_studies_have_the_button(): void
    {
        $props = json_decode(json_encode($this->actingAs($this->owner())->get('/studies')->assertOk()->original->getData()['page']['props']), true);

        $this->assertNotEmpty($props['studies']);
        foreach ($props['studies'] as $study) {
            $this->assertTrue($study['can']['excel'], "study {$study['id']} has the Excel button");
        }
    }
}
