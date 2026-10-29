<?php

namespace Tests\Feature;

use App\Models\HospitalitySector;
use App\Services\Reports\FinancialReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\HotelStudies;
use Tests\TestCase;

// ══════════════════════════════════════════════════════════════════
//  The balance sheet must balance: "Check Error" (total investment - long term loans - owners equity) is zero in every month
//  Location: tests/Feature/BalanceSheetCheckTest.php
//  Run:      php artisan test --filter=BalanceSheetCheckTest
//
//  The five real studies pay their land and property in cash. Here the same studies are changed in the database (rolled back after each test)
//  to pay by installments, months apart, with loans on the purchase: the payments fall in different months, so the payable, the loan
//  withdrawals and the interest of the withdrawal period all have to follow them for the sheet to balance.
// ══════════════════════════════════════════════════════════════════

class BalanceSheetCheckTest extends TestCase
{
    use RefreshDatabase;

    /** The largest absolute Check Error over the months of the study (null when the study cannot be calculated). */
    private function worstCheckError(int $studyId): ?float
    {
        $study = HospitalitySector::query()->withoutGlobalScopes()->findOrFail($studyId);
        $study->bindDateTables();
        $sheet = app(FinancialReports::class)->balanceSheet($study);
        if ($sheet === null) {
            return null;
        }

        return max(array_map(fn ($value) => abs((float) $value), $sheet['balance_sheet_items']['Check Error']['Check Error']));
    }

    /** In the fixture the loans of study 313 still point at the purchase rows of study 306: attach them to its own rows. */
    private function attachLoansToItsOwnPurchases(int $studyId): void
    {
        $acquisition = DB::table('acquisitions')->where('hospitality_sector_id', $studyId)->value('id');
        $property = DB::table('property_acquisitions')->where('hospitality_sector_id', $studyId)->value('id');
        DB::table('loans')->where('hospitality_sector_id', $studyId)->whereIn('section_name', ['land', 'hard_cost_construction'])->update(['acquisition_id' => $acquisition]);
        DB::table('loans')->where('hospitality_sector_id', $studyId)->where('section_name', 'property_loan')->update(['acquisition_id' => $property]);
    }

    public function test_the_real_studies_balance_when_paid_in_cash(): void
    {
        HotelStudies::load();

        foreach ([306, 313, 314] as $id) {
            $worst = $this->worstCheckError($id);
            $this->assertNotNull($worst, "study {$id} cannot be calculated");
            $this->assertLessThan(1, $worst, "study {$id}");
        }
    }

    public function test_land_paid_by_installments_months_apart_with_a_loan_balances(): void
    {
        HotelStudies::load();
        $this->attachLoansToItsOwnPurchases(313);
        DB::table('acquisitions')->where('hospitality_sector_id', 313)->update([
            'land_purchase_cost' => '100000000', 'land_contingency_rate' => '2', 'purchase_date' => '0', 'land_payment_method' => 'installment',
            'first_land_down_payment_percentage' => '30', 'second_land_down_payment_percentage' => '20', 'land_after_month' => '6',
            'land_installment_count' => '4', 'installment_interval' => 'quarterly', 'land_equity_funding_rate' => '80',
        ]);
        DB::table('loans')->where('hospitality_sector_id', 313)->where('section_name', 'land')->update([
            'loan_type' => 'normal', 'period' => '60', 'base_rate' => '20', 'margin_rate' => '2', 'installment_interval' => 'monthly', 'grace_period' => '0',
        ]);

        $worst = $this->worstCheckError(313);

        $this->assertNotNull($worst);
        $this->assertLessThan(1, $worst);
    }

    public function test_property_paid_by_installments_months_apart_with_a_loan_balances(): void
    {
        HotelStudies::load();
        $this->attachLoansToItsOwnPurchases(313);
        DB::table('property_acquisitions')->where('hospitality_sector_id', 313)->update([
            'purchase_date' => '0', 'property_payment_method' => 'installment',
            'first_property_down_payment_percentage' => '30', 'second_property_down_payment_percentage' => '20', 'property_after_month' => '6',
            'property_installment_count' => '4', 'installment_interval' => 'quarterly', 'property_equity_funding_rate' => '70',
        ]);
        DB::table('loans')->where('hospitality_sector_id', 313)->where('section_name', 'property_loan')->update([
            'loan_type' => 'normal', 'period' => '60', 'base_rate' => '20', 'margin_rate' => '2', 'installment_interval' => 'monthly', 'grace_period' => '0',
        ]);

        $worst = $this->worstCheckError(313);

        $this->assertNotNull($worst);
        $this->assertLessThan(1, $worst);
    }

    public function test_the_stock_on_hand_at_opening_is_paid_in_cash_so_the_sheet_still_balances(): void
    {
        HotelStudies::load();
        // a rooms disposable line (1.8 % of the rooms revenue, 45 days of stock) that starts with 4M of stock on the shelf, bought 40 % cash and 60 % after 60 days
        DB::table('department_expenses')->insert([
            'model_name' => 'rooms', 'hospitality_sector_id' => 313, 'company_id' => 41, 'section_name' => 'disposable_expenses_rate', 'name' => 'Guest Consumables',
            'payload' => json_encode(array_fill_keys(range(1, 8), '1.8')), 'inventory_coverage_days' => 45, 'beginning_inventory_balance_value' => 4000000,
            'cash_payment_percentage' => 40, 'deferred_payment_percentage' => 60, 'due_days' => 60, 'start_up_cost' => 0, 'date' => 0,
        ]);

        $worst = $this->worstCheckError(313);

        $this->assertNotNull($worst);
        $this->assertLessThan(1, $worst);
    }
}
