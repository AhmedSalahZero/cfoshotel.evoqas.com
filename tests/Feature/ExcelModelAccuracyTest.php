<?php

namespace Tests\Feature;

use App\Excel\ModelBuilder;
use App\Excel\ModelDataBuilder;
use App\Models\HospitalitySector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\HotelStudies;
use Tests\Support\Recalc;
use Tests\TestCase;

// ══════════════════════════════════════════════════════════════════
//  The Excel model gives the same figures as the application
//  Location: tests/Feature/ExcelModelAccuracyTest.php
//  Run:      php artisan test --filter=ExcelModelAccuracyTest
//
//  The workbook of a study is built, LibreOffice recalculates it, and every line is compared with the application's own engine, month by
//  month. The test is skipped where LibreOffice (soffice) is not installed.
// ══════════════════════════════════════════════════════════════════

class ExcelModelAccuracyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Recalc::available()) {
            $this->markTestSkipped('LibreOffice (soffice) is not installed.');
        }
        HotelStudies::load();
    }

    /** @return array{0: \App\Excel\Ctx, 1: array, 2: HospitalitySector} */
    private function recalculated(int $studyId): array
    {
        $study = HospitalitySector::query()->withoutGlobalScopes()->findOrFail($studyId);
        $study->bindDateTables();
        $x = ModelBuilder::build(ModelDataBuilder::fromStudy($study), 'en');

        return [$x, Recalc::values($x->wb->toString()), $study];
    }

    /** Every month of the model's row must equal the engine's (a month the engine does not have is 0). */
    private function assertRow(array $sheets, \App\Excel\Ctx $x, string $key, array $engine, string $what, float $tolerance = 0.01): void
    {
        $model = Recalc::row($sheets, $x, $key);
        $worst = 0.0;
        $at = null;
        for ($i = 0; $i < $x->m->D; $i++) {
            $diff = abs($model[$i] - (float) ($engine[$i] ?? 0));
            if ($diff > $worst) {
                $worst = $diff;
                $at = $i;
            }
        }
        $this->assertLessThanOrEqual($tolerance, $worst, "$what: month $at is ".round($model[$at] ?? 0, 4).' in the workbook and '.round((float) ($engine[$at] ?? 0), 4).' in the application');
    }

    public static function studies(): array
    {
        return ['study 305' => [305], 'study 306' => [306], 'study 307' => [307], 'study 313' => [313], 'study 314' => [314]];
    }

    /** @dataProvider studies */
    #[\PHPUnit\Framework\Attributes\DataProvider('studies')]
    public function test_the_revenue_matches_the_application(int $id): void
    {
        [$x, $sheets, $study] = $this->recalculated($id);
        $engine = $study->calculateRoomRevenueAndGuestCount();

        $this->assertRow($sheets, $x, 'rm.nights', $engine['totalRoomsSoldNights'], 'room nights');
        $this->assertRow($sheets, $x, 'rm.rev', $engine['monthlyRevenuePerRoom']['total']['total'] ?? [], 'rooms revenue');
        $this->assertRow($sheets, $x, 'rm.guests', $engine['totalMonthlyGuestCountForRooms'], 'guests');
        $this->assertRow($sheets, $x, 'fb.rev', $engine['fAndBFacilityRevenue']['total'] ?? [], 'food & beverage revenue');
        $this->assertRow($sheets, $x, 'cs.rev', $engine['casinoFacilityRevenue']['total'] ?? [], 'gaming revenue');
        $this->assertRow($sheets, $x, 'mt.rev', $engine['meetingFacilityRevenue']['total'] ?? [], 'meeting spaces revenue');
        $this->assertRow($sheets, $x, 'ot.rev', $engine['otherRevenueFacilityRevenue']['total'] ?? [], 'other revenue');
    }

    /** @dataProvider studies */
    #[\PHPUnit\Framework\Attributes\DataProvider('studies')]
    public function test_the_collections_and_receivables_match_the_application(int $id): void
    {
        [$x, $sheets, $study] = $this->recalculated($id);
        $engine = $study->calculateRoomRevenueAndGuestCount()['collectionPoliciesAndReceivableEndBalances'];

        foreach (['rooms', 'foods', 'gaming', 'meetings', 'others'] as $stream) {
            $this->assertRow($sheets, $x, "col.c$stream", $engine['collection_policy'][$stream]['total'] ?? [], "collected from $stream");
            $this->assertRow($sheets, $x, "col.o$stream", $engine['receivable_end_balance'][$stream]['total']['intervalsReport']['monthly']['end_balance'] ?? [], "receivable of $stream");
        }
    }

    /** The number a sheet has in the file (the tabs are in the order of the Cover's contents, not the order they were built). */
    private function sheetNumber(string $bytes, string $name): int
    {
        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($file, $bytes);
        $zip = new \ZipArchive;
        $zip->open($file);
        $book = (string) $zip->getFromName('xl/workbook.xml');
        $zip->close();
        unlink($file);
        preg_match_all('/<sheet name="([^"]*)" sheetId="(\d+)"/u', $book, $found, PREG_SET_ORDER);
        foreach ($found as [, $sheetName, $id]) {
            if (html_entity_decode($sheetName, ENT_QUOTES | ENT_XML1) === $name) {
                return (int) $id;
            }
        }
        $this->fail("sheet $name is not in the workbook");
    }

    /** The workbook with one input cell changed, as a person would do in Excel (the value is written into the sheet's XML). */
    private function withCell(string $bytes, int $sheetNumber, string $ref, float $value): string
    {
        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($file, $bytes);
        $zip = new \ZipArchive;
        $zip->open($file);
        $name = "xl/worksheets/sheet{$sheetNumber}.xml";
        $xml = $zip->getFromName($name);
        $changed = preg_replace('/(<c r="'.$ref.'"[^>]*>)<v>[^<]*<\/v>/', '${1}<v>'.$value.'</v>', $xml, 1, $count);
        $this->assertSame(1, $count, "the cell $ref holds a number to change");
        $zip->addFromString($name, $changed);
        $zip->close();
        $out = (string) file_get_contents($file);
        unlink($file);

        return $out;
    }

    /** Changing an input cell of the workbook moves the revenue exactly as changing it in the application does. */
    public function test_changing_cells_moves_the_revenue_like_the_application(): void
    {
        $study = HospitalitySector::query()->withoutGlobalScopes()->findOrFail(313);
        $study->bindDateTables();
        $x = ModelBuilder::build(ModelDataBuilder::fromStudy($study), 'en');
        $bytes = $x->wb->toString();
        $sheetNumber = $this->sheetNumber($bytes, $x->sheets['rm']->name);

        // the number of rooms of the first type
        $countCell = preg_replace('/^.*!\$?([A-Z]+)\$?(\d+)$/', '$1$2', $x->name('rm.count0'));
        $room = $study->rooms()->orderBy('id')->first();
        $changedBytes = $this->withCell($bytes, $sheetNumber, $countCell, $room->room_count + 25);
        // the occupancy of the first year of operation (general occupancy, or the first room's own)
        $occKey = isset($x->rows['rm.occ']) ? 'rm.occ' : 'rm.occ0';
        $yearIdx = intdiv($x->m->opIdx, 12);
        $occRef = \App\Excel\Sheet::col(\App\Excel\Ctx::FIRST + $yearIdx).$x->rows[$occKey]['row'];
        $currentOcc = 55.0;
        $changedBytes = $this->withCell($changedBytes, $sheetNumber, $occRef, $currentOcc);
        $model = Recalc::values($changedBytes);

        // the same two changes in the application
        $room->update(['room_count' => $room->room_count + 25]);
        if ($occKey === 'rm.occ') {
            $general = json_decode((string) $study->general_occupancy_rate, true);
            $general[$yearIdx] = (string) $currentOcc;
            $study->update(['general_occupancy_rate' => json_encode($general)]);
        } else {
            $per = json_decode((string) $room->occupancy_rate_per_room, true);
            $per[$yearIdx] = (string) $currentOcc;
            $room->update(['occupancy_rate_per_room' => json_encode($per)]);
        }
        $fresh = HospitalitySector::query()->withoutGlobalScopes()->findOrFail(313);
        $fresh->bindDateTables();
        $engine = $fresh->calculateRoomRevenueAndGuestCount();

        $this->assertRow($model, $x, 'rm.nights', $engine['totalRoomsSoldNights'], 'room nights after the change');
        $this->assertRow($model, $x, 'rm.rev', $engine['monthlyRevenuePerRoom']['total']['total'] ?? [], 'rooms revenue after the change');
        $this->assertRow($model, $x, 'fb.rev', $engine['fAndBFacilityRevenue']['total'] ?? [], 'food & beverage revenue follows the guests');
    }

    /** Every kind of collection terms at once: general / per item, customised / system default, every interval. */
    public function test_every_kind_of_collection_terms_matches_the_application(): void
    {
        $id = 313;
        DB::table('hospitality_sectors')->where('id', $id)->update([
            'room_collection_policy_type' => 'general_collection_terms',
            'rooms_general_collection_policy_type' => 'customize',
            'rooms_general_collection_policy_value' => json_encode(['rate' => ['40', '35', '25', '0'], 'due_in_days' => ['0', '75', '120', '0']]),
            'food_collection_policy_type' => 'general_collection_terms',
            'foods_general_collection_policy_type' => 'system_default',
            'foods_general_collection_policy_interval' => 'quarterly',
            'casino_collection_policy_type' => 'terms_per_sales_channel',
            'meeting_collection_policy_type' => 'terms_per_sales_channel',
            'other_collection_policy_type' => 'general_collection_terms',
            'others_general_collection_policy_type' => 'system_default',
            'others_general_collection_policy_interval' => 'semi-annually',
        ]);
        DB::table('casinos')->where('hospitality_sector_id', $id)->update(['collection_policy_type' => 'customize', 'collection_policy_value' => json_encode(['rate' => ['50', '50', '0', '0'], 'due_in_days' => ['29', '31', '0', '0']])]);
        DB::table('meetings')->where('hospitality_sector_id', $id)->update(['collection_policy_type' => 'system_default', 'collection_policy_interval' => 'annually']);
        DB::table('foods')->where('hospitality_sector_id', $id)->update(['collection_policy_type' => 'customize']);

        [$x, $sheets, $study] = $this->recalculated($id);
        $engine = $study->calculateRoomRevenueAndGuestCount()['collectionPoliciesAndReceivableEndBalances'];

        foreach (['rooms', 'foods', 'gaming', 'meetings', 'others'] as $stream) {
            $this->assertRow($sheets, $x, "col.c$stream", $engine['collection_policy'][$stream]['total'] ?? [], "collected from $stream");
            $this->assertRow($sheets, $x, "col.o$stream", $engine['receivable_end_balance'][$stream]['total']['intervalsReport']['monthly']['end_balance'] ?? [], "receivable of $stream");
        }
    }

    /** Every expense line, the stock and supplier payables of the disposables, and the prepaid expenses of the fixed lines. */
    private function assertExpenses(array $sheets, \App\Excel\Ctx $x, array $engine): void
    {
        $names = ['Rooms Direct Expenses' => 'rooms', 'Foods Direct Expenses' => 'foods', 'Gaming Direct Expenses' => 'gaming', 'Meetings Direct Expenses' => 'meetings',
            'Other Revenue Direct Expenses' => 'others', 'General And Administrative Expenses' => 'general', 'Sales And Marketing Expenses' => 'marketing',
            'Property Expenses' => 'property', 'Energy Expenses' => 'energy'];
        $lines = 0;
        foreach (['directExpenses', 'Undistributed Operating Expenses'] as $group) {
            foreach ($engine[$group] ?? [] as $name => $sections) {
                $sheet = \App\Excel\Sheets\ExpenseSheet::DEPARTMENTS[$names[$name]]['sheet'];
                foreach ($sections as $section => $byId) {
                    foreach ($byId as $id => $series) {
                        if (is_numeric($id)) {
                            $this->assertRow($sheets, $x, "$sheet.ex$id", $series, "$name / $section / line $id");
                            $lines++;
                        }
                    }
                }
            }
        }
        $this->assertGreaterThanOrEqual(0, $lines);   // a study (305) may have no expense lines at all

        foreach (['rooms' => 'xr', 'foods' => 'xf', 'gaming' => 'xg'] as $stream => $sheet) {
            $this->assertRow($sheets, $x, "$sheet.t_inv", $engine['inventoryStatements'][$stream]['total']['monthly']['end_balance'] ?? [], "stock of $stream");
            $this->assertRow($sheets, $x, "$sheet.t_owed", $engine['disposablePaymentStatements'][$stream]['total']['monthly']['end_balance'] ?? [], "owed to suppliers for $stream");
        }
        foreach (['General' => 'xe', 'Marketing' => 'xk', 'Property' => 'xp', 'Energy' => 'xn'] as $name => $sheet) {
            $this->assertRow($sheets, $x, "$sheet.t_fbal", $engine["prepaidExpenseStatementFor{$name}ForView"]['total']['monthly']['end_balance'] ?? [], "prepaid $name expenses");
        }
    }

    /** @dataProvider studies */
    #[\PHPUnit\Framework\Attributes\DataProvider('studies')]
    public function test_the_expenses_match_the_application(int $id): void
    {
        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertExpenses($sheets, $x, $study->calculateRoomRevenueAndGuestCount());
    }

    /** Disposables with an opening stock, coverage days, cash and deferred payment, and fixed lines paid every quarter / half year / year. */
    public function test_disposables_and_prepaid_expenses_of_every_kind_match_the_application(): void
    {
        $id = 313;
        $now = now()->toDateTimeString();
        $payload = json_encode(array_fill_keys(range(1, 9), '2.5'));
        $study = HospitalitySector::query()->withoutGlobalScopes()->findOrFail($id);
        $atOpening = round(25000 * pow(1.05, $study->getDiffBetweenOperationStartDateAndStudyStartDate()), 8);   // as the application saves it
        $row = fn (string $model, string $name, array $extra) => $extra + ['model_name' => $model, 'hospitality_sector_id' => $id, 'company_id' => 41, 'name' => $name,
            'created_at' => $now, 'updated_at' => $now, 'start_up_cost' => 0, 'date' => 0];
        foreach (['rooms' => [45, 40, 60, 3000000], 'foods' => [30, 100, 0, 0], 'gaming' => [7, 50, 30, 500000]] as $model => [$coverage, $cash, $due, $opening]) {
            DB::table('department_expenses')->insert($row($model, "Consumables of $model", [
                'section_name' => 'disposable_expenses_rate', 'payload' => $payload, 'inventory_coverage_days' => $coverage, 'beginning_inventory_balance_value' => $opening,
                'cash_payment_percentage' => $cash, 'deferred_payment_percentage' => 100 - $cash, 'due_days' => $due,
            ]));
        }
        foreach (['general' => 'quarterly', 'marketing' => 'semi-annually', 'energy' => 'annually', 'property' => 'monthly'] as $model => $terms) {
            DB::table('department_expenses')->insert($row($model, "Fixed cost of $model", [
                'section_name' => 'fixed_monthly_expenses', 'expense_per_night_sold' => 25000, 'night_expense_escalation_rate' => 5, 'guest_expense_at_operation_date' => $atOpening,
                'guest_annual_escalation_rate' => 4, 'chosen_night_expense_currency' => 1, 'opex_payment_terms' => $terms,
            ]));
        }

        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertExpenses($sheets, $x, $study->calculateRoomRevenueAndGuestCount());
    }

    private function assertStartUp(array $sheets, \App\Excel\Ctx $x, array $engine): void
    {
        $totals = $engine['startUpAndPreOperationExpensesTotals'] ?? [];
        foreach (['payments' => 'payments', 'total_monthly_depreciation' => 'dep', 'payable_end_balance' => 'payable', 'final_total_gross' => 'gross', 'accumulated_depreciation' => 'accum', 'end_balance' => 'net'] as $key => $row) {
            $this->assertRow($sheets, $x, "su.$row", $totals[$key] ?? [], "start-up costs: $key");
        }
    }

    /** @dataProvider studies */
    #[\PHPUnit\Framework\Attributes\DataProvider('studies')]
    public function test_the_start_up_costs_match_the_application(int $id): void
    {
        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertStartUp($sheets, $x, $study->calculateRoomRevenueAndGuestCount());
    }

    /** Start-up costs before and after the opening, paid in cash and later, and monthly pre-operating expenses. */
    public function test_start_up_costs_of_every_kind_match_the_application(): void
    {
        $id = 313;
        $now = now()->toDateTimeString();
        $row = fn (string $section, string $name, array $extra) => $extra + ['model_name' => 'start', 'section_name' => $section, 'hospitality_sector_id' => $id, 'company_id' => 41,
            'name' => $name, 'created_at' => $now, 'updated_at' => $now];
        foreach ([[8, 5000000, 100, 0, 0], [20, 12000000, 40, 60, 75], [30, 3000000, 50, 50, 29], [26, 2000000, 0, 100, 45]] as $n => [$date, $cost, $cash, $deferred, $due]) {
            DB::table('department_expenses')->insert($row('start_up_cost', "Start-up $n", ['start_up_cost' => $cost, 'date' => $date, 'cash_payment_percentage' => $cash, 'deferred_payment_percentage' => $deferred, 'due_days' => $due]));
        }
        DB::table('department_expenses')->insert($row('pre_operating_expense', 'Pre-opening payroll', ['start_up_cost' => 0, 'date' => 0, 'manpower_payload' => json_encode(array_map('strval', array_merge(array_fill(0, 10, 0), array_fill(10, 14, 400000))))]));

        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertStartUp($sheets, $x, $study->calculateRoomRevenueAndGuestCount());
    }

    /** Construction, soft costs and FF&E: the work, the payments, and how they are funded. */
    private function assertInvestment(array $sheets, \App\Excel\Ctx $x, array $engine): void
    {
        $cash = $engine['CashOutReport']['Acquisition And Development Payment'] ?? [];
        $in = $engine['CashInReport'] ?? [];
        foreach (['hc' => ['Hard Construction', 'hardConstructionExecutionAndPayment'], 'sc' => ['Soft Construction', 'softConstructionExecutionAndPayment'], 'fe' => ['FFE', 'ffeExecutionAndPayment']] as $key => [$name, $execKey]) {
            if (! isset($x->rows["inv.{$key}ex"])) {
                $this->assertSame([], $cash["$name Payment"] ?? [], "$name has no sheet rows but the application pays for it");

                continue;
            }
            $this->assertRow($sheets, $x, "inv.{$key}ex", $engine[$execKey] ?? [], "$name work");
            $this->assertRow($sheets, $x, "inv.{$key}pay", $cash["$name Payment"] ?? [], "$name payments");
            $this->assertRow($sheets, $x, "inv.{$key}eq", $in['Equity Injection']["$name Equity Injection"] ?? [], "$name equity");
            $this->assertRow($sheets, $x, "inv.{$key}ln", $in['Loan Withdrawal']["$name Loan Withdrawal"] ?? [], "$name loan withdrawal");
        }
    }

    /** @dataProvider studies */
    #[\PHPUnit\Framework\Attributes\DataProvider('studies')]
    public function test_the_construction_and_ffe_match_the_application(int $id): void
    {
        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertInvestment($sheets, $x, $study->calculateRoomRevenueAndGuestCount());
    }

    /** Every execution method, down payments, two payment shares, and equity funding below 100 %. */
    public function test_construction_methods_and_funding_of_every_kind_match_the_application(): void
    {
        $id = 313;
        DB::table('acquisitions')->where('hospitality_sector_id', $id)->update([
            'hard_construction_cost' => '80000000', 'hard_construction_contingency_rate' => '5', 'hard_construction_start_date' => '4', 'hard_construction_duration' => '14',
            'hard_execution_method' => 'straight-line', 'hard_down_payment' => '15', 'hard_balance_rate_one' => '50', 'hard_due_one' => '45', 'hard_balance_rate_two' => '35', 'hard_due_two' => '90',
            'hard_equity_funding' => '35',
            'soft_construction_cost' => '9000000', 'soft_construction_contingency_rate' => '3', 'soft_construction_start_date' => '2', 'soft_construction_duration' => '16',
            'soft_execution_method' => 'steady-decline', 'soft_down_payment' => '20', 'soft_balance_rate_one' => '40', 'soft_due_one' => '30', 'soft_balance_rate_two' => '40', 'soft_due_two' => '60',
        ]);
        DB::table('ffe')->where('hospitality_sector_id', $id)->update([
            'start_date' => '6', 'duration' => '9', 'execution_method' => 's-curve', 'down_payment' => '25', 'balance_rate_one' => '40', 'due_one' => '60', 'balance_rate_two' => '35', 'due_two' => '90',
            'ffe_equity_funding' => '45',
        ]);

        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertInvestment($sheets, $x, $study->calculateRoomRevenueAndGuestCount());
    }

    /** The two shares of the work paid far apart (30 and 120 days): the months are not paid for in calendar order, and the equity follows the application's order. */
    public function test_payment_shares_far_apart_are_funded_in_the_applications_order(): void
    {
        $id = 313;
        DB::table('acquisitions')->where('hospitality_sector_id', $id)->update([
            'hard_construction_cost' => '80000000', 'hard_construction_contingency_rate' => '5', 'hard_construction_start_date' => '4', 'hard_construction_duration' => '14',
            'hard_execution_method' => 'straight-line', 'hard_down_payment' => '15', 'hard_balance_rate_one' => '50', 'hard_due_one' => '30', 'hard_balance_rate_two' => '35', 'hard_due_two' => '120',
            'hard_equity_funding' => '35',
        ]);
        DB::table('ffe')->where('hospitality_sector_id', $id)->update([
            'start_date' => '6', 'duration' => '9', 'execution_method' => 'steady-growth', 'down_payment' => '10', 'balance_rate_one' => '30', 'due_one' => '0', 'balance_rate_two' => '60', 'due_two' => '150',
            'ffe_equity_funding' => '40',
        ]);

        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertInvestment($sheets, $x, $study->calculateRoomRevenueAndGuestCount());
    }

    /** The purchase of the land and of the property: when it is paid and how it is funded. */
    private function assertPurchases(array $sheets, \App\Excel\Ctx $x, array $engine): void
    {
        $cash = $engine['CashOutReport']['Acquisition And Development Payment'] ?? [];
        $in = $engine['CashInReport'] ?? [];
        foreach (['ld' => 'Land', 'pp' => 'Property'] as $key => $name) {
            if (! isset($x->rows["pur.{$key}pay"])) {
                $this->assertSame([], $cash["$name Payments"] ?? [], "$name has no sheet rows but the application pays for it");

                continue;
            }
            $this->assertRow($sheets, $x, "pur.{$key}pay", $cash["$name Payments"] ?? [], "$name payments");
            $this->assertRow($sheets, $x, "pur.{$key}eq", $in['Equity Injection']["$name Equity Injection"] ?? [], "$name equity");
            $this->assertRow($sheets, $x, "pur.{$key}ln", $in['Loan Withdrawal']["$name Loan Withdrawal"] ?? [], "$name loan withdrawal");
        }
    }

    /** @dataProvider studies */
    #[\PHPUnit\Framework\Attributes\DataProvider('studies')]
    public function test_the_land_and_property_purchases_match_the_application(int $id): void
    {
        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertPurchases($sheets, $x, $study->calculateRoomRevenueAndGuestCount());
    }

    public static function purchaseMethods(): array
    {
        return [
            'installments every month' => ['installment', 'monthly', 4, 6],
            'installments every quarter' => ['installment', 'quarterly', 4, 6],
            'installments every half year' => ['installment', 'semi-annually', 3, 0],
            'installments every year' => ['installment', 'annually', 2, 4],
            'custom shares' => ['customize', '0', 1, 0],
        ];
    }

    /** Land and property paid in every way, with a loan for part of the cost. */
    #[\PHPUnit\Framework\Attributes\DataProvider('purchaseMethods')]
    public function test_land_and_property_paid_in_every_way_match_the_application(string $method, string $interval, int $count, int $after): void
    {
        $id = 313;
        $custom = json_encode(['rate' => ['30', '30', '25', '15'], 'due_in_days' => ['0', '45', '90', '150']]);
        DB::table('acquisitions')->where('hospitality_sector_id', $id)->update([
            'land_purchase_cost' => '90000000', 'land_contingency_rate' => '2', 'purchase_date' => '2', 'land_payment_method' => $method,
            'first_land_down_payment_percentage' => '30', 'second_land_down_payment_percentage' => '20', 'land_after_month' => (string) $after, 'land_installment_count' => (string) $count,
            'installment_interval' => $interval, 'land_equity_funding_rate' => '60', 'collection_policy_value' => $custom,
        ]);
        DB::table('property_acquisitions')->where('hospitality_sector_id', $id)->update([
            'property_purchase_cost' => '50000000', 'property_contingency_rate' => '1', 'purchase_date' => '1', 'property_payment_method' => $method,
            'first_property_down_payment_percentage' => '25', 'second_property_down_payment_percentage' => '15', 'property_after_month' => (string) $after, 'property_installment_count' => (string) $count,
            'installment_interval' => $interval, 'property_equity_funding_rate' => '40', 'collection_policy_value' => $custom,
        ]);
        $this->fitBreakdown($id);

        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertPurchases($sheets, $x, $study->calculateRoomRevenueAndGuestCount());
    }

    // ── loans ──

    /** A variant that changes the cost of the property must change the breakdown of its cost (land, building, FF&E) with it, or the study is inconsistent and its balance sheet cannot balance. */
    private function fitBreakdown(int $id): void
    {
        $property = DB::table('property_acquisitions')->where('hospitality_sector_id', $id)->first();
        $total = (float) $property->property_purchase_cost * (1 + (float) $property->property_contingency_rate / 100);
        foreach (DB::table('property_acquisition_breakdowns')->where('hospitality_sector_id', $id)->get() as $row) {
            DB::table('property_acquisition_breakdowns')->where('id', $row->id)->update(['item_amount' => (string) ($total * (float) $row->property_cost_percentage / 100)]);
        }
    }

    /** In the fixture the loans still point at purchase rows of other studies: attach them to the study's own rows (and the FF&E loan to its FF&E). */
    private function attachLoans(int $id): void
    {
        $acquisition = DB::table('acquisitions')->where('hospitality_sector_id', $id)->value('id');
        $property = DB::table('property_acquisitions')->where('hospitality_sector_id', $id)->value('id');
        $ffe = DB::table('ffe')->where('hospitality_sector_id', $id)->value('id');
        DB::table('loans')->where('hospitality_sector_id', $id)->whereIn('section_name', ['land', 'hard_cost_construction'])->update(['acquisition_id' => $acquisition]);
        DB::table('loans')->where('hospitality_sector_id', $id)->where('section_name', 'property_loan')->update(['acquisition_id' => $property]);
        DB::table('loans')->where('hospitality_sector_id', $id)->where('section_name', 'ffe_cost')->update(['ffe_id' => $ffe]);
    }

    /** What the application worked out for the loans of the study (the same call the statements make). */
    private function engineLoans(HospitalitySector $study): array
    {
        return (new \App\Calculations\CalculateFixedLoanAtEndService($study))->calculateFixedAssetsLoans($study, $study->getDatesAsStringAndIndex(), app('dateIndexWithDate'), app('dateWithDateIndex'));
    }

    /** The months of an array keyed by month number, with a month that has no value taking the last one before it. @return array<int,float> */
    private function filledForward(array $values, int $months): array
    {
        $out = [];
        $last = 0.0;
        for ($i = 0; $i < $months; $i++) {
            $last = isset($values[$i]) ? (float) $values[$i] : $last;
            $out[$i] = $last;
        }

        return $out;
    }

    /** The balance of a loan as the balance sheet takes it: the drawing balance until the schedule starts, then the schedule's balance. */
    private function loanBalance(array $endBalance, array $withdrawal, int $months): array
    {
        $schedule = $this->filledForward($endBalance, $months);
        $start = array_key_last($withdrawal);
        array_pop($withdrawal);
        $drawing = array_fill(0, $months, 0.0);
        if ($withdrawal !== [] && $start !== null) {
            $previous = 0.0;
            foreach (range(array_key_first($withdrawal), $start - 1) as $month) {
                $previous = (float) ($withdrawal[$month] ?? $previous);
                $drawing[$month] = $previous;
            }
        }
        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $out[$i] = $drawing[$i] + $schedule[$i];
        }

        return $out;
    }

    private function assertLoans(array $sheets, \App\Excel\Ctx $x, array $engine): void
    {
        $names = [
            'pp' => ['property', 'Property Loan Installment', 'propertyLoanInstallment'],
            'ld' => ['land', 'Land Loan Installment', 'landLoanInstallment'],
            'hc' => ['hardConstruction', 'Hard Construction Loan Installment', 'hardConstructionLoanInstallment'],
            'fe' => ['ffe', 'FFE Loan Installment', 'ffeLoanInstallment'],
        ];
        $drawing = ['pp' => 'propertyLoanWithdrawalEndBalance', 'ld' => 'landLoanWithdrawalEndBalance', 'hc' => 'hardLoanWithdrawalEndBalance', 'fe' => 'ffeLoanWithdrawalEndBalance'];
        $found = 0;
        foreach ($names as $key => [$prefix, $installmentName, $installmentKey]) {
            if (! isset($x->rows["ln.{$key}int"])) {
                continue;
            }
            $found++;
            $interest = $engine[$prefix.'LoanInterestAmounts'] ?? [];
            $this->assertRow($sheets, $x, "ln.{$key}int", $interest, "$prefix loan interest");
            $this->assertRow($sheets, $x, "ln.{$key}sp", $engine[$installmentKey][$installmentName] ?? [], "$prefix loan installments");
            $endBalance = $engine[$prefix.'LoanEndBalance'] ?? [];
            $this->assertRow($sheets, $x, "ln.{$key}bal", $this->loanBalance($endBalance, $engine[$drawing[$key]] ?? [], $x->m->D), "$prefix loan balance", 0.05);
            $amount = Recalc::row($sheets, $x, "ln.{$key}wc")[$x->m->D - 1];
            $this->assertEqualsWithDelta((float) ($engine[['pp' => 'propertyLoanAmount', 'ld' => 'landLoanAmount', 'hc' => 'hardLoanAmount', 'fe' => 'ffeLoanAmount'][$key]] ?? 0), $amount, 0.05, "$prefix loan amount");
        }
        if (isset($x->rows['ln.ldcap'])) {
            $this->assertRow($sheets, $x, 'ln.ldcap', $engine['landLoanCapitalizedInterest'] ?? [], 'land loan interest before opening');
        }

        return;
    }

    /** @dataProvider studies */
    #[\PHPUnit\Framework\Attributes\DataProvider('studies')]
    public function test_the_loans_match_the_application(int $id): void
    {
        $this->attachLoans($id);
        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertLoans($sheets, $x, $this->engineLoans($study));
    }

    public static function loanVariants(): array
    {
        // [type, months between installments, tenor, grace, step rate, step interval] for the land, construction, FF&E and property loans
        return [
            'plain, step-up, grace paying interest, step-down' => [[
                ['normal', 'monthly', 60, 0, 0, 'annually'], ['step-up', 'quartly', 96, 0, 5, 'annually'],
                ['grace_period_without_capitalization', 'semi annually', 60, 6, 0, 'annually'], ['step-down', 'monthly', 84, 0, 4, 'quartly'],
            ], 'installment'],
            'capitalised grace and long grace' => [[
                ['grace_step-up_with_capitalization', 'quartly', 60, 8, 6, 'semi annually'], ['grace_step-down_without_capitalization', 'monthly', 72, 5, 3, 'annually'],
                ['normal', 'semi annually', 48, 14, 0, 'annually'], ['grace_step-up_without_capitalization', 'quartly', 36, 3, 5, 'quartly'],
            ], 'cash'],
            'steps every few installments' => [[
                ['step-up', 'monthly', 60, 0, 5, 'semi annually'], ['step-down', 'semi annually', 96, 0, 7, 'semi annually'],
                ['grace_step-down_with_capitalization', 'monthly', 60, 12, 5, 'quartly'], ['normal', 'quartly', 60, 0, 0, 'annually'],
            ], 'installment'],
        ];
    }

    /** Loans of every type, interval, grace and step, with the cost drawn from them over several months. */
    #[\PHPUnit\Framework\Attributes\DataProvider('loanVariants')]
    public function test_loans_of_every_kind_match_the_application(array $loans, string $landMethod): void
    {
        $id = 313;
        $this->attachLoans($id);
        DB::table('acquisitions')->where('hospitality_sector_id', $id)->update([
            'land_purchase_cost' => '90000000', 'land_contingency_rate' => '2', 'purchase_date' => '2', 'land_payment_method' => $landMethod,
            'first_land_down_payment_percentage' => '30', 'second_land_down_payment_percentage' => '20', 'land_after_month' => '6', 'land_installment_count' => '4',
            'installment_interval' => 'quarterly', 'land_equity_funding_rate' => '40',
            'hard_construction_cost' => '80000000', 'hard_construction_contingency_rate' => '5', 'hard_construction_start_date' => '4', 'hard_construction_duration' => '14',
            'hard_execution_method' => 'straight-line', 'hard_down_payment' => '15', 'hard_balance_rate_one' => '50', 'hard_due_one' => '30', 'hard_balance_rate_two' => '35', 'hard_due_two' => '60',
            'hard_equity_funding' => '35',
        ]);
        DB::table('ffe')->where('hospitality_sector_id', $id)->update([
            'start_date' => '6', 'duration' => '9', 'execution_method' => 's-curve', 'down_payment' => '25', 'balance_rate_one' => '40', 'due_one' => '60', 'balance_rate_two' => '35', 'due_two' => '90',
            'ffe_equity_funding' => '45',
        ]);
        DB::table('property_acquisitions')->where('hospitality_sector_id', $id)->update([
            'property_purchase_cost' => '50000000', 'property_contingency_rate' => '1', 'purchase_date' => '1', 'property_payment_method' => 'installment',
            'first_property_down_payment_percentage' => '25', 'second_property_down_payment_percentage' => '15', 'property_after_month' => '3', 'property_installment_count' => '3',
            'installment_interval' => 'quarterly', 'property_equity_funding_rate' => '40',
        ]);
        foreach (['land', 'hard_cost_construction', 'ffe_cost', 'property_loan'] as $n => $section) {
            [$type, $interval, $tenor, $grace, $step, $stepInterval] = $loans[$n];
            DB::table('loans')->where('hospitality_sector_id', $id)->where('section_name', $section)->update([
                'loan_type' => $type, 'installment_interval' => $interval, 'period' => (string) $tenor, 'grace_period' => (string) $grace, 'base_rate' => '18', 'margin_rate' => '2.5',
                'step_up_rate' => (string) $step, 'step_down_rate' => (string) $step, 'step_up_interval' => $stepInterval, 'step_down_interval' => $stepInterval,
            ]);
        }
        if (! DB::table('loans')->where('hospitality_sector_id', $id)->where('section_name', 'property_loan')->exists()) {
            $template = (array) DB::table('loans')->where('hospitality_sector_id', $id)->where('section_name', 'land')->first();
            unset($template['id']);
            [$type, $interval, $tenor, $grace, $step, $stepInterval] = $loans[3];
            DB::table('loans')->insert(array_merge($template, [
                'section_name' => 'property_loan', 'acquisition_id' => DB::table('property_acquisitions')->where('hospitality_sector_id', $id)->value('id'),
                'loan_type' => $type, 'installment_interval' => $interval, 'period' => (string) $tenor, 'grace_period' => (string) $grace, 'step_up_rate' => (string) $step,
                'step_down_rate' => (string) $step, 'step_up_interval' => $stepInterval, 'step_down_interval' => $stepInterval,
            ]));
        }

        $this->fitBreakdown($id);
        [$x, $sheets, $study] = $this->recalculated($id);
        $engine = $this->engineLoans($study);

        $this->assertLoans($sheets, $x, $engine);
        $this->assertIncome($sheets, $x, $study);
        $this->assertStatements($sheets, $x, $study);
        $this->assertDashboard($sheets, $x, $study);
        foreach (['landLoanAmount', 'hardLoanAmount', 'ffeLoanAmount', 'propertyLoanAmount'] as $amount) {
            $this->assertGreaterThan(1000, $engine[$amount], "$amount: the variant must have a loan to compare");
        }
        $this->assertCount(4, \App\Excel\Sheets\LoansSheet::blocks($x), 'all four loans are in the workbook');
    }

    // ── fixed assets ──

    /** Projects under progress, building, FF&E, land and the depreciation that goes to the income statement. */
    private function assertAssets(array $sheets, \App\Excel\Ctx $x, HospitalitySector $study): void
    {
        if (! isset($x->sheets['fa'])) {
            return;
        }
        $items = app(\App\Services\Statements\ReportItems::class);
        $c = $items->context($study);
        if ($c === null) {
            try {
                $study->calculateRoomRevenueAndGuestCount();
            } catch (\Throwable $e) {
                $this->fail('the application cannot calculate the study: '.$e->getMessage().' @ '.basename($e->getFile()).':'.$e->getLine());
            }
        }
        $this->assertNotNull($c, 'the application cannot calculate the study');
        $monthly = $c['monthly'];
        if (isset($x->rows['fa.cend'])) {
            $this->assertRow($sheets, $x, 'fa.cend', $monthly['projectUnderProgressConstruction']['end_balance'] ?? [], 'construction under progress');
            $this->assertRow($sheets, $x, 'fa.ctr', $monthly['projectUnderProgressConstruction']['transferred_date_and_vales'] ?? [], 'construction moved to the assets');
        }
        if (isset($x->rows['fa.fend'])) {
            $this->assertRow($sheets, $x, 'fa.fend', $monthly['projectUnderProgressFFE']['end_balance'] ?? [], 'FF&E under progress');
        }
        $parts = ['ini' => 'initial_total_gross', 'fin' => 'final_total_gross', 'acc' => 'accumulated_depreciation', 'end' => 'end_balance', 'dep' => 'total_monthly_depreciation', 'rep' => 'replacement_cost'];
        $blocks = ['bl' => ['propertyAssetsForBuilding', 'building'], 'pf' => ['propertyAssetsForFFE', 'property FF&E']];
        foreach ($blocks as $key => [$engineKey, $label]) {
            if (! isset($x->rows["fa.{$key}fin"])) {
                continue;
            }
            foreach ($parts as $row => $engineRow) {
                $this->assertRow($sheets, $x, "fa.$key$row", $monthly[$engineKey][$engineRow] ?? [], "$label $engineRow", 0.02);
            }
        }
        foreach ($study->ffeItems as $i => $item) {
            if (! isset($x->rows["fa.i{$i}fin"])) {
                continue;
            }
            foreach ($parts as $row => $engineRow) {
                $this->assertRow($sheets, $x, "fa.i$i$row", $monthly['ffeAssetItems'][$item->getName()][$engineRow] ?? [], "FF&E item {$item->getName()} $engineRow", 0.02);
            }
        }
        if (isset($x->rows['fa.itG'])) {
            $this->assertRow($sheets, $x, 'fa.itG', $monthly['totalOfFFEItemForFFE']['final_total_gross'] ?? [], 'FF&E items gross');
            $this->assertRow($sheets, $x, 'fa.itI', $monthly['totalOfFFEItemForFFE']['initial_total_gross'] ?? [], 'FF&E items initial gross');
        }
        if (isset($x->rows['fa.depPl'])) {
            $this->assertRow($sheets, $x, 'fa.depPl', $monthly['propertyMonthlyDepreciation'] ?? [], 'depreciation in the income statement', 0.05);
        }
        if (isset($x->rows['fa.lacc'])) {
            $land = $study->calculateLandFixedAssets($items->studyDates($c), $c['dashboard']['propertyLandCapitalizedInterest'] ?? [], $c['dashboard']['landLoanCapitalizedInterest'] ?? []);
            $this->assertRow($sheets, $x, 'fa.lacc', $land['AccumulatedLandFixed'] ?? [], 'land at cost', 0.02);
        }
    }

    /** @dataProvider studies */
    #[\PHPUnit\Framework\Attributes\DataProvider('studies')]
    public function test_the_fixed_assets_match_the_application(int $id): void
    {
        $this->attachLoans($id);
        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertAssets($sheets, $x, $study);
    }

    public static function assetVariants(): array
    {
        // [hard start, hard duration, FF&E start, FF&E duration, property purchase month, building replacement (rate, years), FF&E replacement (rate, years), item replacement years]
        return [
            'work done before opening, replacements' => [4, 14, 6, 9, 1, [10, 2], [5, 3], [3, 2, 0], true],
            // the application's own balance sheet does not balance when work or a purchase falls after the opening (interest is both capitalised and charged): the workbook gives the same figures
            'work running past the opening' => [4, 40, 20, 30, 12, [8, 1], [6, 2], [2, 4, 3], false],
            'property bought after the opening' => [2, 20, 3, 12, 30, [12, 3], [4, 1], [1, 1, 2], false],
        ];
    }

    /** Capitalised interest, the work finishing before and after the opening, replacements of every interval, a property bought at different times. */
    #[\PHPUnit\Framework\Attributes\DataProvider('assetVariants')]
    public function test_assets_of_every_kind_match_the_application(int $hardStart, int $hardDuration, int $ffeStart, int $ffeDuration, int $purchase, array $building, array $ffeProperty, array $itemYears, bool $balances): void
    {
        $id = 313;
        $this->attachLoans($id);
        DB::table('acquisitions')->where('hospitality_sector_id', $id)->update([
            'hard_construction_cost' => '80000000', 'hard_construction_contingency_rate' => '5', 'hard_construction_start_date' => (string) $hardStart, 'hard_construction_duration' => (string) $hardDuration,
            'hard_execution_method' => 'straight-line', 'hard_down_payment' => '15', 'hard_balance_rate_one' => '50', 'hard_due_one' => '30', 'hard_balance_rate_two' => '35', 'hard_due_two' => '60',
            'hard_equity_funding' => '35', 'soft_construction_start_date' => '2', 'soft_construction_duration' => '16', 'soft_execution_method' => 'straight-line',
        ]);
        DB::table('ffe')->where('hospitality_sector_id', $id)->update([
            'start_date' => (string) $ffeStart, 'duration' => (string) $ffeDuration, 'execution_method' => 'steady-growth', 'down_payment' => '25', 'balance_rate_one' => '40', 'due_one' => '60',
            'balance_rate_two' => '35', 'due_two' => '90', 'ffe_equity_funding' => '45',
        ]);
        DB::table('property_acquisitions')->where('hospitality_sector_id', $id)->update([
            'property_purchase_cost' => '50000000', 'property_contingency_rate' => '1', 'purchase_date' => (string) $purchase, 'property_payment_method' => 'installment',
            'first_property_down_payment_percentage' => '25', 'second_property_down_payment_percentage' => '15', 'property_after_month' => '3', 'property_installment_count' => '3',
            'installment_interval' => 'quarterly', 'property_equity_funding_rate' => '40',
            'replacement_cost_rate' => (string) $building[0], 'replacement_interval' => (string) $building[1], 'ffe_replacement_cost_rate' => (string) $ffeProperty[0], 'ffe_replacement_interval' => (string) $ffeProperty[1],
        ]);
        DB::table('loans')->where('hospitality_sector_id', $id)->update(['loan_type' => 'normal', 'installment_interval' => 'quartly', 'period' => '60', 'grace_period' => '0', 'base_rate' => '18', 'margin_rate' => '2.5']);
        if (! DB::table('loans')->where('hospitality_sector_id', $id)->where('section_name', 'property_loan')->exists()) {
            $template = (array) DB::table('loans')->where('hospitality_sector_id', $id)->where('section_name', 'land')->first();
            unset($template['id']);
            DB::table('loans')->insert(array_merge($template, ['section_name' => 'property_loan', 'acquisition_id' => DB::table('property_acquisitions')->where('hospitality_sector_id', $id)->value('id')]));
        }
        $this->fitBreakdown($id);
        foreach (DB::table('ffe_items')->where('hospitality_sector_id', $id)->orderBy('id')->get() as $n => $item) {
            DB::table('ffe_items')->where('id', $item->id)->update(['replacement_interval' => (string) ($itemYears[$n % 3]), 'replacement_cost_rate' => (string) (20 + 10 * $n)]);
        }

        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertAssets($sheets, $x, $study);
        $this->assertLoans($sheets, $x, $this->engineLoans($study));
        $this->assertIncome($sheets, $x, $study);
        $this->assertStatements($sheets, $x, $study, $balances);
    }

    // ── fees, property taxes and insurance ──

    /** The months of an engine array that fall in the study (the application keeps a few payments that fall after it). @return array<int,float> */
    private function inStudy(array $values, HospitalitySector $study): array
    {
        $out = [];
        foreach ($values as $month => $value) {
            if (is_numeric($month) && (int) $month <= $study->getStudyEndDateAsIndex()) {
                $out[(int) $month] = $value;
            }
        }

        return $out;
    }

    private function assertTaxes(array $sheets, \App\Excel\Ctx $x, HospitalitySector $study): void
    {
        $items = app(\App\Services\Statements\ReportItems::class);
        $c = $items->context($study);
        $cash = app(\App\Services\Reports\FinancialReports::class)->cashInOut($study);
        $this->assertNotNull($c);
        $monthly = $c['monthly'];

        $this->assertRow($sheets, $x, 'tx.bf', $this->inStudy($monthly['baseManagementFees'] ?? [], $study), 'base management fee');
        if (isset($x->rows['tx.texp'])) {
            $this->assertRow($sheets, $x, 'tx.texp', $monthly['propertyTaxes'] ?? [], 'property taxes');
            $this->assertRow($sheets, $x, 'tx.tpay', $this->inStudy($cash['monthlyPropertyTaxesAndExpensesAndPayments']['payments'] ?? [], $study), 'property taxes paid');
            $owed = (new \App\Calculations\PropertyTaxesPayableEndBalance)->getPropertyTaxesPayableEndBalance($monthly['propertyTaxes'] ?? [], $cash['monthlyPropertyTaxesAndExpensesAndPayments']['payments'] ?? [], $c['dateIndexWithDate'], $study)['monthly']['end_balance'] ?? [];
            $this->assertRow($sheets, $x, 'tx.tbal', $owed, 'property taxes owed');
        }
        if (isset($x->rows['tx.iexp'])) {
            $this->assertRow($sheets, $x, 'tx.iexp', $monthly['propertyInsurance'] ?? [], 'property insurance');
            $this->assertRow($sheets, $x, 'tx.iok', $this->inStudy($cash['monthlyPropertyInsuranceAndExpensesAndPayments']['payments'] ?? [], $study), 'property insurance paid');
            $owed = (new \App\Calculations\PropertyInsurancePayableEndBalance)->getPropertyInsurancePayableEndBalance($monthly['propertyInsurance'] ?? [], $cash['monthlyPropertyInsuranceAndExpensesAndPayments']['payments'] ?? [], $c['dateIndexWithDate'], $c['dateWithDateIndex'], $study)['monthly']['end_balance'] ?? [];
            $this->assertRow($sheets, $x, 'tx.ibal', $owed, 'property insurance owed');
        }
    }

    /** @dataProvider studies */
    #[\PHPUnit\Framework\Attributes\DataProvider('studies')]
    public function test_the_fees_taxes_and_insurance_match_the_application(int $id): void
    {
        $this->attachLoans($id);
        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertTaxes($sheets, $x, $study);
    }

    public static function taxMonths(): array
    {
        return ['paid in March' => ['3'], 'paid in December' => ['12'], 'paid in January' => ['1']];
    }

    /** Property taxes and insurance charged every year on the building and the FF&E, paid in a month of the next year. */
    #[\PHPUnit\Framework\Attributes\DataProvider('taxMonths')]
    public function test_property_taxes_and_insurance_of_every_kind_match_the_application(?string $paymentMonth): void
    {
        $id = 313;
        $this->attachLoans($id);
        DB::table('department_expenses')->where('hospitality_sector_id', $id)->whereIn('name', ['Property Taxes', 'Property Insurance'])->delete();
        $row = fn (string $name, string $rate, ?string $month) => [
            'model_name' => 'property', 'hospitality_sector_id' => $id, 'company_id' => 41, 'section_name' => 'other_property_fixed_expenses', 'name' => $name, 'payload' => null,
            'percentage_from_fixed_assets' => $rate, 'guest_annual_escalation_rate' => '5', 'payment_month' => $month, 'start_up_cost' => 0, 'date' => 0,
        ];
        DB::table('department_expenses')->insert([$row('Property Taxes', '0.3', $paymentMonth), $row('Property Insurance', '0.25', null)]);
        DB::table('property_acquisitions')->where('hospitality_sector_id', $id)->update([
            'property_purchase_cost' => '50000000', 'purchase_date' => '1', 'property_payment_method' => 'cash', 'property_equity_funding_rate' => '100',
            'replacement_cost_rate' => '10', 'replacement_interval' => '2', 'ffe_replacement_cost_rate' => '5', 'ffe_replacement_interval' => '3',
        ]);
        $this->fitBreakdown($id);

        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertTrue(isset($x->rows['tx.texp']) && isset($x->rows['tx.iexp']), 'both charges are in the workbook');
        $this->assertTaxes($sheets, $x, $study);
        $this->assertIncome($sheets, $x, $study);
        $this->assertStatements($sheets, $x, $study);
        $c = app(\App\Services\Statements\ReportItems::class)->context($study);
        $this->assertGreaterThan(1000, array_sum($c['monthly']['propertyTaxes'] ?? []), 'the study has taxes to compare');
    }

    // ── income statement ──

    private function assertIncome(array $sheets, \App\Excel\Ctx $x, HospitalitySector $study): void
    {
        $items = app(\App\Services\Statements\ReportItems::class);
        $c = $items->context($study);
        $this->assertNotNull($c);
        $annual = $items->forInterval($c, 'annually');
        $monthly = $c['monthly'];
        $one = fn (array $group) => reset($group) ?: [];
        $series = fn (array $line) => array_filter($line, fn ($key) => is_numeric($key), ARRAY_FILTER_USE_KEY);

        $this->assertRow($sheets, $x, 'is.rtotal', $series($monthly['hotelRevenue']['Total Revenues'] ?? []), 'total revenue');
        $this->assertRow($sheets, $x, 'is.dtotal', $series($annual['directExpenses']['Departmental Expenses'] ?? []), 'departmental expenses');
        $this->assertRow($sheets, $x, 'is.gtotal', $series($annual['DepartmentsGrossProfit']['Departments Gross Profit'] ?? []), 'departments gross profit');
        $this->assertRow($sheets, $x, 'is.utotal', $series($annual['undistributedOperatingExpenses']['Undistributed Operating Expenses'] ?? []), 'undistributed expenses');
        $this->assertRow($sheets, $x, 'is.ototal', $series($one($annual['other_deductions'])), 'other deductions');
        $this->assertRow($sheets, $x, 'is.ebitda', $series($one($annual['EBITDA'])), 'EBITDA');
        $this->assertRow($sheets, $x, 'is.inc', $series($one($annual['incentive_management_fees'])), 'incentive fee');
        $this->assertRow($sheets, $x, 'is.dep', $series($one($annual['depreciation'])), 'depreciation');
        $this->assertRow($sheets, $x, 'is.ebit', $series($one($annual['EBIT'])), 'EBIT');
        $this->assertRow($sheets, $x, 'is.itotal', array_intersect_key($monthly['totalLoanInterest'] ?? [], $series($one($annual['EBIT']))), 'loan interest');
        $this->assertRow($sheets, $x, 'is.ebt', $series($one($annual['EBT'])), 'EBT');
        $this->assertRow($sheets, $x, 'is.tax', $series($one($annual['taxes'])), 'corporate tax');
        $this->assertRow($sheets, $x, 'is.np', $series($one($annual['net_profit'])), 'net profit');
    }

    /** @dataProvider studies */
    #[\PHPUnit\Framework\Attributes\DataProvider('studies')]
    public function test_the_income_statement_matches_the_application(int $id): void
    {
        $this->attachLoans($id);
        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertIncome($sheets, $x, $study);
    }

    // ── cash flow and balance sheet ──

    /** @param bool $mustBalance the study is consistent, so its balance sheet balances (a variant that changes the cost of a purchase and not its breakdown does not) */
    private function assertStatements(array $sheets, \App\Excel\Ctx $x, HospitalitySector $study, bool $mustBalance = true): void
    {
        $cash = app(\App\Services\Reports\FinancialReports::class)->cashInOut($study);
        $this->assertNotNull($cash);
        $sheet = app(\App\Services\Reports\FinancialReports::class)->balanceSheet($study);
        $report = $cash['reportItems'];
        $series = fn (array $line) => $this->inStudy(array_filter($line, fn ($key) => is_numeric($key), ARRAY_FILTER_USE_KEY), $study);
        $item = fn (string $name) => $this->inStudy($sheet['balance_sheet_items'][$name][$name] ?? [], $study);

        foreach (['rooms' => 'Total Rooms Collection', 'foods' => 'Total F&B Collection', 'gaming' => 'Total Gaming Collection', 'meetings' => 'Total Meeting Spaces Collection', 'others' => 'Total Other Collection'] as $stream => $name) {
            $this->assertRow($sheets, $x, "cf.c$stream", $series($report['cashInReport']['Total Cash In']['subItems'][$name] ?? []), "$stream collections", 0.02);
        }
        $this->assertRow($sheets, $x, 'cf.out', $series($report['cashOutReport']['Total Cash Out Report'] ?? []), 'cash out', 0.05);
        $this->assertRow($sheets, $x, 'cf.wc', $series($cash['working_capital_injection'] ?? []), 'working capital injection', 0.05);
        $this->assertRow($sheets, $x, 'cf.inTotal', $series($report['cashInReport']['Total Cash In'] ?? []), 'cash in', 0.05);
        $this->assertRow($sheets, $x, 'cf.acc', $this->inStudy($report['netCash']['Accumulated Net Cash'] ?? [], $study), 'accumulated net cash', 0.05);
        foreach (['ta' => 'Total Assets', 'tcl' => 'Total Current Liabilities', 'ti' => 'Total Investment', 'lot' => 'Long Term Loans', 'oe' => 'Owners Equity', 'lta' => 'Total Long Term Assets', 'tca' => 'Total Current Assets'] as $row => $name) {
            $this->assertRow($sheets, $x, "bs.$row", $item($name), $name, 0.05);
        }
        $this->assertRow($sheets, $x, 'bs.chk', $item('Check Error'), 'Check Error', 0.05);
        if ($mustBalance) {
            $worst = max(array_map('abs', array_slice(Recalc::row($sheets, $x, 'bs.chk'), 0, $study->getStudyEndDateAsIndex() + 1)));
            $this->assertLessThan(1, $worst, 'Check Error of the workbook');
        }
    }

    /** @dataProvider studies */
    #[\PHPUnit\Framework\Attributes\DataProvider('studies')]
    public function test_the_cash_flow_and_balance_sheet_match_the_application(int $id): void
    {
        $this->attachLoans($id);
        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertStatements($sheets, $x, $study);
    }

    // ── ratios and the value of the hotel ──

    private function assertDashboard(array $sheets, \App\Excel\Ctx $x, HospitalitySector $study): void
    {
        $analysis = app(\App\Services\Reports\StudyAnalysis::class);
        $ratios = $analysis->ratios($study);
        $this->assertNotNull($ratios);
        $dashboard = $analysis->dashboard($study);
        $this->assertNotNull($dashboard);

        // a year row of the workbook, by year number; the application keys a year by its December
        $yearly = fn (string $key) => array_slice(array_values(Recalc::row($sheets, $x, $key)), 0, $x->m->Y);
        $dateIndexWithDate = app('dateIndexWithDate');
        $byYear = function (array $data) use ($dateIndexWithDate, $x) {
            $out = [];
            foreach ($data as $index => $value) {
                $out[(int) substr((string) $dateIndexWithDate[$index], -4) - $x->m->baseYear] = (float) $value;
            }

            return $out;
        };
        $rows = ['Return on Sales "ROS"' => 'ros', 'Return on Assets "ROA"' => 'roa', 'Return On Capital Employed "ROCE"' => 'roce', 'Return on Equity "ROE"' => 'roe', 'Gross Profit Margin' => 'gpm',
            'EBITDA Margin' => 'ebm', 'Net Profit Margin' => 'npm', 'Current Ratio' => 'cr', 'Quick Ratio' => 'qr', 'Cash Ratio' => 'car', 'Working Capital' => 'wcr',
            'Days Sales Outstanding "DSO"' => 'dso', 'Days Inventory Outstanding "DIO"' => 'dio', 'Days Payables Outstanding "DPO"' => 'dpo', 'Cash Conversion Cycle "CCC"' => 'ccc',
            'Debt to Asset Ratio' => 'da', 'Debt to Equity Ratio' => 'de'];
        $percent = ['ros', 'roa', 'roce', 'roe', 'gpm', 'ebm', 'npm'];
        $compared = 0;
        foreach ($ratios['ratio_analysis_report'] as $group) {
            foreach ($group as $name => $ratio) {
                $key = $rows[$name];
                $engine = $byYear($ratio['data']);
                $model = $yearly("db.$key");
                foreach ($engine as $y => $value) {
                    if (! isset($model[$y])) {
                        continue;   // a figure the application keeps for a year after the study (a loan paid off later)
                    }
                    // a year whose divisor is nothing in the workbook has the rounding dust of the application as its ratio: nothing to compare
                    $divisors = ['ros' => ['rev'], 'roa' => ['ta'], 'roce' => ['ti'], 'roe' => ['oe'], 'gpm' => ['rev'], 'ebm' => ['rev'], 'npm' => ['rev'], 'cr' => ['tcl'], 'qr' => ['tcl'], 'car' => ['tcl'],
                        'dso' => ['rev'], 'dio' => ['dis'], 'dpo' => ['dis'], 'ccc' => ['rev', 'dis'], 'da' => ['ta'], 'de' => ['oe']][$key] ?? [];
                    foreach ($divisors as $divisor) {
                        if (abs($yearly("db.$divisor")[$y]) < 1) {
                            continue 2;
                        }
                    }
                    $expected = $value;
                    $this->assertEqualsWithDelta($expected, $model[$y], max(0.0001, abs($expected) * 1e-7), "$name in year $y");
                    $compared++;
                }
            }
        }
        $this->assertGreaterThan(0, $compared);

        // the key figures: the cards print rounded numbers
        $cards = collect($dashboard['card_items'])->pluck('value', 'title');
        $number = fn (string $text) => (float) preg_replace('/[^0-9.\-]/', '', explode(' ', trim($text))[0]);
        $cell = fn (string $key) => $this->cellValue($sheets, $x, "db.$key");
        $this->assertEqualsWithDelta($number($cards['Total Fixed Asset Investment']), $cell('fixedInv'), 1.0, 'fixed asset investment');
        $this->assertEqualsWithDelta($number($cards['Pre-operating & Working Capital']), $cell('wcInj'), 1.0, 'pre-operating and working capital');
        $this->assertEqualsWithDelta($number($cards['Total Required Investment']), $cell('req'), 1.0, 'required investment');
        $this->assertEqualsWithDelta($number($cards['Total Equity Injection']), $cell('eq'), 1.0, 'equity');
        $this->assertEqualsWithDelta($number($cards['Total Loans']), $cell('loanTotal'), 1.0, 'loans');
        $this->assertEqualsWithDelta($number($cards['WACC %']), $cell('wacc') * 100, 0.06, 'WACC');
        $this->assertEqualsWithDelta($number($cards['Cost Of Debt %']), $cell('cod') * 100, 0.06, 'cost of debt');
        $this->assertEqualsWithDelta($number($cards['Net Present Value For Firm']), $cell('npvF'), 1.0, 'NPV for the firm');
        if (is_numeric($dashboard['irrForEquity']) && ! is_nan($dashboard['irrForEquity'])) {
            $this->assertEqualsWithDelta($dashboard['irrForEquity'], $cell('irrE'), 1e-6, 'IRR for the owners');
        }
        $this->assertEqualsWithDelta($dashboard['netPresentValueForEquity'], $cell('npvE'), max(1.0, abs($dashboard['netPresentValueForEquity']) * 1e-9), 'NPV for the owners');
        if (preg_match('/^([0-9.]+) Years/', (string) $cards['Payback Period'], $match)) {
            $this->assertEqualsWithDelta((float) $match[1], $cell('pbYears'), 0.051, 'payback');
        }
    }

    /** The value of a single cell of a sheet row registered with Grid::later() / scalar(). */
    private function cellValue(array $sheets, \App\Excel\Ctx $x, string $key): float
    {
        $ref = $x->name($key);                       // 'Sheet'!$E$12
        preg_match("/^'(.*)'!\\$([A-Z]+)\\$(\\d+)$/", $ref, $parts);
        $col = 0;
        foreach (str_split($parts[2]) as $letter) {
            $col = $col * 26 + ord($letter) - 64;
        }

        return (float) ($sheets[str_replace("''", "'", $parts[1])][(int) $parts[3]][$col] ?? 0);
    }

    /** @dataProvider studies */
    #[\PHPUnit\Framework\Attributes\DataProvider('studies')]
    public function test_the_ratios_and_the_value_match_the_application(int $id): void
    {
        $this->attachLoans($id);
        [$x, $sheets, $study] = $this->recalculated($id);

        $this->assertDashboard($sheets, $x, $study);
    }

    /** No cell of the recalculated workbook is an error (#DIV/0!, #NAME?, #REF!, Err:...), in either language, on a study with loans, assets and taxes. */
    public function test_no_cell_of_the_workbook_is_an_error(): void
    {
        $this->attachLoans(313);
        foreach (['en', 'ar'] as $lang) {
            $study = HospitalitySector::query()->withoutGlobalScopes()->findOrFail(313);
            $study->bindDateTables();
            $x = ModelBuilder::build(ModelDataBuilder::fromStudy($study), $lang);
            $sheets = Recalc::values($x->wb->toString());
            $errors = [];
            foreach ($sheets as $name => $rows) {
                foreach ($rows as $r => $cells) {
                    foreach ($cells as $c => $value) {
                        if (is_string($value) && (str_starts_with($value, '#') || str_starts_with($value, 'Err:'))) {
                            $errors[] = "$name!".\App\Excel\Sheet::col($c).$r.' = '.$value;
                        }
                    }
                }
            }
            $this->assertSame([], array_slice($errors, 0, 10), "cells in error ($lang), first ten");
            $this->assertGreaterThan(20, count($sheets), 'the workbook has all its sheets');
        }
    }
}

