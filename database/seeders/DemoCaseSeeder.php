<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Services\Expenses\ExpensesService;
use App\Services\Expenses\ManagementFeesService;
use App\Services\Expenses\StartUpCostService;
use App\Services\Investment\FfeCostService;
use App\Services\Investment\LandAcquisitionService;
use App\Services\Revenue\CasinosRevenueService;
use App\Services\Revenue\FoodsRevenueService;
use App\Services\Revenue\MeetingsRevenueService;
use App\Services\Revenue\OthersRevenueService;
use App\Services\Revenue\RoomsRevenueService;
use App\Services\Revenue\TypeListService;
use App\Services\StudyService;
use App\Support\ExpensePages;
use App\Support\StudyStreams;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Demo case: "Coral Horizon Resort & Spa" (new 5-star beach resort, Red Sea, Egypt)
//  Location: database/seeders/DemoCaseSeeder.php
//  Run:      php artisan db:seed --class=DemoCaseSeeder       (needs the demo company: it creates it by itself)
//
//  Fills EVERY input form of the demo study with the figures of CFO-Hotels-Demo-Case.xlsx, using the application's own
//  forms-as-data: each screen's blank form is read from its service (->present()), the demo figures are written into it,
//  and it is saved with the same service the screen uses (->save()).
//  Safe to run again: if the study already exists nothing is changed.
//  NOT YET RUN: written without access to PHP. If a step stops with an error, send me the text on the screen.
// ══════════════════════════════════════════════════════════════════

class DemoCaseSeeder extends Seeder
{
    private const STUDY = 'Coral Horizon Resort & Spa - Feasibility Study (Base Case)';

    private array $d;

    public function run(): void
    {
        $this->d = json_decode(<<<'JSON'
{"fx": [62.0, 66.03, 70.32, 74.89, 79.76, 84.55, 89.62, 95.0, 100.22, 105.73, 111.55, 117.13, 122.98], "types": {"foods": ["All-Day Dining Restaurant", "Specialty Restaurant", "Pool & Beach Bar"], "meetings": ["Conference & Banquet Hall"], "others": ["Diving Center & Water Sports"]}, "pages": {"rooms": {"flag": true, "percent": [{"name": "Credit Card Commissions", "values": [1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5]}, {"name": "Distribution & Reservation Systems", "values": [1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5]}, {"name": "Guest Transfers & Shuttle", "values": [1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0]}], "night": [{"name": "Housekeeping Supplies & Amenities", "amount": 190, "escalation": 11, "annual": 9, "terms": "monthly"}, {"name": "Laundry & Linen Replacement", "amount": 130, "escalation": 11, "annual": 9, "terms": "monthly"}], "guest": [{"name": "Welcome Amenities & Guest Services", "amount": 80, "escalation": 11, "annual": 9, "terms": "monthly"}], "disposable": [{"name": "Guest Consumables & Minibar Stock", "beginning": 4000000, "coverage": 45, "cash": 40, "dueDays": 60, "values": [1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 1.8]}], "manpower": [{"name": "Rooms Division Manager", "salary": 95000, "taxes": 22, "insurance": 3, "h1": 1, "h2": 1}, {"name": "Front Office Manager", "salary": 55000, "taxes": 18, "insurance": 4, "h1": 1, "h2": 1}, {"name": "Receptionists & Guest Relations", "salary": 14000, "taxes": 4, "insurance": 11, "h1": 20, "h2": 24}, {"name": "Bell Staff & Concierge", "salary": 10500, "taxes": 2, "insurance": 11, "h1": 15, "h2": 18}, {"name": "Executive Housekeeper", "salary": 60000, "taxes": 18, "insurance": 4, "h1": 1, "h2": 1}, {"name": "Housekeeping Supervisors", "salary": 19000, "taxes": 6, "insurance": 10, "h1": 7, "h2": 8}, {"name": "Room Attendants", "salary": 9800, "taxes": 2, "insurance": 11, "h1": 82, "h2": 96}, {"name": "Public Area & Laundry Staff", "salary": 9000, "taxes": 2, "insurance": 11, "h1": 29, "h2": 34}]}, "foods": {"flag": true, "percent": [{"name": "Food & Beverage Cost of Sales", "values": [35.5, 35.5, 35.5, 35.5, 35.5, 35.5, 35.5, 35.5, 35.5, 35.5, 35.5, 35.5, 35.5]}, {"name": "Operating Supplies, Linen & Breakage", "values": [2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5]}], "disposable": [{"name": "Kitchen & Bar Consumables", "beginning": 6000000, "coverage": 30, "cash": 50, "dueDays": 30, "values": [3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0]}], "manpower": [{"name": "F&B Director", "salary": 90000, "taxes": 22, "insurance": 3, "h1": 1, "h2": 1}, {"name": "Restaurant Managers", "salary": 42000, "taxes": 15, "insurance": 5, "h1": 4, "h2": 5}, {"name": "Chefs de Partie & Cooks", "salary": 15500, "taxes": 5, "insurance": 11, "h1": 53, "h2": 62}, {"name": "Stewards & Kitchen Helpers", "salary": 9000, "taxes": 2, "insurance": 11, "h1": 41, "h2": 48}, {"name": "Waiters & Hostesses", "salary": 10000, "taxes": 2, "insurance": 11, "h1": 63, "h2": 74}, {"name": "Bartenders", "salary": 12500, "taxes": 3, "insurance": 11, "h1": 14, "h2": 16}, {"name": "Banquet & Events Staff", "salary": 10000, "taxes": 2, "insurance": 11, "h1": 20, "h2": 24}]}, "casinos": {"flag": true, "percent": [{"name": "Gaming Taxes & Licence Fees", "values": [8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8]}, {"name": "Promotions & Complimentaries", "values": [5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5]}], "disposable": [{"name": "Gaming Consumables (cards, chips)", "beginning": 600000, "coverage": 90, "cash": 100, "dueDays": 0, "values": [1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0]}], "manpower": [{"name": "Gaming Manager", "salary": 85000, "taxes": 22, "insurance": 3, "h1": 1, "h2": 1}, {"name": "Dealers", "salary": 16000, "taxes": 5, "insurance": 11, "h1": 7, "h2": 8}, {"name": "Gaming Security & Cashiers", "salary": 12000, "taxes": 3, "insurance": 11, "h1": 4, "h2": 5}]}, "meetings": {"percent": [{"name": "Event Direct Costs (decor, AV, extra staff)", "values": [18, 18, 18, 18, 18, 18, 18, 18, 18, 18, 18, 18, 18]}, {"name": "Meeting Supplies & Equipment Rental", "values": [10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10]}], "guest": [{"name": "Event Refreshments & Materials", "amount": 40, "escalation": 11, "annual": 9, "terms": "monthly"}]}, "others": {"flag": true, "percent": [{"name": "Spa & Retail Product Costs", "values": [12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12]}, {"name": "Activity & Equipment Maintenance", "values": [4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4]}], "guest": [{"name": "Spa Consumables", "amount": 25, "escalation": 11, "annual": 9, "terms": "monthly"}], "manpower": [{"name": "Spa & Wellness Manager", "salary": 65000, "taxes": 20, "insurance": 3, "h1": 1, "h2": 1}, {"name": "Therapists & Fitness Trainers", "salary": 12500, "taxes": 3, "insurance": 11, "h1": 26, "h2": 30}, {"name": "Dive Centre Instructors", "salary": 16000, "taxes": 5, "insurance": 11, "h1": 5, "h2": 6}, {"name": "Recreation & Kids Club Team", "salary": 9500, "taxes": 2, "insurance": 11, "h1": 14, "h2": 16}]}, "general": {"flag": true, "percent": [{"name": "Professional Fees, Audit & Legal", "values": [1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2]}], "fixed": [{"name": "IT, Licences & Software", "amount": 1800000, "escalation": 11, "annual": 9, "terms": "monthly"}, {"name": "General Insurance & Miscellaneous", "amount": 450000, "escalation": 11, "annual": 9, "terms": "monthly"}, {"name": "Staff Meals, Transport & Uniforms", "amount": 2700000, "escalation": 11, "annual": 9, "terms": "monthly"}], "manpower": [{"name": "General Manager", "salary": 260000, "taxes": 25, "insurance": 2, "h1": 1, "h2": 1}, {"name": "Finance & Control Team", "salary": 24000, "taxes": 8, "insurance": 10, "h1": 8, "h2": 10}, {"name": "HR & Training Team", "salary": 19000, "taxes": 6, "insurance": 10, "h1": 3, "h2": 4}, {"name": "IT & Admin Staff", "salary": 15000, "taxes": 4, "insurance": 11, "h1": 4, "h2": 5}, {"name": "Purchasing & Stores", "salary": 13000, "taxes": 3, "insurance": 11, "h1": 4, "h2": 5}, {"name": "Security Team", "salary": 9500, "taxes": 2, "insurance": 11, "h1": 19, "h2": 22}]}, "marketing": {"flag": true, "percent": [{"name": "Advertising & Promotion", "values": [2.0, 2.0, 2.0, 2.0, 2.0, 2.0, 2.0, 2.0, 2.0, 2.0, 2.0, 2.0, 2.0]}], "fixed": [{"name": "Marketing Programme & Tour-Operator Support", "amount": 2600000, "escalation": 11, "annual": 9, "terms": "monthly"}], "manpower": [{"name": "Director of Sales & Marketing", "salary": 110000, "taxes": 23, "insurance": 3, "h1": 1, "h2": 1}, {"name": "Sales Managers", "salary": 38000, "taxes": 14, "insurance": 6, "h1": 3, "h2": 3}, {"name": "Reservations & Revenue Team", "salary": 16000, "taxes": 5, "insurance": 11, "h1": 4, "h2": 5}]}, "property": {"percent": [{"name": "Repairs & Maintenance", "values": [0.6, 0.6, 0.6, 0.6, 0.6, 0.6, 0.6, 0.6, 0.6, 0.6, 0.6, 0.6, 0.6]}], "fixed": [{"name": "Maintenance Contracts & Spare Parts", "amount": 1800000, "escalation": 11, "annual": 9, "terms": "monthly"}, {"name": "Engineering, Gardening & Pool Team (payroll)", "amount": 800000, "escalation": 11, "annual": 10, "terms": "monthly"}], "property": [{"percentage": 0.3, "annual": 5, "month": "03"}, {"percentage": 0.25, "annual": 5, "month": null}]}, "energy": {"percent": [{"name": "Electricity, Water & Fuel (variable)", "values": [3.5, 3.5, 3.5, 3.5, 3.5, 3.5, 3.5, 3.5, 3.5, 3.5, 3.5, 3.5, 3.5]}], "fixed": [{"name": "Desalination, Gas & Generator Fuel (base load)", "amount": 2400000, "escalation": 11, "annual": 9, "terms": "monthly"}]}}, "rooms": {"adr": {"Standard Double Room": 112, "Deluxe Double Room": 130, "Premier Double Room": 152, "Junior Suite": 185, "Regular Suite": 260, "Presidential Suite": 580}, "occ": [50, 58, 66, 72, 75, 76, 76, 76, 76, 76, 76, 76, 76], "season": [90, 95, 100, 100, 90, 85, 95, 100, 95, 100, 95, 100], "channels": {"direct-reservations": [15, 0, {"type": "system_default", "interval": "monthly"}], "agency": [50, 25, {"type": "customize", "rates": [30, 70, 0, 0], "due": [0, 60, 0, 0]}], "online-booking-engine": [20, 15, {"type": "customize", "rates": [100, 0, 0, 0], "due": [30, 0, 0, 0]}], "corporates": [8, 12, {"type": "customize", "rates": [100, 0, 0, 0], "due": [45, 0, 0, 0]}], "others-channels": [7, 8, {"type": "system_default", "interval": "quarterly"}]}}, "foods": {"All-Day Dining Restaurant": {"method": "guest_capture_x_meals_per_guest_x_cover_value_per_meal_method", "value": 450, "s": {"capture": [70, 78, 82, 85, 85, 85, 85, 85, 85, 85, 85, 85, 85], "meals": [1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2]}}, "Specialty Restaurant": {"method": "guest_capture_x_cover_value_per_guest_method", "value": 1200, "s": {"capture": [22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22]}}, "Lobby Lounge & Bar": {"method": "cover_count_target_per_day_x_cover_value_method", "value": 420, "s": {"target": [100, 130, 150, 160, 160, 160, 160, 160, 160, 160, 160, 160, 160]}}, "Café": {"method": "percentage_from_rooms_revenue", "value": 0, "s": {"rooms": [3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0]}}, "Pool & Beach Bar": {"method": "guest_capture_x_cover_value_per_guest_method", "value": 280, "s": {"capture": [55, 55, 55, 55, 55, 55, 55, 55, 55, 55, 55, 55, 55]}}}, "meetings": {"Wedding Halls": {"method": "guest_count_charges_per_guest_occupancy_rate_method", "value": 1800, "occupancy": [2, 5, 7, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8], "terms": {"type": "customize", "rates": [50, 50, 0, 0], "due": [0, 30, 0, 0]}}, "Meeting Rooms": {"method": "facility_count_daily_rent_occupancy_rate_method", "value": 28000, "occupancy": [10, 15, 20, 22, 25, 25, 25, 25, 25, 25, 25, 25, 25], "terms": {"type": "customize", "rates": [100, 0, 0, 0], "due": [30, 0, 0, 0]}}, "Conference & Banquet Hall": {"method": "percentage_from_f_b_revenue", "value": 0, "fb": [5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5], "terms": {"type": "system_default", "interval": "monthly"}}}, "others": {"GYM & SPA": {"method": "guest_capture_x_charges_per_guest_method", "value": 700, "capture": [12, 15, 18, 18, 18, 18, 18, 18, 18, 18, 18, 18, 18]}, "Diving Center & Water Sports": {"method": "guest_capture_x_charges_per_guest_method", "value": 1800, "capture": [4, 5, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6]}, "Laundry": {"method": "percentage_from_rooms_revenue", "value": 0, "rooms": [1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0]}, "Commercial Shops": {"method": "percentage_from_rooms_revenue", "value": 0, "rooms": [1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2, 1.2]}}, "ffe": [["Guest Room FF&E (300 rooms)", 1, 190000000, 8, 60, 5], ["Public Areas & Lobby", 1, 52000000, 10, 40, 5], ["Kitchens & F&B Equipment", 1, 62000000, 10, 30, 5], ["Pool, Beach & Landscape Furniture", 1, 33000000, 8, 40, 4], ["Spa, Gym & Dive Equipment", 1, 30000000, 8, 40, 5], ["IT, PMS & Telecom Systems", 1, 27000000, 5, 50, 4], ["Laundry Equipment", 1, 17000000, 10, 30, 5], ["Engineering & Vehicles", 1, 21000000, 10, 30, 5], ["Operating Supplies & Equipment (OS&E)", 1, 36000000, 3, 40, 3], ["Gaming Equipment", 2, 220000, 7, 40, 5]]}
JSON, true, 512, JSON_THROW_ON_ERROR);

        app()->setLocale('en');
        $this->call(DemoCompanySeeder::class);

        $user = User::query()->where('email', DemoCompanySeeder::EMAIL)->firstOrFail();
        Auth::setUser($user);                       // the study tables belong to the signed-in person's company
        $company = Company::query()->findOrFail($user->company_id);

        $study = \App\Models\HospitalitySector::query()->where('company_id', $company->id)->where('study_name', self::STUDY)->first();
        if ($study) {
            $this->command?->warn('The demo study already exists - its forms are filled in again (nothing is doubled).');
        }

        $this->step('Types', fn () => $this->types($company));
        $study ??= $this->step('Study', fn () => $this->study($user, $company));
        $study = $study->refresh();
        $this->step('Rooms', fn () => $this->rooms($study));
        $this->step('Food and beverage', fn () => $this->foods($study));
        $this->step('Gaming', fn () => $this->casinos($study));
        $this->step('Meetings', fn () => $this->meetings($study));
        $this->step('Other revenue', fn () => $this->others($study));
        $this->step('Expenses (nine pages)', fn () => $this->expenses($study));
        $this->step('Management fees', fn () => $this->fees($study));
        $this->step('Start-up cost', fn () => $this->startUp($study));
        $this->step('Land and construction', fn () => $this->land($study));
        $this->step('FF&E', fn () => $this->ffe($study));

        $this->command?->info('Done. Sign in as demo@cfo-hotels.com and open the study "'.self::STUDY.'".');
    }

    private function step(string $name, \Closure $work): mixed
    {
        try {
            $result = $work();
            $this->command?->info("OK   {$name}");

            return $result;
        } catch (\Throwable $e) {
            $this->command?->error("STOP at: {$name}");
            $this->command?->error($e->getMessage());
            throw $e;
        }
    }

    // ── helpers ──────────────────────────────────────────────────

    /** A list of values, one per operating year, as the screens keep it: [year index => value]. Short lists repeat their last value. */
    private function series(array $years, array $values): array
    {
        $out = [];
        foreach (array_values($years) as $i => $year) {
            $out[$year['index']] = $values[$i] ?? end($values);
        }

        return $out;
    }

    /** The blank forms hand over some empty fields as objects; the services want plain arrays. */
    private function plain(array $form): array
    {
        return json_decode(json_encode($form), true);
    }

    private function find(array &$rows, string $name): ?int
    {
        foreach ($rows as $i => $row) {
            if (($row['name'] ?? null) === $name) {
                return $i;
            }
        }
        throw new \RuntimeException("The form has no line called \"{$name}\".");
    }

    private function terms(array $t): array
    {
        return $t['type'] === 'customize'
            ? ['type' => 'customize', 'interval' => 'monthly', 'rates' => $t['rates'], 'due' => $t['due']]
            : ['type' => 'system_default', 'interval' => $t['interval'] ?? 'monthly', 'rates' => [0, 0, 0, 0], 'due' => [0, 0, 0, 0]];
    }

    private function typeId(Company $company, string $stream, string $title): int
    {
        $row = StudyStreams::types($company, $stream)->firstWhere('title', $title);

        return (int) ($row['id'] ?? throw new \RuntimeException("Type \"{$title}\" not found in {$stream}."));
    }

    // ── forms ────────────────────────────────────────────────────

    private function types(Company $company): void
    {
        $service = app(TypeListService::class);
        foreach ($this->d['types'] as $stream => $titles) {
            $rows = collect($service->rows($company, $stream))->map(fn ($r) => ['id' => $r['id'], 'title' => $r['title']])->all();
            foreach ($titles as $title) {
                if (! collect($rows)->contains('title', $title)) {
                    $rows[] = ['id' => null, 'title' => $title];
                }
            }
            $service->save($company, $stream, $rows);
        }
    }

    private function study(User $user, Company $company): \App\Models\HospitalitySector
    {
        $t = fn (string $stream, string $title) => $this->typeId($company, $stream, $title);
        $data = [
            'study_name' => self::STUDY, 'property_name' => 'Coral Horizon Resort & Spa', 'region' => 'Sahl Hasheesh, Hurghada',
            'property_status' => 'under_development', 'star_rating' => 5, 'country_id' => 1, 'state_id' => 5,
            'study_start' => '2027-01', 'duration_in_years' => 15,
            'development_start_month' => 0, 'development_duration' => 33, 'operation_start_month' => 33,
            'financial_year_start_month' => 'january', 'main_functional_currency' => 1, 'additional_currency' => 2, 'exchange_rate' => 54.5,
            'corporate_taxes_rate' => 22.5, 'investment_return_rate' => 18, 'perpetual_growth_rate' => 7,
            'streams' => [
                'rooms' => ['on' => true, 'mode' => 'types', 'rows' => [
                    ['type_id' => $t('rooms', 'Standard Double Room'), 'count' => 96, 'value' => 2.0],
                    ['type_id' => $t('rooms', 'Deluxe Double Room'), 'count' => 110, 'value' => 2.0],
                    ['type_id' => $t('rooms', 'Premier Double Room'), 'count' => 50, 'value' => 2.2],
                    ['type_id' => $t('rooms', 'Junior Suite'), 'count' => 30, 'value' => 2.5],
                    ['type_id' => $t('rooms', 'Regular Suite'), 'count' => 12, 'value' => 3.0],
                    ['type_id' => $t('rooms', 'Presidential Suite'), 'count' => 2, 'value' => 4.0],
                ]],
                'foods' => ['on' => true, 'mode' => 'types', 'rows' => [
                    ['type_id' => $t('foods', 'All-Day Dining Restaurant'), 'count' => 1, 'value' => 350],
                    ['type_id' => $t('foods', 'Specialty Restaurant'), 'count' => 3, 'value' => 80],
                    ['type_id' => $t('foods', 'Lobby Lounge & Bar'), 'count' => 1, 'value' => 120],
                    ['type_id' => $t('foods', 'Café'), 'count' => 1, 'value' => 60],
                    ['type_id' => $t('foods', 'Pool & Beach Bar'), 'count' => 2, 'value' => 80],
                ]],
                'casinos' => ['on' => true, 'mode' => 'types', 'rows' => [['type_id' => $t('casinos', 'Small Gaming Hall'), 'count' => 1, 'value' => 60]]],
                'meetings' => ['on' => true, 'mode' => 'types', 'rows' => [
                    ['type_id' => $t('meetings', 'Wedding Halls'), 'count' => 1, 'value' => 400],
                    ['type_id' => $t('meetings', 'Meeting Rooms'), 'count' => 4, 'value' => 40],
                    ['type_id' => $t('meetings', 'Conference & Banquet Hall'), 'count' => 1, 'value' => 250],
                ]],
                'others' => ['on' => true, 'mode' => 'types', 'rows' => [
                    ['type_id' => $t('others', 'GYM & SPA'), 'count' => 1],
                    ['type_id' => $t('others', 'Diving Center & Water Sports'), 'count' => 1],
                    ['type_id' => $t('others', 'Laundry'), 'count' => 1],
                    ['type_id' => $t('others', 'Commercial Shops'), 'count' => 8],
                ]],
            ],
            'channels' => ['on' => true, 'names' => array_keys($this->d['rooms']['channels'])],
        ];

        return app(StudyService::class)->create($user, $data);
    }

    private function rooms(\App\Models\HospitalitySector $study): void
    {
        $s = app(RoomsRevenueService::class);
        $p = $s->present($study);
        $f = $p['form'];
        $r = $this->d['rooms'];
        $f['currency'] = 2;
        foreach ($f['rooms'] as $i => $room) {
            $f['rooms'][$i]['adr'] = $r['adr'][$room['name']];
            $f['rooms'][$i]['escalation'] = 3.5;
            $f['rooms'][$i]['annual'] = 3.5;
        }
        $f['exchange'] = $this->series($p['years'], $this->d['fx']);
        $f['occupancy']['mode'] = 'general';
        $f['occupancy']['general'] = $this->series($p['years'], $r['occ']);
        $f['seasonality']['type'] = 'general';
        $f['seasonality']['interval'] = 'monthly';
        $f['seasonality']['general'] = array_combine(array_map(fn ($m) => sprintf('%02d', $m), range(1, 12)), $r['season']);
        foreach ($f['channels'] as $i => $channel) {
            [$share, $discount, $terms] = $r['channels'][$channel['name']];
            $f['channels'][$i]['share'] = $this->series($p['years'], [$share]);
            $f['channels'][$i]['discount'] = $this->series($p['years'], [$discount]);
            $f['channels'][$i]['terms'] = $this->terms($terms);
        }
        $f['collection'] = ['mode' => 'per_channel', 'general' => $this->terms(['type' => 'system_default', 'interval' => 'monthly'])];
        $s->save($study, $this->plain($f));
    }

    private function foods(\App\Models\HospitalitySector $study): void
    {
        $s = app(FoodsRevenueService::class);
        $p = $s->present($study);
        $f = $p['form'];
        foreach ($f['facilities'] as $i => $facility) {
            $cfg = $this->d['foods'][$facility['name']];
            $f['facilities'][$i]['method'] = $cfg['method'];
            $f['facilities'][$i]['currency'] = 1;
            $f['facilities'][$i]['value'] = $cfg['value'];
            $f['facilities'][$i]['escalation'] = $cfg['value'] ? 11 : 0;
            $f['facilities'][$i]['annual'] = $cfg['value'] ? 9 : 0;
            foreach ($cfg['s'] as $name => $values) {
                $f['facilities'][$i][$name] = $this->series($p['years'], $values);
            }
            $f['facilities'][$i]['terms'] = $this->terms(['type' => 'system_default', 'interval' => 'monthly']);
        }
        $f['collection'] = ['mode' => 'general', 'general' => $this->terms(['type' => 'system_default', 'interval' => 'monthly'])];
        $s->save($study, $this->plain($f));
    }

    private function casinos(\App\Models\HospitalitySector $study): void
    {
        $s = app(CasinosRevenueService::class);
        $p = $s->present($study);
        $f = $p['form'];
        $f['facilities'][0]['method'] = 'guest_count_target_per_day_charges_per_guest_method';
        $f['facilities'][0]['currency'] = 2;
        $f['facilities'][0]['value'] = 35;
        $f['facilities'][0]['escalation'] = 3.5;
        $f['facilities'][0]['annual'] = 3.5;
        $f['facilities'][0]['target'] = $this->series($p['years'], [15, 20, 25]);
        $f['facilities'][0]['terms'] = $this->terms(['type' => 'system_default', 'interval' => 'monthly']);
        $f['collection'] = ['mode' => 'general', 'general' => $this->terms(['type' => 'system_default', 'interval' => 'monthly'])];
        $s->save($study, $this->plain($f));
    }

    private function meetings(\App\Models\HospitalitySector $study): void
    {
        $s = app(MeetingsRevenueService::class);
        $p = $s->present($study);
        $f = $p['form'];
        foreach ($f['facilities'] as $i => $facility) {
            $cfg = $this->d['meetings'][$facility['name']];
            $f['facilities'][$i]['method'] = $cfg['method'];
            $f['facilities'][$i]['currency'] = 1;
            $f['facilities'][$i]['value'] = $cfg['value'];
            $f['facilities'][$i]['escalation'] = $cfg['value'] ? 11 : 0;
            $f['facilities'][$i]['annual'] = $cfg['value'] ? 9 : 0;
            if (isset($cfg['occupancy'])) {
                $f['facilities'][$i]['occupancy'] = $this->series($p['years'], $cfg['occupancy']);
            }
            if (isset($cfg['fb'])) {
                $f['facilities'][$i]['fb'] = $this->series($p['years'], $cfg['fb']);
            }
            $f['facilities'][$i]['terms'] = $this->terms($cfg['terms']);
        }
        $months = array_combine(array_map(fn ($m) => sprintf('%02d', $m), range(1, 12)), [5, 5, 8, 9, 8, 8, 11, 12, 9, 10, 8, 7]);
        $f['guestPlan']['type'] = 'general';
        $f['guestPlan']['interval'] = 'monthly';
        $f['guestPlan']['months'] = $months;
        $f['rentPlan']['type'] = 'general';
        $f['rentPlan']['interval'] = 'quarterly';
        $f['rentPlan']['quarters'] = ['quarter-one' => 28, 'quarter-two' => 24, 'quarter-three' => 20, 'quarter-four' => 28];
        $f['collection'] = ['mode' => 'per_facility', 'general' => $this->terms(['type' => 'system_default', 'interval' => 'monthly'])];
        $s->save($study, $this->plain($f));
    }

    private function others(\App\Models\HospitalitySector $study): void
    {
        $s = app(OthersRevenueService::class);
        $p = $s->present($study);
        $f = $p['form'];
        foreach ($f['facilities'] as $i => $facility) {
            $cfg = $this->d['others'][$facility['name']];
            $f['facilities'][$i]['method'] = $cfg['method'];
            $f['facilities'][$i]['currency'] = 1;
            $f['facilities'][$i]['value'] = $cfg['value'];
            $f['facilities'][$i]['escalation'] = $cfg['value'] ? 11 : 0;
            $f['facilities'][$i]['annual'] = $cfg['value'] ? 9 : 0;
            foreach (['capture', 'rooms'] as $key) {
                if (isset($cfg[$key])) {
                    $f['facilities'][$i][$key] = $this->series($p['years'], $cfg[$key]);
                }
            }
            $f['facilities'][$i]['terms'] = $this->terms(['type' => 'system_default', 'interval' => 'monthly']);
        }
        $f['collection'] = ['mode' => 'general', 'general' => $this->terms(['type' => 'system_default', 'interval' => 'monthly'])];
        $s->save($study, $this->plain($f));
    }

    private function expenses(\App\Models\HospitalitySector $study): void
    {
        $s = app(ExpensesService::class);
        foreach ($this->d['pages'] as $key => $cfg) {
            $page = ExpensePages::find($key);
            $p = $s->present($study, $page);
            $f = $p['form'];
            $f['flag'] = ! empty($cfg['flag']);
            foreach ($page['sections'] as $section) {
                $name = $section['name'];
                $rows = $f['sections'][$name];
                $items = match (true) {
                    $section['kind'] === 'percent' => $cfg['percent'] ?? [],
                    $section['kind'] === 'disposable' => $cfg['disposable'] ?? [],
                    $section['kind'] === 'manpower' => $cfg['manpower'] ?? [],
                    $section['kind'] === 'property' => $cfg['property'] ?? [],
                    $section['style'] === 'night' => $cfg['night'] ?? [],
                    $section['style'] === 'guest' || $section['style'] === 'guestNight' => $cfg['guest'] ?? [],
                    default => $cfg['fixed'] ?? [],
                };
                foreach ($items as $i => $item) {
                    $row = $rows[$i];
                    switch ($section['kind']) {
                        case 'percent':
                            $row['name'] = $item['name'];
                            $row['values'] = $this->series($p['years'], $item['values']);
                            break;
                        case 'disposable':
                            $row = array_replace($row, array_diff_key($item, ['values' => 1]), ['values' => $this->series($p['years'], $item['values'])]);
                            break;
                        case 'cost':
                            $row = array_replace($row, ['name' => $item['name'], 'currency' => 1, 'amount' => $item['amount'], 'escalation' => $item['escalation'], 'annual' => $item['annual']]);
                            if ($section['style'] === 'fixed') {
                                $row['terms'] = $item['terms'];
                            }
                            break;
                        case 'manpower':
                            $heads = [];
                            foreach (array_values($p['dates']) as $n => $date) {
                                $heads[$date['index']] = $n < 12 ? $item['h1'] : $item['h2'];
                            }
                            $row = array_replace($row, ['name' => $item['name'], 'salary' => $item['salary'], 'currency' => 1, 'escalation' => 11, 'annual' => 10,
                                'taxes' => $item['taxes'], 'insurance' => $item['insurance'], 'heads' => $heads]);
                            break;
                        case 'property':
                            $row = array_replace($row, ['currency' => 1, 'percentage' => $item['percentage'], 'annual' => $item['annual']]);
                            if ($item['month'] !== null) {
                                $row['month'] = $item['month'];
                            }
                            break;
                    }
                    $rows[$i] = $row;
                }
                $f['sections'][$name] = $rows;
            }
            $s->save($study, $page, $this->plain($f));
        }
    }

    private function fees(\App\Models\HospitalitySector $study): void
    {
        $s = app(ManagementFeesService::class);
        $p = $s->present($study);
        $f = $p['form'];
        $f['sections']['base_management_fees_as_percentage_from_revenues'][0]['values'] = $this->series($p['years'], [3]);
        $f['sections']['incentive_management_fees_as_percentage_from_revenues'][0]['values'] = $this->series($p['years'], [8]);
        $s->save($study, $this->plain($f));
    }

    private function startUp(\App\Models\HospitalitySector $study): void
    {
        $s = app(StartUpCostService::class);
        $p = $s->present($study);
        $f = $p['form'];
        $startUp = [['Brand & Operator Technical Services Fee', 20000000, 24, 50, 30], ['Recruitment & Training of Opening Team', 30000000, 29, 100, 0], ['Pre-opening Marketing & Launch', 45000000, 30, 60, 60]];
        foreach ($startUp as $i => [$name, $cost, $date, $cash, $due]) {
            $f['sections'][ExpensePages::START_UP][$i] = array_replace($f['sections'][ExpensePages::START_UP][$i], ['name' => $name, 'currency' => 1, 'cost' => $cost, 'date' => $date, 'cash' => $cash, 'dueDays' => $due]);
        }
        // pre-operating: [name, month index from, to, amount per month]  (index 0 = Jan 2027)
        $pre = [['Pre-opening Payroll - Management Team', [[20, 26, 2500000], [27, 32, 4000000]]], ['Pre-opening Office & Administration', [[18, 32, 1000000]]], ['Pre-opening Sales & Travel', [[24, 32, 1500000]]]];
        foreach ($pre as $i => [$name, $spans]) {
            $amounts = [];
            foreach ($p['dates'] as $date) {
                $amounts[$date['index']] = 0;
                foreach ($spans as [$from, $to, $amount]) {
                    if ($date['index'] >= $from && $date['index'] <= $to) {
                        $amounts[$date['index']] = $amount;
                    }
                }
            }
            $f['sections'][ExpensePages::PRE_OPERATING][$i] = array_replace($f['sections'][ExpensePages::PRE_OPERATING][$i], ['name' => $name, 'currency' => 1, 'amounts' => $amounts]);
        }
        $s->save($study, $this->plain($f));
    }

    private function loan(int $period, string $interval = 'quartly'): array
    {
        return ['loan_type' => 'normal', 'base_rate' => 20, 'margin_rate' => 2, 'period' => $period, 'grace_period' => 0, 'installment_interval' => $interval,
            'step_up_rate' => 0, 'step_up_interval' => 'annually', 'step_down_rate' => 0, 'step_down_interval' => 'annually'];
    }

    private function land(\App\Models\HospitalitySector $study): void
    {
        $s = app(LandAcquisitionService::class);
        $f = $s->present($study)['form'];
        $f['land'] = array_replace($f['land'], ['date' => 0, 'cost' => 300000000, 'contingency' => 2, 'method' => 'installment', 'first' => 30, 'second' => 20,
            'after' => 6, 'count' => 4, 'interval' => 'quarterly', 'equity' => 80]);
        $f['hard'] = array_replace($f['hard'], ['cost' => 1150000000, 'contingency' => 7, 'start' => 6, 'duration' => 26, 'method' => 's-curve',
            'down' => 10, 'one' => 60, 'dueOne' => 30, 'dueTwo' => 60, 'equity' => 70]);
        $f['soft'] = array_replace($f['soft'], ['cost' => 150000000, 'contingency' => 5, 'start' => 0, 'duration' => 32, 'method' => 'straight-line',
            'down' => 20, 'one' => 40, 'dueOne' => 30, 'dueTwo' => 60]);
        $f['loans'][LAND_LOAN] = $this->loan(60, 'quartly');
        $f['loans'][HARD_COST_CONSTRUCTION] = $this->loan(96, 'quartly');
        $s->save($study, $this->plain($f));
    }

    private function ffe(\App\Models\HospitalitySector $study): void
    {
        $s = app(FfeCostService::class);
        $f = $s->present($study)['form'];
        foreach ($this->d['ffe'] as $i => [$name, $currency, $cost, $dep, $rate, $interval]) {
            $f['rows'][$i] = array_replace($f['rows'][$i], ['name' => $name, 'currency' => $currency, 'cost' => $cost, 'contingency' => 5,
                'depreciation' => $dep, 'replacementRate' => $rate, 'replacementInterval' => $interval]);
        }
        $f['dates'] = ['start' => 26, 'duration' => 6, 'method' => 'steady-growth'];
        $f['terms'] = ['down' => 30, 'one' => 50, 'dueOne' => 60, 'dueTwo' => 90];
        $f['equity'] = 60;
        $f['loans'][FFE_COST] = $this->loan(60, 'semi annually');
        $s->save($study, $this->plain($f));
    }
}
