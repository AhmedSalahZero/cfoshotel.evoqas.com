<?php

namespace Tests\Feature;

use App\Models\Acquisition;
use App\Models\Casino;
use App\Models\CasinoName;
use App\Models\Company;
use App\Models\Concerns\AllowsLazyLoading;
use App\Models\Country;
use App\Models\Currency;
use App\Models\DepartmentExpense;
use App\Models\FFE;
use App\Models\FFEItem;
use App\Models\Food;
use App\Models\FoodName;
use App\Models\HospitalitySector;
use App\Models\Loan;
use App\Models\ManagementFee;
use App\Models\Meeting;
use App\Models\MeetingName;
use App\Models\Other;
use App\Models\OtherName;
use App\Models\PropertyAcquisition;
use App\Models\PropertyAcquisitionBreakDown;
use App\Models\Room;
use App\Models\RoomName;
use App\Models\SalesChannel;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\HotelStudies;
use Tests\TestCase;

// The hotel models: their tables, their links and what their accessors return, checked on five real studies.
// Run:  php artisan test --filter=HotelModelsTest
class HotelModelsTest extends TestCase
{
    use RefreshDatabase;

    /** model => table */
    private const MODELS = [
        Acquisition::class => 'acquisitions', Casino::class => 'casinos', CasinoName::class => 'casino_names', Country::class => 'countries',
        Currency::class => 'currencies', DepartmentExpense::class => 'department_expenses', FFE::class => 'ffe', FFEItem::class => 'ffe_items',
        Food::class => 'foods', FoodName::class => 'food_names', HospitalitySector::class => 'hospitality_sectors', Loan::class => 'loans',
        ManagementFee::class => 'management_fees', Meeting::class => 'meetings', MeetingName::class => 'meeting_names', Other::class => 'others',
        OtherName::class => 'other_names', PropertyAcquisition::class => 'property_acquisitions',
        PropertyAcquisitionBreakDown::class => 'property_acquisition_breakdowns', Room::class => 'rooms', RoomName::class => 'room_names',
        SalesChannel::class => 'saleschannels', State::class => 'states',
    ];

    /** The parts that belong to a study: model => table. Each has a hospitalitySector() link, the study has the matching link back. */
    private const PARTS = [
        Room::class => 'rooms', Food::class => 'foods', Casino::class => 'casinos', Meeting::class => 'meetings', Other::class => 'others',
        SalesChannel::class => 'saleschannels',
    ];

    /** Methods that only return one column, by model: method => column. Made from the accessor files. */
    private const GETTERS = [
        'Acquisition' => ['getCollectionPolicyType' => 'collection_policy_type', 'getConstructionEndDate' => 'hard_construction_end_date', 'getHardConstructionDuration' => 'hard_construction_duration', 'getHardExecutionMethod' => 'hard_execution_method', 'getLandInstallmentInterval' => 'installment_interval', 'getLandPaymentMethod' => 'land_payment_method', 'getLandPurchaseDateAsIndex' => 'purchase_date', 'getSoftConstructionDuration' => 'soft_construction_duration', 'getSoftExecutionMethod' => 'soft_execution_method'],
        'Casino' => ['getCasinoCount' => 'casino_count', 'getCasinoCover' => 'casino_cover', 'getCasinoTypeId' => 'casino_type_id', 'getChargesValueAtOperationDate' => 'charges_value_at_operation_date', 'getChosenCurrency' => 'chosen_casino_currency', 'getCollectionPolicyType' => 'collection_policy_type', 'getId' => 'id'],
        'DepartmentExpense' => ['getChosenCurrency' => 'chosen_currency', 'getDateAsIndex' => 'date', 'getGuestExpenseAtOperationDate' => 'guest_expense_at_operation_date', 'getGuestExpenseChosenCurrency' => 'chosen_guest_expense_currency', 'getGuestExpenseEscalationRate' => 'guest_expense_escalation_rate', 'getIdentifier' => 'id', 'getName' => 'name', 'getOpexPaymentTerm' => 'opex_payment_terms'],
        'FFE' => ['getDuration' => 'duration', 'getEndDate' => 'end_date', 'getExecutionMethod' => 'execution_method'],
        'FFEItem' => ['getCurrencyName' => 'currency_name', 'getName' => 'name'],
        'Food' => ['getChosenCurrency' => 'chosen_food_currency', 'getCollectionPolicyType' => 'collection_policy_type', 'getCoverValueAtOperationDate' => 'cover_value_at_operation_date', 'getFoodCount' => 'food_count', 'getFoodCover' => 'food_cover', 'getFoodTypeId' => 'food_type_id', 'getId' => 'id'],
        'HospitalitySector' => ['getAdditionalCurrency' => 'additional_currency', 'getAverageGuestCount' => 'average_guest_count', 'getCountryId' => 'country_id', 'getDevelopmentStartDateAsIndex' => 'development_start_date', 'getDurationInYears' => 'duration_in_years', 'getDurationType' => 'duration_type', 'getGuestMeetingSeasonalityInterval' => 'guest_meeting_seasonality_interval', 'getGuestMeetingSeasonalityType' => 'guest_meeting_seasonality_type', 'getId' => 'id', 'getMainFunctionalCurrency' => 'main_functional_currency', 'getPropertyName' => 'property_name', 'getPropertyStatus' => 'property_status', 'getRegion' => 'region', 'getRentMeetingSeasonalityInterval' => 'rent_meeting_seasonality_interval', 'getRentMeetingSeasonalityType' => 'rent_meeting_seasonality_type', 'getRoomsCount' => 'rooms_count', 'getSeasonalityInterval' => 'seasonality_interval', 'getSeasonalityType' => 'seasonality_type', 'getStarRating' => 'star_rating', 'getStateId' => 'state_id', 'getStudyEndDate' => 'study_end_date', 'getStudyStartDate' => 'study_start_date', 'getTotalCasinoCoverCount' => 'total_casino_cover_count', 'getTotalCasinoFacilityCount' => 'total_casino_facility_count', 'getTotalMeetingCoverCount' => 'total_meeting_cover_count', 'getTotalMeetingFacilityCount' => 'total_meeting_facility_count'],
        'Loan' => ['getInstallmentInterval' => 'installment_interval', 'getLoanType' => 'loan_type', 'getStartDate' => 'start_date', 'getStepDownIntervalName' => 'step_down_interval', 'getStepUpIntervalName' => 'step_up_interval'],
        'ManagementFee' => ['getIdentifier' => 'id', 'getName' => 'name'],
        'Meeting' => ['getChosenCurrency' => 'chosen_meeting_currency', 'getCollectionPolicyType' => 'collection_policy_type', 'getId' => 'id', 'getMeetingCount' => 'meeting_count', 'getMeetingCover' => 'meeting_cover', 'getMeetingTypeId' => 'meeting_type_id'],
        'Other' => ['getChosenCurrency' => 'chosen_other_currency', 'getCollectionPolicyType' => 'collection_policy_type', 'getId' => 'id', 'getOtherCount' => 'other_count', 'getOtherTypeId' => 'other_type_id'],
        'PropertyAcquisition' => ['getCollectionPolicyType' => 'collection_policy_type', 'getPropertyInstallmentInterval' => 'installment_interval', 'getPropertyPaymentMethod' => 'property_payment_method', 'getPropertyPurchaseDateAsIndex' => 'purchase_date'],
        'PropertyAcquisitionBreakDown' => ['getIdentifier' => 'id', 'getName' => 'name'],
        'Room' => ['getAverageDailyRateAtOperationDate' => 'average_daily_rate_at_operation_date', 'getAverageDailyRateEscalationRate' => 'average_daily_rate_escalation_rate', 'getChosenCurrency' => 'chosen_room_currency', 'getGuestPerRoom' => 'guest_per_room', 'getId' => 'id', 'getRoomCount' => 'room_count', 'getRoomTypeId' => 'room_type_id'],
        'SalesChannel' => ['getCollectionPolicyType' => 'collection_policy_type', 'getId' => 'id', 'getName' => 'name'],
    ];

    public function test_every_hotel_model_has_a_table_and_only_real_columns(): void
    {
        $this->assertCount(23, self::MODELS);

        foreach (self::MODELS as $class => $table) {
            $model = new $class();
            $this->assertSame($table, $model->getTable(), "{$class} points to another table");
            $this->assertTrue(Schema::hasTable($table), "table {$table} is missing");

            $columns = Schema::getColumnListing($table);
            foreach (array_keys($model->getCasts()) as $cast) {
                $this->assertContains($cast, $columns, "{$class}: cast '{$cast}' is not a column");
            }
        }
    }

    public function test_every_hotel_model_may_read_its_relations_on_demand(): void
    {
        foreach (array_keys(self::MODELS) as $class) {
            $this->assertInstanceOf(AllowsLazyLoading::class, new $class(), "{$class} is not marked");
        }
    }

    public function test_the_study_sees_all_its_parts(): void
    {
        HotelStudies::load();

        $relations = [
            'rooms' => 'rooms', 'foods' => 'foods', 'casinos' => 'casinos', 'meetings' => 'meetings', 'others' => 'others',
            'salesChannels' => 'saleschannels', 'departmentExpenses' => 'department_expenses', 'ffeItems' => 'ffe_items',
            'managementFees' => 'management_fees', 'loans' => 'loans', 'propertyAcquisitionBreakDown' => 'property_acquisition_breakdowns',
        ];

        foreach (HotelStudies::STUDY_IDS as $id) {
            $study = HospitalitySector::findOrFail($id);

            foreach ($relations as $relation => $table) {
                $expected = count(array_filter(HotelStudies::rows($table), fn ($row) => $row['hospitality_sector_id'] === $id));
                $this->assertCount($expected, $study->{$relation}, "study {$id}: {$relation}");
            }

            foreach (['acquisition' => 'acquisitions', 'propertyAcquisition' => 'property_acquisitions', 'ffe' => 'ffe'] as $relation => $table) {
                $expected = count(array_filter(HotelStudies::rows($table), fn ($row) => $row['hospitality_sector_id'] === $id));
                $this->assertSame($expected, $study->{$relation} === null ? 0 : 1, "study {$id}: {$relation}");
            }
        }
    }

    public function test_every_part_sees_its_study(): void
    {
        HotelStudies::load();

        $parts = self::PARTS + [
            DepartmentExpense::class => 'department_expenses', ManagementFee::class => 'management_fees', FFEItem::class => 'ffe_items',
            PropertyAcquisitionBreakDown::class => 'property_acquisition_breakdowns', Acquisition::class => 'acquisitions',
            PropertyAcquisition::class => 'property_acquisitions', FFE::class => 'ffe', Loan::class => 'loans',
        ];

        foreach ($parts as $class => $table) {
            foreach ($class::query()->whereNotNull('hospitality_sector_id')->get() as $part) {
                $this->assertInstanceOf(HospitalitySector::class, $part->hospitalitySector, "{$class} {$part->id} has no study");
                $this->assertSame((int) $part->hospitality_sector_id, (int) $part->hospitalitySector->id);
            }
        }
    }

    public function test_the_company_and_the_creator_of_a_study_are_found(): void
    {
        HotelStudies::load();

        foreach (HospitalitySector::all() as $study) {
            $this->assertInstanceOf(Company::class, $study->company);
            $this->assertSame((int) $study->company_id, (int) $study->company->id);
            $this->assertInstanceOf(User::class, $study->creator);
        }
    }

    public function test_countries_and_states_see_each_other(): void
    {
        HotelStudies::load();

        $egypt = Country::where('name_en', 'Egypt')->firstOrFail();
        $cairo = State::where('name_en', 'Cairo')->firstOrFail();

        $this->assertTrue($egypt->states->contains($cairo));
        $this->assertTrue($cairo->country->is($egypt));

        app()->setLocale('ar');
        $this->assertSame('مصر', $egypt->getName());
        app()->setLocale('en');
        $this->assertSame('Egypt', $egypt->getName());
        $this->assertSame('Cairo', $cairo->getName());
    }

    public function test_the_currency_names_follow_the_position_stored_in_the_study(): void
    {
        HotelStudies::load();

        $names = Currency::orderBy('id')->pluck('name')->all();
        $this->assertSame(['EGP', 'USD', 'EURO', 'AED', 'SAR', 'QAR'], $names);

        foreach (HospitalitySector::all() as $study) {
            $this->assertSame($names[((int) $study->main_functional_currency) - 1], $study->getMainFunctionalCurrencyFormatted());
            $this->assertSame($names[((int) $study->additional_currency) - 1], $study->getAdditionalCurrencyFormatted());
        }

        $this->assertSame('USD', HospitalitySector::findOrFail(307)->getAdditionalCurrencyFormatted());
    }

    public function test_each_company_lists_its_own_room_types_and_the_total_row_is_found(): void
    {
        HotelStudies::load();

        foreach (Company::whereIn('id', [40, 41, 42])->get() as $company) {
            $total = $company->getTotalRoomId();

            $this->assertTrue(RoomName::findOrFail($total)->getName() !== '', 'a Total row exists');
            $this->assertFalse($company->getRoomExceptTotal()->contains('id', $total));
            $this->assertSame($company->roomNames()->count() - 1, $company->getRoomExceptTotal()->count());
        }
    }

    public function test_simple_getters_return_the_column_they_are_named_after(): void
    {
        HotelStudies::load();

        // the five saved studies have no sales channels, so two are added here to check their getters too
        if (! SalesChannel::query()->exists()) {
            foreach ([['name' => 'Direct', 'collection_policy_type' => 'system_default'], ['name' => 'Online agent', 'collection_policy_type' => 'customize']] as $channel) {
                \Illuminate\Support\Facades\DB::table('saleschannels')->insert($channel + ['hospitality_sector_id' => 305]);
            }
        }

        $checked = 0;
        foreach (self::GETTERS as $model => $getters) {
            $class = 'App\\Models\\'.$model;
            $rows = $class::query()->get();
            $this->assertNotEmpty($rows, "{$model}: no real rows to test with");

            foreach ($rows as $row) {
                foreach ($getters as $method => $column) {
                    try {
                        $actual = $row->{$method}();
                    } catch (\TypeError $e) {
                        // the old method promises a number or text but the column is empty: it never worked for this row either
                        $this->assertNull($row->{$column}, "{$model}::{$method} failed on a filled column: ".$e->getMessage());

                        continue;
                    }
                    $this->assertEquals($row->{$column}, $actual, "{$model}#{$row->id}::{$method}() should return {$column}");
                    $checked++;
                }
            }
        }

        $this->assertGreaterThan(500, $checked);
    }

    public function test_company_and_creator_names_are_found_through_the_study(): void
    {
        HotelStudies::load();

        foreach ([Room::class, Food::class, Casino::class, Meeting::class, Other::class, SalesChannel::class] as $class) {
            foreach ($class::query()->whereNotNull('hospitality_sector_id')->get() as $part) {
                $study = $part->hospitalitySector;

                $this->assertSame((int) $study->company_id, $part->getCompanyId(), "{$class}::getCompanyId");
                $this->assertSame($study->company->displayName(), $part->getCompanyName(), "{$class}::getCompanyName");
                $this->assertSame($study->creator->name, $part->getCreatorName(), "{$class}::getCreatorName");
            }
        }

        $study = HospitalitySector::findOrFail(305);
        $this->assertSame('Company 40', $study->getCompanyName());
    }

    public function test_a_date_that_is_not_set_gives_nothing_instead_of_a_crash(): void
    {
        app()->instance('dateIndexWithDate', []);

        // forceFill: only the date getters are checked here, whatever columns the table has
        // (FFE has no purchase date at all - its three "land purchase date" methods are unused leftovers - so it is not checked)
        $this->assertNull((new Acquisition)->forceFill(['purchase_date' => null])->getLandPurchaseDateFormatted());
        $this->assertNull((new PropertyAcquisition)->forceFill(['purchase_date' => null])->getPropertyPurchaseDateFormatted());
        $this->assertNull((new DepartmentExpense)->forceFill(['date' => null])->getDateFormatted());
    }
}
