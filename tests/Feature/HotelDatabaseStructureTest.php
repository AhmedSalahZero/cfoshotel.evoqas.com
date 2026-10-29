<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// The hotel tables exist with exactly the agreed columns, and they are tied together the agreed way.
// Run:  php artisan test --filter=HotelDatabaseStructureTest
class HotelDatabaseStructureTest extends TestCase
{
    use RefreshDatabase;

    /** Every hotel table and its columns, checked against the structure of the previous CFO Hotels database. */
    private function expected(): array
    {
        return [
            'countries' => 'id,name_en,name_ar,created_at,updated_at,deleted_at',
            'states' => 'id,country_id,name_ar,name_en,created_at,updated_at',
            'currencies' => 'id,name,created_at,updated_at',
            'room_names' => 'id,title,is_active,company_id,created_at,updated_at',
            'food_names' => 'id,title,is_active,company_id,created_at,updated_at',
            'casino_names' => 'id,title,is_active,company_id,created_at,updated_at',
            'meeting_names' => 'id,title,is_active,company_id,created_at,updated_at',
            'other_names' => 'id,title,is_active,company_id,created_at,updated_at',
            'hospitality_sectors' => 'id,study_name,property_name,operation_dates,region,study_dates,operation_start_date,star_rating,country_id,property_status,state_id,duration_in_years,type,duration_type,study_start_date,study_end_date,development_start_month,development_start_date,company_id,development_duration,operation_start_month,additional_currency,exchange_rate,corporate_taxes_rate,investment_return_rate,perpetual_growth_rate,creator_id,updated_at,casino_cover,created_at,main_functional_currency,financial_year_start_month,total_f&b_facility_count,total_f&b_cover_count,total_casino_facility_count,total_casino_cover_count,total_meeting_cover_count,total_meeting_facility_count,rooms_count,average_guest_count,is_total_rooms,is_total_foods,is_total_casinos,has_casino_section,has_meeting_section,has_other_section,is_total_other,is_total_meetings,has_sales_channels,selected_sales_revenues,general_occupancy_rate,room_collection_policy_type,seasonality_interval,seasonality_type,general_seasonality,add_sales_channels_share_discount,study_navigators,guest_meeting_seasonality_type,guest_meeting_general_seasonality,guest_meeting_seasonality_interval,rent_meeting_seasonality_type,rent_meeting_general_seasonality,rent_meeting_seasonality_interval,has_rooms_manpower,has_foods_manpower,has_casinos_manpower,has_sales_and_general_manpower,has_other_revenue_manpower,other_collection_policy_type,meeting_collection_policy_type,casino_collection_policy_type,food_collection_policy_type,rooms_general_collection_policy_interval,rooms_general_collection_policy_value,rooms_general_collection_policy_type,others_general_collection_policy_value,others_general_collection_policy_interval,others_general_collection_policy_type,meetings_general_collection_policy_interval,meetings_general_collection_policy_value,meetings_general_collection_policy_type,foods_general_collection_policy_value,foods_general_collection_policy_interval,foods_general_collection_policy_type,casinos_general_collection_policy_value,casinos_general_collection_policy_interval,casinos_general_collection_policy_type,has_visit_room_section,has_visit_food_section,has_visit_casino_section,has_visit_meeting_section,has_visit_other_section,exchange_rates',
            'hospitality_sector_shares' => 'id,hospitality_sector_id,user_id,can_edit,created_at,updated_at',
            'rooms' => 'id,room_count,guest_per_room,hospitality_sector_id,average_daily_rate,chosen_room_currency,average_daily_rate_at_operation_date,average_daily_rate_annual_escalation_rate,occupancy_rate_type,average_daily_rate_escalation_rate,occupancy_rate_per_room,seasonality,created_at,updated_at,room_type_id',
            'saleschannels' => 'id,name,hospitality_sector_id,percentages,revenue_share_percentage,created_at,updated_at,discount_or_commission,collection_policy_type,collection_policy_value,collection_policy_interval',
            'foods' => 'id,food_count,food_cover,total_daily_cover_count,f&b_facilities,cover_value,estimation_date,chosen_food_currency,cover_value_escalation_rate,cover_value_at_operation_date,cover_value_annual_escalation_rate,guest_capture_cover_percentage,cover_per_day,hospitality_sector_id,created_at,updated_at,meal_per_guest,percentage_from_rooms_revenues,collection_policy_type,collection_policy_value,collection_policy_interval,food_type_id',
            'casinos' => 'id,casino_count,f&b_facilities,casino_cover,hospitality_sector_id,percentage_from_rooms_revenues,collection_policy_type,collection_policy_value,collection_policy_interval,cover_per_day,guest_capture_cover_percentage,charges_value_at_operation_date,charges_value_escalation_rate,charges_value_annual_escalation_rate,chosen_casino_currency,cover_value,updated_at,created_at,casino_type_id',
            'meetings' => 'id,collection_policy_type,collection_policy_value,collection_policy_interval,meeting_count,meeting_cover,hospitality_sector_id,f&b_facilities,created_at,updated_at,guest_capture_cover_percentage,guest_seasonality,rent_seasonality,charges_value_escalation_rate,chosen_meeting_currency,cover_value,charges_value_per_guest,charges_value_at_operation_date,charges_value_annual_escalation_rate,percentage_from_f_and_b_revenues,meeting_type_id',
            'others' => 'id,collection_policy_type,collection_policy_value,collection_policy_interval,f&b_facilities,other_count,chosen_other_currency,hospitality_sector_id,charges_per_guest_escalation_rate,charges_per_guest,charges_per_guest_annual_escalation_rate,charges_per_guest_at_operation_date,guest_capture_cover_percentage,percentage_from_rooms_revenues,updated_at,created_at,other_type_id',
            'department_expenses' => 'id,model_name,hospitality_sector_id,section_name,name,payload,manpower_payload,expense_per_night_sold,expense_per_guest,inventory_coverage_days,beginning_inventory_balance_value,cash_payment_percentage,deferred_payment_percentage,due_days,current_net_salary,chosen_currency,escalation_rate,net_salary_at_operation_date,annual_escalation_rate,salary_taxes,social_insurance,night_expense_escalation_rate,chosen_night_expense_currency,company_id,night_annual_escalation_rate,created_at,updated_at,night_expense_at_operation_date,guest_annual_escalation_rate,guest_expense_at_operation_date,guest_expense_escalation_rate,expense_per_guest_sold,chosen_guest_expense_currency,opex_payment_terms,payment_month,percentage_from_fixed_assets,start_up_cost,date',
            'management_fees' => 'id,model_name,hospitality_sector_id,section_name,name,payload,company_id,created_at,updated_at',
            'acquisitions' => 'id,land_installment_count,land_after_month,second_land_down_payment_percentage,first_land_down_payment_percentage,land_payment_method,land_contingency_rate,land_purchase_cost,purchase_date,land_equity_funding_rate,installment_interval,collection_policy_interval,collection_policy_type,collection_policy_value,hospitality_sector_id,company_id,created_at,updated_at,has_land_section,hard_construction_contingency_rate,hard_construction_cost,has_hard_construction_cost_section,hard_construction_duration,hard_construction_start_date,hard_construction_end_date,hard_execution_method,hard_down_payment,hard_balance_rate_one,hard_balance_rate_two,hard_due_one,hard_due_two,hard_equity_funding,soft_due_two,soft_balance_rate_two,soft_due_one,soft_balance_rate_one,soft_construction_start_date,soft_down_payment,soft_construction_end_date,soft_execution_method,soft_construction_contingency_rate,soft_construction_cost,has_soft_construction_cost_section,soft_construction_duration',
            'property_acquisitions' => 'id,property_installment_count,property_after_month,second_property_down_payment_percentage,first_property_down_payment_percentage,property_payment_method,property_contingency_rate,property_purchase_cost,purchase_date,property_equity_funding_rate,installment_interval,collection_policy_interval,collection_policy_type,collection_policy_value,hospitality_sector_id,company_id,created_at,updated_at,has_land_section,replacement_cost_name,replacement_cost_rate,replacement_interval,ffe_replacement_interval,ffe_replacement_cost_rate,ffe_replacement_cost_name',
            'property_acquisition_breakdowns' => 'id,model_name,hospitality_sector_id,section_name,name,property_cost_percentage,item_amount,depreciation_duration,company_id,created_at,updated_at',
            'ffe' => 'id,hospitality_sector_id,company_id,created_at,updated_at,duration,start_date,end_date,execution_method,down_payment,balance_rate_one,balance_rate_two,due_one,due_two,ffe_equity_funding',
            'ffe_items' => 'id,hospitality_sector_id,ffe_id,section_name,model_name,name,depreciation_duration,created_at,updated_at,item_cost,contingency_rate,company_id,currency_name,replacement_cost_rate,replacement_interval',
            'loans' => 'id,hospitality_sector_id,company_id,section_name,acquisition_id,ffe_id,loan_type,grace_period,loan_amount,installment_interval,start_date,end_date,period,fixedType,base_rate,margin_rate,pricing,duration,step_down_rate,step_up_rate,step_up_interval,step_down_interval,borrowing_rate,capitalization_type,margin_interest,loan_interest,min_interest,repayment_duration,installment_amount,created_at,updated_at',
        ];
    }

    /** Saves one study of the given company, owned by the given person, and returns its id. */
    private function insertStudy(int $companyId, int $creatorId): int
    {
        return DB::table('hospitality_sectors')->insertGetId([
            'study_name' => 'Nile Grand', 'company_id' => $companyId, 'creator_id' => $creatorId,
            'study_start_date' => '2026-01', 'study_end_date' => '2030-12',
            'development_start_month' => 1, 'development_start_date' => '2026-01', 'development_duration' => 12, 'operation_start_month' => 13,
            'additional_currency' => 'usd', 'main_functional_currency' => 'egp', 'exchange_rate' => 50,
            'corporate_taxes_rate' => 22.5, 'investment_return_rate' => 12, 'perpetual_growth_rate' => 3,
        ]);
    }

    /** A person of a company who owns one study. Returns [user, study id]. */
    private function study(): array
    {
        $user = User::factory()->create();

        return [$user, $this->insertStudy($user->company_id, $user->id)];
    }

    public function test_all_hotel_tables_exist(): void
    {
        $this->assertCount(24, $this->expected());

        foreach (array_keys($this->expected()) as $table) {
            $this->assertTrue(Schema::hasTable($table), "table '{$table}' is missing");
        }
    }

    public function test_every_table_has_exactly_the_agreed_columns(): void
    {
        foreach ($this->expected() as $table => $columns) {
            $want = explode(',', $columns);
            $have = Schema::getColumnListing($table);
            sort($want);
            sort($have);

            $this->assertSame($want, $have, "table '{$table}' does not have the agreed columns");
        }
    }

    public function test_a_new_study_is_monthly_and_has_no_sections_switched_on(): void
    {
        [, $study] = $this->study();
        $row = DB::table('hospitality_sectors')->find($study);

        $this->assertSame('monthly', $row->duration_type);
        $this->assertSame('0', (string) $row->has_casino_section);
        $this->assertSame('0', (string) $row->has_meeting_section);
    }

    public function test_deleting_a_study_removes_what_belongs_to_it(): void
    {
        [$user, $study] = $this->study();
        $friend = User::factory()->forCompany(Company::findOrFail($user->company_id))->create();

        DB::table('rooms')->insert(['hospitality_sector_id' => $study]);
        DB::table('saleschannels')->insert(['hospitality_sector_id' => $study]);
        DB::table('loans')->insert(['hospitality_sector_id' => $study, 'company_id' => $user->company_id]);
        DB::table('hospitality_sector_shares')->insert(['hospitality_sector_id' => $study, 'user_id' => $friend->id]);

        DB::table('hospitality_sectors')->where('id', $study)->delete();

        foreach (['rooms', 'saleschannels', 'loans', 'hospitality_sector_shares'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} was not removed with the study");
        }
        $this->assertSame(1, DB::table('users')->where('id', $friend->id)->count(), 'the person must stay');
    }

    public function test_a_company_that_has_studies_cannot_be_deleted_by_accident(): void
    {
        // The company has no people at all: only its study stops the deletion.
        $company = Company::factory()->create();
        $owner = User::factory()->create();
        $this->insertStudy($company->id, $owner->id);

        $this->expectException(QueryException::class);

        DB::table('companies')->where('id', $company->id)->delete();
    }

    public function test_a_person_who_owns_studies_cannot_be_deleted_by_accident(): void
    {
        [$user] = $this->study();

        $this->expectException(QueryException::class);

        DB::table('users')->where('id', $user->id)->delete();
    }

    public function test_a_study_can_be_shared_with_a_person_only_once(): void
    {
        [$user, $study] = $this->study();
        $friend = User::factory()->forCompany(Company::findOrFail($user->company_id))->create();

        DB::table('hospitality_sector_shares')->insert(['hospitality_sector_id' => $study, 'user_id' => $friend->id, 'can_edit' => false]);

        $this->expectException(QueryException::class);

        DB::table('hospitality_sector_shares')->insert(['hospitality_sector_id' => $study, 'user_id' => $friend->id, 'can_edit' => true]);
    }

    public function test_a_room_type_that_is_in_use_cannot_be_deleted(): void
    {
        [$user, $study] = $this->study();
        $type = DB::table('room_names')->insertGetId(['title' => 'Standard', 'company_id' => $user->company_id]);
        DB::table('rooms')->insert(['hospitality_sector_id' => $study, 'room_type_id' => $type]);

        $this->expectException(QueryException::class);

        DB::table('room_names')->where('id', $type)->delete();
    }

    public function test_a_row_cannot_point_to_a_study_that_does_not_exist(): void
    {
        $this->expectException(QueryException::class);

        DB::table('rooms')->insert(['hospitality_sector_id' => 999999]);
    }

    public function test_a_study_must_belong_to_a_real_company(): void
    {
        $owner = User::factory()->create();

        $this->expectException(QueryException::class);

        $this->insertStudy(999999, $owner->id);
    }

    public function test_a_study_must_belong_to_a_real_person(): void
    {
        $company = Company::factory()->create();

        $this->expectException(QueryException::class);

        $this->insertStudy($company->id, 999999);
    }
}
