<?php

namespace App\Models;

use App\Models\Concerns\HospitalitySectorAccess;
use App\Models\Concerns\HospitalitySectorAccessor;
use App\Models\Concerns\HospitalitySectorRelation;
use App\Models\Concerns\HospitalitySectorRevenueOnly;
use App\Models\Concerns\CompanyScope;
use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;

class HospitalitySector extends Model implements AllowsLazyLoading
{
    const STUDY = 'study';

    use CompanyScope, HospitalitySectorAccess, HospitalitySectorAccessor, HospitalitySectorRelation, HospitalitySectorRevenueOnly;

    // protected $with = [
    // 	'departmentExpenses'
    // ];
    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'operation_dates' => 'array',
        'study_dates' => 'array',
    ];

    /**
     * The accessors of the hotel models read the date tables of the study that is open
     * ('dateIndexWithDate', 'dateWithDateIndex' ...) from the application container. The old application filled them
     * in for every request that had a study in its address. Call this when a study is opened (a middleware does it
     * for the study screens), in a console command, or in a test.
     */
    public function bindDateTables(): static
    {
        $tables = $this->getDatesIndexesHelper();

        foreach (['datesIndexWithYearIndex', 'yearIndexWithYear', 'dateIndexWithDate', 'dateIndexWithMonthNumber', 'dateWithMonthNumber', 'dateWithDateIndex'] as $key) {
            app()->instance($key, $tables[$key]);
        }

        return $this;
    }
}
