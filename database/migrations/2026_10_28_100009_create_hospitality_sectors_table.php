<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: hospitality_sectors
//  Location: database/migrations/2026_10_28_100009_create_hospitality_sectors_table.php
//
//  THE STUDY: one hotel business plan (dates, rates, which sections it has, collection policies). Everything else hangs on a study.
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  creator_id is the owner of the study (people are suspended, never deleted, so a study never loses its owner).
//  Links: company_id -> companies (cannot be deleted while used); creator_id -> users (cannot be deleted while used).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitality_sectors', function (Blueprint $table) {
            $table->id();
            $table->string('study_name')->nullable();
            $table->string('property_name')->nullable();
            $table->longText('operation_dates')->nullable();
            $table->string('region')->nullable();
            $table->longText('study_dates')->nullable();
            $table->string('operation_start_date')->nullable();
            $table->tinyInteger('star_rating')->nullable();
            $table->integer('country_id')->nullable();
            $table->string('property_status')->nullable();
            $table->integer('state_id')->nullable();
            $table->tinyInteger('duration_in_years')->nullable();
            $table->string('type')->nullable()->default('IncomeStatement');
            $table->enum('duration_type', ['monthly', 'annually', 'semi-annually', 'quarterly'])->default('monthly');
            $table->string('study_start_date');
            $table->string('study_end_date');
            $table->unsignedBigInteger('development_start_month');
            $table->string('development_start_date');
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->unsignedBigInteger('development_duration');
            $table->unsignedBigInteger('operation_start_month');
            $table->string('additional_currency');
            $table->decimal('exchange_rate', 10, 2);
            $table->decimal('corporate_taxes_rate', 10, 2);
            $table->decimal('investment_return_rate', 10, 2);
            $table->decimal('perpetual_growth_rate', 10, 2);
            $table->foreignId('creator_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('updated_at')->nullable();
            $table->integer('casino_cover')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->string('main_functional_currency');
            $table->string('financial_year_start_month')->nullable();
            $table->integer('total_f&b_facility_count')->nullable();
            $table->integer('total_f&b_cover_count')->nullable();
            $table->integer('total_casino_facility_count')->nullable();
            $table->integer('total_casino_cover_count')->nullable();
            $table->integer('total_meeting_cover_count')->nullable();
            $table->integer('total_meeting_facility_count')->nullable();
            $table->integer('rooms_count')->nullable();
            $table->string('average_guest_count')->nullable();
            $table->string('is_total_rooms')->nullable()->default('0');
            $table->tinyInteger('is_total_foods')->nullable()->default(0);
            $table->tinyInteger('is_total_casinos')->nullable()->default(0);
            $table->tinyInteger('has_casino_section')->nullable()->default(0);
            $table->tinyInteger('has_meeting_section')->nullable()->default(0);
            $table->tinyInteger('has_other_section')->nullable()->default(0);
            $table->tinyInteger('is_total_other')->nullable()->default(0);
            $table->tinyInteger('is_total_meetings')->nullable()->default(0);
            $table->tinyInteger('has_sales_channels')->nullable()->default(0);
            $table->longText('selected_sales_revenues')->nullable();
            $table->longText('general_occupancy_rate')->nullable();
            $table->string('room_collection_policy_type')->nullable();
            $table->string('seasonality_interval')->nullable();
            $table->string('seasonality_type')->nullable();
            $table->longText('general_seasonality')->nullable();
            $table->string('add_sales_channels_share_discount')->nullable();
            $table->longText('study_navigators')->nullable();
            $table->string('guest_meeting_seasonality_type')->nullable();
            $table->longText('guest_meeting_general_seasonality')->nullable();
            $table->string('guest_meeting_seasonality_interval')->nullable();
            $table->string('rent_meeting_seasonality_type')->nullable();
            $table->longText('rent_meeting_general_seasonality')->nullable();
            $table->string('rent_meeting_seasonality_interval')->nullable();
            $table->string('has_rooms_manpower')->nullable()->default('0');
            $table->string('has_foods_manpower')->nullable()->default('0');
            $table->string('has_casinos_manpower')->nullable()->default('0');
            $table->string('has_sales_and_general_manpower')->nullable()->default('0');
            $table->string('has_other_revenue_manpower')->nullable()->default('0');
            $table->string('other_collection_policy_type')->nullable();
            $table->string('meeting_collection_policy_type')->nullable();
            $table->string('casino_collection_policy_type')->nullable();
            $table->string('food_collection_policy_type')->nullable();
            $table->string('rooms_general_collection_policy_interval')->nullable();
            $table->string('rooms_general_collection_policy_value')->nullable();
            $table->string('rooms_general_collection_policy_type')->nullable();
            $table->string('others_general_collection_policy_value')->nullable();
            $table->string('others_general_collection_policy_interval')->nullable();
            $table->string('others_general_collection_policy_type')->nullable();
            $table->string('meetings_general_collection_policy_interval')->nullable();
            $table->string('meetings_general_collection_policy_value')->nullable();
            $table->string('meetings_general_collection_policy_type')->nullable();
            $table->string('foods_general_collection_policy_value')->nullable();
            $table->string('foods_general_collection_policy_interval')->nullable();
            $table->string('foods_general_collection_policy_type')->nullable();
            $table->string('casinos_general_collection_policy_value')->nullable();
            $table->string('casinos_general_collection_policy_interval')->nullable();
            $table->string('casinos_general_collection_policy_type')->nullable();
            $table->tinyInteger('has_visit_room_section')->default(0);
            $table->tinyInteger('has_visit_food_section')->default(0);
            $table->tinyInteger('has_visit_casino_section')->default(0);
            $table->tinyInteger('has_visit_meeting_section')->default(0);
            $table->tinyInteger('has_visit_other_section')->default(0);
            $table->longText('exchange_rates')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitality_sectors');
    }
};
