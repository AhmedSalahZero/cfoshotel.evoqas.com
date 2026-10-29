<?php

namespace App\Console\Commands;

use App\Excel\ModelBuilder;
use App\Excel\ModelDataBuilder;
use App\Models\HospitalitySector;
use Illuminate\Console\Command;

// Writes the Excel model of a study to a file, for checking it by hand (the download button is not wired yet: the model is built in layers,
// each proved against the application's engine by tests/Feature/ExcelModelAccuracyTest).
//   php artisan hotels:model-xlsx 6 /tmp/study-6.xlsx --lang=ar
class ExportModel extends Command
{
    protected $signature = 'hotels:model-xlsx {study : the study number} {path : where to write the .xlsx} {--lang=en : en or ar}';

    protected $description = 'Write the Excel model of a study to a file (work in progress: only the layers already proved are in it)';

    public function handle(): int
    {
        $study = HospitalitySector::query()->withoutGlobalScopes()->find((int) $this->argument('study'));
        if (! $study) {
            $this->error('There is no such study.');

            return self::FAILURE;
        }
        $study->bindDateTables();
        $lang = $this->option('lang') === 'ar' ? 'ar' : 'en';
        $bytes = ModelBuilder::build(ModelDataBuilder::fromStudy($study), $lang)->wb->toString();
        file_put_contents((string) $this->argument('path'), $bytes);
        $this->info(sprintf('%s: %d KB written.', $this->argument('path'), strlen($bytes) / 1024));

        return self::SUCCESS;
    }
}
