<?php

namespace App\Excel;

use App\Excel\Sheets\AssetsSheet;
use App\Excel\Sheets\BalanceSheet;
use App\Excel\Sheets\CashFlowSheet;
use App\Excel\Sheets\CollectionsSheet;
use App\Excel\Sheets\CoverSheet;
use App\Excel\Sheets\DashboardSheet;
use App\Excel\Sheets\ExpenseSheet;
use App\Excel\Sheets\FacilitySheet;
use App\Excel\Sheets\IncomeSheet;
use App\Excel\Sheets\InvestmentSheet;
use App\Excel\Sheets\LoansSheet;
use App\Excel\Sheets\MeetingsSheet;
use App\Excel\Sheets\PurchasesSheet;
use App\Excel\Sheets\RevenueSheet;
use App\Excel\Sheets\RoomsSheet;
use App\Excel\Sheets\StartUpSheet;
use App\Excel\Sheets\StudySheet;
use App\Excel\Sheets\TaxesSheet;

// Builds the whole workbook of a study from its saved rows (see ModelData).
final class ModelBuilder
{
    public static function build(array $dump, string $lang = 'en'): Ctx
    {
        $x = new Ctx(new ModelData($dump), new Labels($lang));

        StudySheet::study($x);
        StudySheet::calendar($x);
        RoomsSheet::build($x);
        FacilitySheet::build($x, 'food');
        FacilitySheet::build($x, 'casino');
        FacilitySheet::build($x, 'other');
        MeetingsSheet::build($x);
        RevenueSheet::build($x);
        CollectionsSheet::build($x);
        foreach (array_keys(ExpenseSheet::DEPARTMENTS) as $dept) {
            ExpenseSheet::build($x, $dept);
        }
        StartUpSheet::build($x);
        InvestmentSheet::build($x);
        PurchasesSheet::build($x);
        LoansSheet::build($x);
        AssetsSheet::build($x);
        TaxesSheet::build($x);
        IncomeSheet::build($x);
        CashFlowSheet::build($x);
        BalanceSheet::build($x);
        DashboardSheet::build($x);
        CoverSheet::build($x);
        $x->wb->order(array_map(fn ($key) => $x->sheets[$key], CoverSheet::order($x)));

        return $x;
    }
}
